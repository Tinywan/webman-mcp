<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol\Result;

final readonly class JsonResult implements ProtocolDispatchResult
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private array $data,
        private int $httpStatus = 200,
    ) {}

    public function status(): int
    {
        return $this->httpStatus;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return ['Content-Type' => 'application/json'];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->data;
    }
}
