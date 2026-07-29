<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Transport;

use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\ProtocolRequest;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Tool\Schema\HeaderAnnotation;
use Tinywan\Mcp\Tool\Schema\ToolSchemaValidator;

final readonly class HeaderMirrorValidator
{
    public function __construct(
        private ToolSchemaValidator $schemas = new ToolSchemaValidator(),
        private HeaderValueDecoder $decoder = new HeaderValueDecoder(),
    ) {}

    public function validate(HttpRequestContext $http, ProtocolRequest $request, ServerDefinition $server): void
    {
        $this->match($http, 'mcp-protocol-version', $request->protocolVersion, $request);
        $this->match($http, 'mcp-method', $request->method, $request);

        if ($request->method !== 'tools/call') {
            return;
        }

        $name = $this->stringParam($request, 'name');
        if ($name === null) {
            throw new ProtocolException(ProtocolErrors::headerMismatch($request->id, 'Body Tool name is missing.'));
        }

        $this->match($http, 'mcp-name', $name, $request);
        $tool = $server->tool($name);
        if ($tool === null) {
            return;
        }

        $arguments = $this->arrayParam($request, 'arguments');
        foreach ($this->schemas->headerAnnotations($tool->definition) as $annotation) {
            $this->matchAnnotation($http, $request, $arguments, $annotation);
        }
    }

    private function match(HttpRequestContext $http, string $header, string $expected, ProtocolRequest $request): void
    {
        $actual = $http->header($header);
        if ($actual === null || $this->decoder->decode($actual) !== $expected) {
            throw new ProtocolException(ProtocolErrors::headerMismatch($request->id, "{$header} does not match Body."));
        }
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    private function matchAnnotation(
        HttpRequestContext $http,
        ProtocolRequest $request,
        array $arguments,
        HeaderAnnotation $annotation,
    ): void {
        $header = strtolower($annotation->headerName());
        $actual = $http->header($header);
        if ($actual === null) {
            return;
        }

        $expected = $this->pathValue($arguments, $annotation->propertyPath);
        if ($expected === null || $this->decoder->decode($actual) !== $this->scalarString($expected)) {
            throw new ProtocolException(ProtocolErrors::headerMismatch(
                $request->id,
                "{$header} does not match Body arguments.",
            ));
        }
    }

    private function stringParam(ProtocolRequest $request, string $name): ?string
    {
        return array_key_exists($name, $request->params) && is_string($request->params[$name])
            ? $request->params[$name]
            : null;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function arrayParam(ProtocolRequest $request, string $name): array
    {
        return (
            array_key_exists($name, $request->params) && is_array($request->params[$name])
                ? $request->params[$name]
                : []
        );
    }

    /**
     * @param array<array-key, mixed> $value
     * @param list<string> $path
     */
    private function pathValue(array $value, array $path): string|int|bool|null
    {
        if ($path === []) {
            return null;
        }

        $segment = $path[0];
        if (!array_key_exists($segment, $value)) {
            return null;
        }

        if (count($path) === 1) {
            return is_string($value[$segment]) || is_int($value[$segment]) || is_bool($value[$segment])
                ? $value[$segment]
                : null;
        }

        return is_array($value[$segment]) ? $this->pathValue($value[$segment], array_slice($path, offset: 1)) : null;
    }

    private function scalarString(string|int|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
