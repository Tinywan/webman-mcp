<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Transport;

use RuntimeException;

final class HttpTransportException extends RuntimeException
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly array $headers = [],
    ) {
        parent::__construct($message);
    }
}
