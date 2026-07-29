<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Transport;

use JsonException;
use Tinywan\Mcp\Contracts\ToolResolverInterface;
use Tinywan\Mcp\Protocol\Error\ProtocolError;
use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\NativeProtocolDriver;
use Tinywan\Mcp\Protocol\ProtocolParser;
use Tinywan\Mcp\Protocol\Result\ProtocolDispatchResult;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Runtime\Deadline;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Security\AuthenticationException;
use Tinywan\Mcp\Tool\Schema\ToolSchemaValidator;
use Tinywan\Mcp\Tool\ToolRuntime;

final readonly class StreamableHttpTransport
{
    public function __construct(
        private ServerRegistry $registry,
        private ToolResolverInterface $tools,
        private ProtocolParser $parser = new ProtocolParser(),
        private HttpBoundaryValidator $boundary = new HttpBoundaryValidator(),
        private HeaderMirrorValidator $headers = new HeaderMirrorValidator(),
    ) {}

    public function handle(HttpRequestContext $http): HttpResponse
    {
        $server = $this->registry->byPath($http->path);
        if ($server === null) {
            return new HttpResponse(404, body: 'Not Found');
        }

        try {
            $this->boundary->validate($http, $server);
            $principal = $server->authenticator->authenticate($http);
            $request = $this->parser->parse($http->body);
            $this->headers->validate($http, $request, $server);
            $context = new ExecutionContext(
                $principal,
                bin2hex(random_bytes(8)),
                $request->protocolVersion,
                $request->clientInfo,
                $request->clientCapabilities,
                Deadline::none(),
            );
            $driver = new NativeProtocolDriver($server, new ToolRuntime(new ToolSchemaValidator(), $this->tools));

            return $this->mapResult($driver->dispatch($request, $context));
        } catch (HttpTransportException $exception) {
            return new HttpResponse($exception->status, $exception->headers, $exception->getMessage());
        } catch (AuthenticationException) {
            return $this->protocolError(ProtocolErrors::unauthorized());
        } catch (ProtocolException $exception) {
            return $this->protocolError($exception->error);
        }
    }

    private function mapResult(ProtocolDispatchResult $result): HttpResponse
    {
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
