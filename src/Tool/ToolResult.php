<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tool;

use Tinywan\Mcp\Tool\Content\ToolContentInterface;

final readonly class ToolResult
{
    /**
     * @param list<ToolContentInterface> $content
     */
    public function __construct(
        public array $content,
        public mixed $structuredContent = null,
        public bool $hasStructuredContent = false,
        public bool $isError = false,
    ) {}

    /**
     * @param list<ToolContentInterface> $content
     */
    public static function success(array $content, mixed $structuredContent = null): self
    {
        return new self($content, $structuredContent, func_num_args() >= 2);
    }

    /**
     * @param list<ToolContentInterface> $content
     */
    public static function error(array $content, mixed $structuredContent = null): self
    {
        return new self($content, $structuredContent, func_num_args() >= 2, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'resultType' => 'complete',
            'content' => array_map(
                static fn(ToolContentInterface $content): array => $content->toArray(),
                $this->content,
            ),
        ];

        if ($this->hasStructuredContent) {
            $data['structuredContent'] = $this->structuredContent;
        }

        if ($this->isError) {
            $data['isError'] = true;
        }

        return $data;
    }
}
