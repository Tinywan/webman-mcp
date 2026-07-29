<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol\Error;

use RuntimeException;

final class ProtocolException extends RuntimeException
{
    public function __construct(
        public readonly ProtocolError $error,
    ) {
        parent::__construct($error->message, $error->code);
    }
}
