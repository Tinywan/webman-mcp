<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Resource;

use InvalidArgumentException;

final readonly class ResourceDefinition
{
    public function __construct(
        public string $uri,
        public string $name,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?int $size = null,
        public ?string $title = null,
        public ?ResourceAnnotations $annotations = null,
    ) {
        if (!self::validUri($uri)) {
            throw new InvalidArgumentException('A Resource URI must be an absolute URI.');
        }
        if ($name === '') {
            throw new InvalidArgumentException('A Resource name cannot be empty.');
        }
        if ($size !== null && $size < 0) {
            throw new InvalidArgumentException('A Resource size cannot be negative.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['uri' => $this->uri, 'name' => $this->name];
        foreach (['description', 'mimeType', 'size', 'title'] as $property) {
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

    public static function validUri(string $uri): bool
    {
        return preg_match('/^[a-z][a-z0-9+.-]*:/i', $uri) === 1 && preg_match('/\s/', $uri) !== 1;
    }
}
