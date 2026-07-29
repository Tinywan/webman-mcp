<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

final readonly class OriginPolicy
{
    /**
     * @param list<string> $allowedOrigins
     */
    public function __construct(
        private array $allowedOrigins = [],
    ) {}

    public function allows(?string $origin): bool
    {
        if ($origin === null || $origin === '') {
            return true;
        }

        return (
            in_array('*', $this->allowedOrigins, strict: true) || in_array($origin, $this->allowedOrigins, strict: true)
        );
    }

    /**
     * @return list<string>
     */
    public function origins(): array
    {
        return $this->allowedOrigins;
    }
}
