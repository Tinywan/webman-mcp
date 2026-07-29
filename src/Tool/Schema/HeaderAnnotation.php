<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tool\Schema;

final readonly class HeaderAnnotation
{
    /**
     * @param list<string> $propertyPath
     */
    public function __construct(
        public string $name,
        public array $propertyPath,
        public string $type,
    ) {}

    public function headerName(): string
    {
        return "Mcp-Param-{$this->name}";
    }
}
