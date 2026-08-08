<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol;

use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Resource\ResourceDefinition;
use Tinywan\Mcp\Resource\ResourceRuntime;
use Tinywan\Mcp\Runtime\ExecutionContext;

final readonly class ResourceProtocolHandler
{
    public function __construct(
        private ServerDefinition $server,
        private ResourceRuntime $runtime,
    ) {}

    /**
     * @return null|array<string, mixed>
     */
    public function dispatch(ProtocolRequest $request, ExecutionContext $context): ?array
    {
        return match ($request->method) {
            'resources/list' => $this->listResources($request, $context),
            'resources/read' => $this->readResource($request, $context),
            'resources/templates/list' => $this->listTemplates($request, $context),
            default => null,
        };
    }

    public function visible(ExecutionContext $context): bool
    {
        return $this->runtime->hasVisible($this->server, $context);
    }

    /** @return array<string, mixed> */
    private function listResources(ProtocolRequest $request, ExecutionContext $context): array
    {
        return $this->withMeta($this->runtime->listResources(
            $this->server,
            $context,
            $this->cursor($request),
            $request->id,
        ));
    }

    /** @return array<string, mixed> */
    private function listTemplates(ProtocolRequest $request, ExecutionContext $context): array
    {
        return $this->withMeta($this->runtime->listTemplates(
            $this->server,
            $context,
            $this->cursor($request),
            $request->id,
        ));
    }

    /** @return array<string, mixed> */
    private function readResource(ProtocolRequest $request, ExecutionContext $context): array
    {
        $uri = $this->stringParam($request, 'uri');
        if ($uri === null || !ResourceDefinition::validUri($uri)) {
            throw new ProtocolException(ProtocolErrors::invalidParams(
                $request->id,
                'A valid Resource URI is required.',
            ));
        }

        return $this->withMeta($this->runtime->read($this->server, $context, $uri, $request->id));
    }

    private function cursor(ProtocolRequest $request): ?string
    {
        if (!array_key_exists('cursor', $request->params)) {
            return null;
        }
        if (!is_string($request->params['cursor'])) {
            throw new ProtocolException(ProtocolErrors::invalidParams($request->id, 'Cursor must be a string.'));
        }

        return $request->params['cursor'];
    }

    private function stringParam(ProtocolRequest $request, string $name): ?string
    {
        return array_key_exists($name, $request->params) && is_string($request->params[$name])
            ? $request->params[$name]
            : null;
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function withMeta(array $result): array
    {
        return [
            '_meta' => ['io.modelcontextprotocol/serverInfo' => $this->server->identity->toArray()],
            ...$result,
        ];
    }
}
