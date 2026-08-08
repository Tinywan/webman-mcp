<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol;

use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;

final readonly class ProtocolParams
{
    public function __construct(
        private ProtocolRequest $request,
    ) {}

    public function requiredString(string $name): string
    {
        $value = $this->optionalString($name);
        if ($value === null || $value === '') {
            throw new ProtocolException(ProtocolErrors::invalidParams($this->request->id, "{$name} is required."));
        }

        return $value;
    }

    public function optionalString(string $name): ?string
    {
        if (!array_key_exists($name, $this->request->params)) {
            return null;
        }
        if (!is_string($this->request->params[$name])) {
            throw new ProtocolException(ProtocolErrors::invalidParams($this->request->id, "{$name} must be a string."));
        }

        return $this->request->params[$name];
    }

    /** @return array<string, mixed> */
    public function object(string $name): array
    {
        /** @var mixed $value */
        $value = $this->request->params[$name] ?? null;
        if (!is_array($value) || array_is_list($value)) {
            throw new ProtocolException(ProtocolErrors::invalidParams(
                $this->request->id,
                "{$name} must be an object.",
            ));
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /** @param array<string, mixed> $object */
    public function objectString(array $object, string $name): string
    {
        if (!is_string($object[$name] ?? null) || $object[$name] === '') {
            throw new ProtocolException(ProtocolErrors::invalidParams($this->request->id, "{$name} is required."));
        }

        return $object[$name];
    }

    /** @return array<string, string> */
    public function stringMap(mixed $value): array
    {
        if (!is_array($value) || $value !== [] && array_is_list($value)) {
            throw new ProtocolException(ProtocolErrors::invalidParams(
                $this->request->id,
                'Arguments must be an object of strings.',
            ));
        }
        foreach (array_keys($value) as $name) {
            if (!is_string($name) || !is_string($value[$name])) {
                throw new ProtocolException(ProtocolErrors::invalidParams(
                    $this->request->id,
                    'Arguments must be an object of strings.',
                ));
            }
        }

        /** @var array<string, string> $value */
        return $value;
    }

    /** @return array<string, string> */
    public function completionContext(): array
    {
        if (!array_key_exists('context', $this->request->params)) {
            return [];
        }
        $context = $this->object('context');

        return $this->stringMap($context['arguments'] ?? []);
    }
}
