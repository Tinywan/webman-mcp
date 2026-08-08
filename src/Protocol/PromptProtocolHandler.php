<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol;

use Tinywan\Mcp\Prompt\CompletionCall;
use Tinywan\Mcp\Prompt\CompletionReference;
use Tinywan\Mcp\Prompt\PromptRuntime;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Runtime\ExecutionContext;

final readonly class PromptProtocolHandler
{
    public function __construct(
        private ServerDefinition $server,
        private PromptRuntime $runtime,
    ) {}

    /** @return null|array<string, mixed> */
    public function dispatch(ProtocolRequest $request, ExecutionContext $context): ?array
    {
        return match ($request->method) {
            'prompts/list' => $this->list($request, $context),
            'prompts/get' => $this->get($request, $context),
            'completion/complete' => $this->complete($request, $context),
            default => null,
        };
    }

    public function visiblePrompts(ExecutionContext $context): bool
    {
        return $this->runtime->hasVisiblePrompts($this->server, $context);
    }

    public function visibleCompletions(ExecutionContext $context): bool
    {
        return $this->runtime->hasVisibleCompletions($this->server, $context);
    }

    /** @return array<string, mixed> */
    private function list(ProtocolRequest $request, ExecutionContext $context): array
    {
        $result = $this->runtime->list(
            $this->server,
            $context,
            (new ProtocolParams($request))->optionalString('cursor'),
            $request->id,
        );

        $result['_meta'] = $this->serverMeta();

        return $result;
    }

    /** @return array<string, mixed> */
    private function get(ProtocolRequest $request, ExecutionContext $context): array
    {
        $params = new ProtocolParams($request);
        $name = $params->requiredString('name');
        $arguments = $params->stringMap($request->params['arguments'] ?? []);
        /** @var array<string, mixed> $result */
        $result = $this->runtime->get($this->server, $context, $name, $arguments, $request->id);

        $result['_meta'] = $this->serverMeta();

        return $result;
    }

    /** @return array<string, mixed> */
    private function complete(ProtocolRequest $request, ExecutionContext $context): array
    {
        $params = new ProtocolParams($request);
        $reference = $params->object('ref');
        $argument = $params->object('argument');
        $type = $params->objectString($reference, 'type');
        $identifier = $type === 'ref/prompt'
            ? $params->objectString($reference, 'name')
            : $params->objectString($reference, 'uri');
        $completion = new CompletionCall(
            new CompletionReference($type, $identifier),
            $params->objectString($argument, 'name'),
            $params->objectString($argument, 'value'),
            $params->completionContext(),
        );
        $result = $this->runtime->complete($this->server, $context, $completion, $request->id);

        $result['_meta'] = $this->serverMeta();

        return $result;
    }

    /** @return array<string, array{name: string, version: string, title?: string}> */
    private function serverMeta(): array
    {
        return ['io.modelcontextprotocol/serverInfo' => $this->server->identity->toArray()];
    }
}
