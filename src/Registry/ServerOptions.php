<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use InvalidArgumentException;

final readonly class ServerOptions
{
    public function __construct(
        public OriginPolicy $originPolicy = new OriginPolicy(),
        public ?string $instructions = null,
        public int $bodyLimit = 2_097_152,
    ) {
        if ($bodyLimit < 1) {
            throw new InvalidArgumentException('The Server body limit must be positive.');
        }
    }
}
