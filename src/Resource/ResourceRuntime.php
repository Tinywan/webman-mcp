<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Resource;

use JsonException;
use Throwable;
use Tinywan\Mcp\Contracts\ResourceResolverInterface;
use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\RequestId;
use Tinywan\Mcp\Registry\RegisteredResource;
use Tinywan\Mcp\Registry\RegisteredResourceTemplate;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Runtime\ExecutionContext;

final readonly class ResourceRuntime
{
    public function __construct(
        private ResourceResolverInterface $resolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function listResources(
        ServerDefinition $server,
        ExecutionContext $context,
        ?string $cursor,
        ?RequestId $requestId,
    ): array {
        $visible = array_values(array_filter($server->resources(), static fn(RegisteredResource $resource): bool => $server->features->resources->authorizer->canListResource(
            $context->principal,
            $resource->definition,
        )));

        return $this->page($visible, $cursor, $server->options->resources->pageSize, 'resources', $requestId);
    }

    /**
     * @return array<string, mixed>
     */
    public function listTemplates(
        ServerDefinition $server,
        ExecutionContext $context,
        ?string $cursor,
        ?RequestId $requestId,
    ): array {
        $visible = array_values(array_filter($server->resourceTemplates(), static fn(RegisteredResourceTemplate $template): bool => $server->features->resources->authorizer->canListTemplate(
            $context->principal,
            $template->definition,
        )));

        return $this->page($visible, $cursor, $server->options->resources->pageSize, 'resourceTemplates', $requestId);
    }

    /**
     * @return array<string, mixed>
     */
    public function read(ServerDefinition $server, ExecutionContext $context, string $uri, ?RequestId $requestId): array
    {
        $registered = $server->resource($uri);
        if (
            $registered === null
            || !$server->features->resources->authorizer->canReadResource($context->principal, $registered->definition)
        ) {
            $registered = $this->matchingTemplate($server, $context, $uri);
        }

        if ($registered === null) {
            throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Unknown or unauthorized resource.'));
        }

        if ($context->deadline->isExpired()) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $context->traceId));
        }

        try {
            $result = $this->resolver->resolve($registered)->read(new ResourceReadCall($uri), $context);
            $payload = $result->toArray();
            $encoded = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (ProtocolException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $context->traceId));
        }

        if (strlen($encoded) > $server->options->resources->responseBytes) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $context->traceId));
        }

        return $payload;
    }

    public function hasVisible(ServerDefinition $server, ExecutionContext $context): bool
    {
        foreach ($server->resources() as $resource) {
            if ($server->features->resources->authorizer->canListResource($context->principal, $resource->definition)) {
                return true;
            }
        }
        foreach ($server->resourceTemplates() as $template) {
            if ($server->features->resources->authorizer->canListTemplate($context->principal, $template->definition)) {
                return true;
            }
        }

        return false;
    }

    private function matchingTemplate(
        ServerDefinition $server,
        ExecutionContext $context,
        string $uri,
    ): ?RegisteredResourceTemplate {
        foreach ($server->resourceTemplates() as $template) {
            if (
                $template->definition->matches($uri)
                && $server->features->resources->authorizer->canReadTemplate(
                    $context->principal,
                    $template->definition,
                    $uri,
                )
            ) {
                return $template;
            }
        }

        return null;
    }

    /**
     * @param list<RegisteredResource|RegisteredResourceTemplate> $registered
     * @return array<string, mixed>
     */
    private function page(array $registered, ?string $cursor, int $size, string $key, ?RequestId $requestId): array
    {
        $fingerprint = hash('sha256', implode("\n", array_map(
            static fn(RegisteredResource|RegisteredResourceTemplate $item): string => $item
                instanceof RegisteredResource
                    ? $item->definition->uri
                    : $item->definition->uriTemplate,
            $registered,
        )));
        $offset = $cursor === null ? 0 : $this->decodeCursor($cursor, $fingerprint, $requestId);
        $items = array_slice($registered, $offset, $size);
        $nextOffset = $offset + count($items);
        $payload = [
            'resultType' => 'complete',
            'cacheScope' => 'private',
            'ttlMs' => 0,
            $key => array_map(
                static fn(RegisteredResource|RegisteredResourceTemplate $item): array => $item->definition->toArray(),
                $items,
            ),
        ];
        if ($nextOffset < count($registered)) {
            $payload['nextCursor'] = $this->encodeCursor($nextOffset, $fingerprint);
        }

        return $payload;
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
        if ($decoded === false) {
            throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Invalid cursor.'));
        }

        try {
            /** @var mixed $data */
            $data = json_decode($decoded, associative: true, depth: 8, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Invalid cursor.'));
        }
        if (
            !is_array($data)
            || !array_key_exists('offset', $data)
            || !is_int($data['offset'])
            || $data['offset'] < 0
            || !array_key_exists('fingerprint', $data)
            || !is_string($data['fingerprint'])
            || !hash_equals($fingerprint, $data['fingerprint'])
        ) {
            throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Invalid cursor.'));
        }

        return $data['offset'];
    }
}
