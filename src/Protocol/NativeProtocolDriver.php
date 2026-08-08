<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol;

use Tinywan\Mcp\Contracts\CancellationCoordinatorInterface;
use Tinywan\Mcp\Contracts\ProtocolDriverInterface;
use Tinywan\Mcp\Prompt\FactoryPromptResolver;
use Tinywan\Mcp\Prompt\PromptRuntime;
use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\Result\AcceptedResult;
use Tinywan\Mcp\Protocol\Result\JsonResult;
use Tinywan\Mcp\Protocol\Result\ProtocolDispatchResult;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Resource\FactoryResourceResolver;
use Tinywan\Mcp\Resource\ResourceRuntime;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Runtime\ProcessCancellationCoordinator;
use Tinywan\Mcp\Subscription\FactorySubscriptionResolver;
use Tinywan\Mcp\Subscription\SubscriptionRuntime;
use Tinywan\Mcp\Tool\ToolRuntime;
use Tinywan\Mcp\Version;

final readonly class NativeProtocolDriver implements ProtocolDriverInterface
{
    private ServerDefinition $server;

    private ToolRuntime $tools;

    private ResourceProtocolHandler $resources;

    private PromptProtocolHandler $prompts;

    private SubscriptionProtocolHandler $subscriptions;

    public function __construct(
        ServerDefinition $server,
        ToolRuntime $tools,
        ?ResourceRuntime $resources = null,
        ?PromptRuntime $prompts = null,
        ?SubscriptionRuntime $subscriptions = null,
        ?CancellationCoordinatorInterface $cancellations = null,
    ) {
        $this->server = $server;
        $this->tools = $tools;
        $this->resources = new ResourceProtocolHandler(
            $server,
            $resources ?? new ResourceRuntime(new FactoryResourceResolver()),
        );
        $this->prompts = new PromptProtocolHandler($server, $prompts ?? new PromptRuntime(new FactoryPromptResolver()));
        $cancellations ??= new ProcessCancellationCoordinator();
        $this->subscriptions = new SubscriptionProtocolHandler(
            $server,
            $subscriptions ?? new SubscriptionRuntime(new FactorySubscriptionResolver(), $cancellations),
            $cancellations,
        );
    }

    public function dispatch(ProtocolRequest $request, ExecutionContext $context): ProtocolDispatchResult
    {
        try {
            $result = $this->route($request, $context);
        } catch (ProtocolException $exception) {
            if ($request->isNotification()) {
                return new AcceptedResult();
            }

            return new JsonResult($exception->error->toEnvelope(), $exception->error->httpStatus);
        }

        if ($request->isNotification()) {
            return new AcceptedResult();
        }

        if ($result instanceof ProtocolDispatchResult) {
            return $result;
        }

        return new JsonResult([
            'jsonrpc' => '2.0',
            'id' => $request->id?->value(),
            'result' => $result,
        ]);
    }

    /**
     * @return array<string, mixed>|ProtocolDispatchResult
     */
    private function route(ProtocolRequest $request, ExecutionContext $context): array|ProtocolDispatchResult
    {
        if ($request->protocolVersion !== Version::PROTOCOL) {
            throw new ProtocolException(ProtocolErrors::unsupportedVersion($request->id, $request->protocolVersion));
        }

        $result = match ($request->method) {
            'server/discover' => $this->discover($context),
            'tools/list' => $this->listTools($context),
            'tools/call' => $this->callTool($request, $context),
            default => $this->resources->dispatch($request, $context) ?? $this->prompts->dispatch(
                $request,
                $context,
            ) ?? $this->subscriptions->dispatch($request, $context),
        };
        if ($result === null) {
            throw new ProtocolException(ProtocolErrors::methodNotFound($this->requestId($request), $request->method));
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function discover(ExecutionContext $context): array
    {
        $visibleTools = $this->tools->list($this->server, $context);
        $capabilities = [];
        if ($visibleTools !== []) {
            $capabilities['tools'] = ['listChanged' => false];
        }
        if ($this->resources->visible($context)) {
            $capabilities['resources'] = ['listChanged' => false, 'subscribe' => false];
        }
        if ($this->prompts->visiblePrompts($context)) {
            $capabilities['prompts'] = ['listChanged' => false];
        }
        if ($this->prompts->visibleCompletions($context)) {
            $capabilities['completions'] = [];
        }
        $capabilities = SubscriptionCapabilities::apply($capabilities, $this->server, $context);
        $result = [
            '_meta' => $this->serverMeta(),
            'resultType' => 'complete',
            'cacheScope' => 'private',
            'ttlMs' => 0,
            'supportedVersions' => [Version::PROTOCOL],
            'capabilities' => $capabilities,
        ];

        if ($this->server->options->instructions !== null) {
            $result['instructions'] = $this->server->options->instructions;
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function listTools(ExecutionContext $context): array
    {
        return [
            '_meta' => $this->serverMeta(),
            'resultType' => 'complete',
            'cacheScope' => 'private',
            'ttlMs' => 0,
            'tools' => array_map(
                static fn(\Tinywan\Mcp\Tool\ToolDefinition $tool): array => $tool->toArray(),
                $this->tools->list($this->server, $context),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function callTool(ProtocolRequest $request, ExecutionContext $context): array
    {
        $name = $this->toolName($request);
        if ($name === null || $name === '') {
            throw new ProtocolException(ProtocolErrors::invalidParams($request->id, 'Tool name is required.'));
        }

        $arguments = $this->toolArguments($request);
        if ($arguments === null) {
            throw new ProtocolException(ProtocolErrors::invalidParams(
                $request->id,
                'Tool arguments must be an object.',
            ));
        }

        return $this->tools->call($this->server, $name, $arguments, $context, $request->id)->toArray();
    }

    /**
     * @return array<string, array{name: string, version: string, title?: string}>
     */
    private function serverMeta(): array
    {
        return ['io.modelcontextprotocol/serverInfo' => $this->server->identity->toArray()];
    }

    private function requestId(ProtocolRequest $request): RequestId
    {
        if ($request->id === null) {
            throw new ProtocolException(ProtocolErrors::invalidRequest());
        }

        return $request->id;
    }

    private function toolName(ProtocolRequest $request): ?string
    {
        if (!array_key_exists('name', $request->params) || !is_string($request->params['name'])) {
            return null;
        }

        return $request->params['name'];
    }

    /**
     * @return null|array<array-key, mixed>
     */
    private function toolArguments(ProtocolRequest $request): ?array
    {
        if (!array_key_exists('arguments', $request->params)) {
            return [];
        }

        return is_array($request->params['arguments']) ? $request->params['arguments'] : null;
    }
}
