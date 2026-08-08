<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Transport;

use JsonException;
use Throwable;
use Tinywan\Mcp\Contracts\PromptResolverInterface;
use Tinywan\Mcp\Contracts\ResourceResolverInterface;
use Tinywan\Mcp\Contracts\ToolResolverInterface;
use Tinywan\Mcp\Governance\RequestGovernor;
use Tinywan\Mcp\Observability\AuditRecorder;
use Tinywan\Mcp\Prompt\FactoryPromptResolver;
use Tinywan\Mcp\Prompt\PromptRuntime;
use Tinywan\Mcp\Protocol\Error\ProtocolError;
use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\NativeProtocolDriver;
use Tinywan\Mcp\Protocol\ProtocolParser;
use Tinywan\Mcp\Protocol\Result\ProtocolDispatchResult;
use Tinywan\Mcp\Protocol\Result\StreamResult;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Resource\FactoryResourceResolver;
use Tinywan\Mcp\Resource\ResourceRuntime;
use Tinywan\Mcp\Runtime\CancellationToken;
use Tinywan\Mcp\Runtime\Deadline;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Security\AuthenticationException;
use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Subscription\SubscriptionRuntime;
use Tinywan\Mcp\Tool\Schema\ToolSchemaValidator;
use Tinywan\Mcp\Tool\ToolRuntime;

final readonly class StreamableHttpTransport
{
    public function __construct(
        private ServerRegistry $registry,
        private ToolResolverInterface $tools,
        private ?ResourceResolverInterface $resources = null,
        private ?PromptResolverInterface $prompts = null,
        private ProtocolParser $parser = new ProtocolParser(),
        private HttpBoundaryValidator $boundary = new HttpBoundaryValidator(),
        private HeaderMirrorValidator $headers = new HeaderMirrorValidator(),
        private TransportServices $services = new TransportServices(),
    ) {}

    public function handle(HttpRequestContext $http): HttpResponse
    {
        $server = $this->registry->byPath($http->path);
        if ($server === null) {
            return new HttpResponse(404, body: 'Not Found');
        }

        $traceId = bin2hex(random_bytes(8));
        $method = $this->safeMethod($http->header('mcp-method'));
        $audit = AuditRecorder::start(
            $this->services->audit,
            $this->services->telemetry,
            $server->id,
            $traceId,
            $method,
        );
        $governor = null;
        try {
            $this->boundary->validate($http, $server);
            $principal = $server->authenticator->authenticate($http);
            $audit->record('authentication', 'succeeded');
            $governor = new RequestGovernor($server, $principal, $http, $traceId, $method);
            $governor->admit();

            return $this->dispatch($server, $principal, $http, $traceId, $audit, $governor);
        } catch (HttpTransportException $exception) {
            $audit->record('response', (string) $exception->status);

            return new HttpResponse($exception->status, $exception->headers, $exception->getMessage());
        } catch (AuthenticationException) {
            $audit->record('authentication', 'rejected');
            $audit->record('response', '401');

            return $this->protocolError(ProtocolErrors::unauthorized());
        } catch (ProtocolException $exception) {
            $audit->record('dispatch', 'rejected');
            $audit->record('response', (string) $exception->error->httpStatus);

            return $this->protocolError($exception->error);
        } catch (Throwable) {
            $audit->record('dispatch', 'failed');
            $audit->record('response', '500');

            return $this->protocolError(ProtocolErrors::internal(null, $traceId));
        } finally {
            $governor?->release();
        }
    }

    private function dispatch(
        ServerDefinition $server,
        Principal $principal,
        HttpRequestContext $http,
        string $traceId,
        AuditRecorder $audit,
        RequestGovernor $governor,
    ): HttpResponse {
        $request = $this->parser->parse($http->body);
        $this->headers->validate($http, $request, $server);
        $replay = $governor->replay($request->id);
        if ($replay !== null) {
            $governor->enforce($replay, $request->id);
            $audit->record('authorization', 'replayed');
            $audit->record('dispatch', 'replayed');
            $audit->record('response', 'succeeded');

            return $replay;
        }
        $context = new ExecutionContext(
            $principal,
            $traceId,
            $request->protocolVersion,
            $request->clientInfo,
            $request->clientCapabilities,
            Deadline::afterMilliseconds($server->options->governance->deadlineMs),
            new CancellationToken(),
        );
        $subscriptions = $this->services->subscriptions;
        $driver = new NativeProtocolDriver(
            $server,
            new ToolRuntime(new ToolSchemaValidator(), $this->tools),
            new ResourceRuntime($this->resources ?? new FactoryResourceResolver()),
            new PromptRuntime($this->prompts ?? new FactoryPromptResolver()),
            new SubscriptionRuntime($subscriptions->resolver, $subscriptions->cancellations),
            $subscriptions->cancellations,
        );
        $audit->record('dispatch', 'started');
        $response = $this->mapResult($driver->dispatch($request, $context));
        $governor->enforce($response, $request->id);
        $governor->store($response, $request->id);
        $audit->record('authorization', 'evaluated');
        $audit->record('handler', $response->status < 500 ? 'completed' : 'failed');
        if ($request->method === 'notifications/cancelled') {
            $audit->record('cancellation', 'accepted');
        }
        if (($response->headers['Content-Type'] ?? null) === 'text/event-stream') {
            $audit->record('stream', 'terminated');
        }
        $audit->record('dispatch', 'completed');
        $audit->record('response', (string) $response->status);

        return $response;
    }

    private function safeMethod(?string $method): string
    {
        return $method !== null && preg_match('/\A[a-z]+\/[a-z_]+\z/D', $method) === 1 ? $method : 'unknown';
    }

    private function mapResult(ProtocolDispatchResult $result): HttpResponse
    {
        if ($result instanceof StreamResult) {
            try {
                return new HttpResponse(
                    $result->status(),
                    $result->headers(),
                    (new EventStreamEncoder())->encode($result->events),
                );
            } catch (JsonException) {
                return $this->protocolError(ProtocolErrors::internal(null, 'response-encoding'));
            }
        }

        $payload = $result->payload();
        if ($payload === null) {
            return new HttpResponse($result->status(), $result->headers());
        }

        try {
            return new HttpResponse(
                $result->status(),
                $result->headers(),
                json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            );
        } catch (JsonException) {
            return $this->protocolError(ProtocolErrors::internal(null, 'response-encoding'));
        }
    }

    private function protocolError(ProtocolError $error): HttpResponse
    {
        try {
            $body = json_encode($error->toEnvelope(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException) {
            $body = '{"jsonrpc":"2.0","error":{"code":-32603,"message":"Internal error"}}';
        }

        return new HttpResponse($error->httpStatus, ['Content-Type' => 'application/json'], $body);
    }
}
