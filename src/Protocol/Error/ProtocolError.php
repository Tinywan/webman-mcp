<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol\Error;

use Tinywan\Mcp\Protocol\RequestId;

final readonly class ProtocolError
{
    /**
     * @param null|array<string, mixed> $data
     */
    public function __construct(
        public int $code,
        public string $message,
        public int $httpStatus,
        public ?RequestId $id = null,
        public ?array $data = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toEnvelope(): array
    {
        $error = [
            'code' => $this->code,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $error['data'] = $this->data;
        }

        $envelope = [
            'jsonrpc' => '2.0',
            'error' => $error,
        ];

        if ($this->id !== null) {
            $envelope['id'] = $this->id->value();
        }

        return $envelope;
    }
}
