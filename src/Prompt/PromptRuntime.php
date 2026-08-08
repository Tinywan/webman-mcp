<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Prompt;

use JsonException;
use Throwable;
use Tinywan\Mcp\Contracts\PromptResolverInterface;
use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\RequestId;
use Tinywan\Mcp\Registry\RegisteredPrompt;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Runtime\ExecutionContext;

final readonly class PromptRuntime
{
    public function __construct(
        private PromptResolverInterface $resolver,
    ) {}

    /** @return array<string, mixed> */
    public function list(
        ServerDefinition $server,
        ExecutionContext $context,
        ?string $cursor,
        ?RequestId $requestId,
    ): array {
        $visible = array_values(array_filter($server->prompts(), static fn(RegisteredPrompt $prompt): bool => $server->features->prompts->authorizer->canList(
            $context->principal,
            $prompt->definition,
        )));
        $fingerprint = hash('sha256', implode("\n", array_map(
            static fn(RegisteredPrompt $prompt): string => $prompt->definition->name,
            $visible,
        )));
        $offset = $cursor === null ? 0 : $this->decodeCursor($cursor, $fingerprint, $requestId);
        $items = array_slice($visible, $offset, $server->options->prompts->pageSize);
        $payload = [
            'resultType' => 'complete',
            'cacheScope' => 'private',
            'ttlMs' => 0,
            'prompts' => array_map(
                static fn(RegisteredPrompt $prompt): array => $prompt->definition->toArray(),
                $items,
            ),
        ];
        $nextOffset = $offset + count($items);
        if ($nextOffset < count($visible)) {
            $payload['nextCursor'] = $this->encodeCursor($nextOffset, $fingerprint);
        }

        return $payload;
    }

    /** @param array<string, string> $arguments @return array<string, mixed> */
    public function get(
        ServerDefinition $server,
        ExecutionContext $context,
        string $name,
        array $arguments,
        ?RequestId $requestId,
    ): array {
        $registered = $server->prompt($name);
        if (
            $registered === null
            || !$server->features->prompts->authorizer->canGet($context->principal, $registered->definition)
        ) {
            throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Unknown or unauthorized Prompt.'));
        }
        $this->validateArguments($registered->definition, $arguments, $requestId);
        if ($context->deadline->isExpired()) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $context->traceId));
        }

        try {
            return $this->resolver
                ->resolvePrompt($registered)
                ->render(new PromptCall($name, $arguments), $context)
                ->toArray();
        } catch (ProtocolException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $context->traceId));
        }
    }

    /** @return array<string, mixed> */
    public function complete(
        ServerDefinition $server,
        ExecutionContext $context,
        CompletionCall $call,
        ?RequestId $requestId,
    ): array {
        $registered = $server->completion($call->reference->key());
        if (
            $registered === null
            || !$server->features->prompts->authorizer->canComplete($context->principal, $call->reference)
        ) {
            throw new ProtocolException(ProtocolErrors::invalidParams(
                $requestId,
                'Unknown or unauthorized completion.',
            ));
        }
        if ($context->deadline->isExpired()) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $context->traceId));
        }

        try {
            $result = $this->resolver->resolveCompletion($registered)->complete($call, $context);
        } catch (Throwable) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $context->traceId));
        }
        $unique = array_values(array_unique($result->values));
        $values = array_slice($unique, offset: 0, length: $server->options->prompts->completionCount);
        $total = $result->total ?? count($unique);

        return [
            'resultType' => 'complete',
            'completion' => [
                'values' => $values,
                'total' => $total,
                'hasMore' => $result->hasMore ?? $total > count($values),
            ],
        ];
    }

    public function hasVisiblePrompts(ServerDefinition $server, ExecutionContext $context): bool
    {
        foreach ($server->prompts() as $prompt) {
            if ($server->features->prompts->authorizer->canList($context->principal, $prompt->definition)) {
                return true;
            }
        }

        return false;
    }

    public function hasVisibleCompletions(ServerDefinition $server, ExecutionContext $context): bool
    {
        foreach ($server->completions() as $completion) {
            if ($server->features->prompts->authorizer->canComplete($context->principal, $completion->reference)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, string> $arguments */
    private function validateArguments(PromptDefinition $definition, array $arguments, ?RequestId $requestId): void
    {
        foreach ($arguments as $name => $_value) {
            if ($definition->argument($name) === null) {
                throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Invalid Prompt arguments.'));
            }
        }
        foreach ($definition->arguments as $argument) {
            if ($argument->required && !array_key_exists($argument->name, $arguments)) {
                throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Invalid Prompt arguments.'));
            }
        }
    }

    private function encodeCursor(int $offset, string $fingerprint): string
    {
        try {
            $json = json_encode(['offset' => $offset, 'fingerprint' => $fingerprint], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '';
        }

        return rtrim(strtr(base64_encode($json), from: '+/', to: '-_'), characters: '=');
    }

    private function decodeCursor(string $cursor, string $fingerprint, ?RequestId $requestId): int
    {
        $padding = (4 - (strlen($cursor) % 4)) % 4;
        $decoded = base64_decode(strtr($cursor . str_repeat('=', $padding), from: '-_', to: '+/'), strict: true);
        try {
            /** @var mixed $data */
            $data = $decoded === false ? null : json_decode($decoded, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $data = null;
        }
        if (
            !is_array($data)
            || !is_int($data['offset'] ?? null)
            || $data['offset'] < 0
            || !is_string($data['fingerprint'] ?? null)
            || !hash_equals($fingerprint, $data['fingerprint'])
        ) {
            throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Invalid cursor.'));
        }

        return $data['offset'];
    }
}
