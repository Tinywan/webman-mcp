<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol;

use Tinywan\Mcp\Contracts\ProtocolDriverInterface;
use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\Result\AcceptedResult;
use Tinywan\Mcp\Protocol\Result\JsonResult;
use Tinywan\Mcp\Protocol\Result\ProtocolDispatchResult;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Tool\ToolRuntime;
use Tinywan\Mcp\Version;

final readonly class NativeProtocolDriver implements ProtocolDriverInterface
{
    public function __construct(
        private ServerDefinition $server,
        private ToolRuntime $tools,
    ) {}

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

        return new JsonResult([
            'jsonrpc' => '2.0',
            'id' => $request->id?->value(),
            'result' => $result,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function route(ProtocolRequest $request, ExecutionContext $context): array
    {
        if ($request->protocolVersion !== Version::PROTOCOL) {
            throw new ProtocolException(ProtocolErrors::unsupportedVersion($request->id, $request->protocolVersion));
        }

        return match ($request->method) {
            'server/discover' => $this->discover($context),
            'tools/list' => $this->listTools($context),
            'tools/call' => $this->callTool($request, $context),
            default => throw new ProtocolException(ProtocolErrors::methodNotFound(
                $this->requestId($request),
                $request->method,
            )),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function discover(ExecutionContext $context): array
    {
        $visibleTools = $this->tools->list($this->server, $context);
        $result = [
            '_meta' => $this->serverMeta(),
            'resultType' => 'complete',
            'cacheScope' => 'private',
            'ttlMs' => 0,
            'supportedVersions' => [Version::PROTOCOL],
            'capabilities' => $visibleTools === [] ? [] : ['tools' => ['listChanged' => false]],
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
