<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Resource;

use InvalidArgumentException;

final readonly class ResourceTemplateDefinition
{
    public function __construct(
        public string $uriTemplate,
        public string $name,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?string $title = null,
        public ?ResourceAnnotations $annotations = null,
    ) {
        if (!self::validTemplate($uriTemplate)) {
            throw new InvalidArgumentException('A Resource URI template must be a valid absolute URI template.');
        }
        if ($name === '') {
            throw new InvalidArgumentException('A Resource Template name cannot be empty.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['uriTemplate' => $this->uriTemplate, 'name' => $this->name];
        foreach (['description', 'mimeType', 'title'] as $property) {
            if ($this->{$property} === null) {
                continue;
            }
            $data[$property] = $this->{$property};
        }
        if ($this->annotations !== null) {
            $data['annotations'] = $this->annotations->toArray();
        }

        return $data;
    }

    public function matches(string $uri): bool
    {
        $quoted = preg_quote($this->uriTemplate, delimiter: '~');
        $pattern = preg_replace('/\\\{[^}]+\\\}/', replacement: '[^/?#]+', subject: $quoted);

        return is_string($pattern) && preg_match("~^{$pattern}$~u", $uri) === 1;
    }

    public static function validTemplate(string $template): bool
    {
        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $template) !== 1 || preg_match('/\s/', $template) === 1) {
            return false;
        }

        $matches = null;
        preg_match_all('/\{[^{}]+\}/', $template, $matches);
        $withoutExpressions = preg_replace('/\{[^{}]+\}/', replacement: '', subject: $template);

        return (
            is_string($withoutExpressions)
            && !str_contains($withoutExpressions, '{')
            && !str_contains($withoutExpressions, '}')
        );
    }
}
