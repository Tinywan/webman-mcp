<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tool\Schema;

final class HeaderAnnotationCollector
{
    private const ROOT = 0;

    private const PROPERTY = 1;

    private const UNREACHABLE = 2;

    /**
     * @param array<string, mixed> $schema
     * @return list<HeaderAnnotation>
     */
    public function collect(array $schema): array
    {
        $annotations = [];
        $seen = [];
        $this->walk($schema, [], self::ROOT, $seen, $annotations);

        return $annotations;
    }

    /**
     * @param array<array-key, mixed> $node
     * @param list<string> $path
     * @param array<string, true> $seen
     * @param list<HeaderAnnotation> $annotations
     */
    private function walk(array $node, array $path, int $location, array &$seen, array &$annotations): void
    {
        if (array_key_exists('x-mcp-header', $node)) {
            $this->add($node, $path, $location, $seen, $annotations);
        }

        foreach (array_keys($node) as $keyword) {
            $this->walkKeyword($keyword, $node[$keyword], $path, $location, $seen, $annotations);
        }
    }

    /**
     * @param array<array-key, mixed> $node
     * @param list<string> $path
     * @param array<string, true> $seen
     * @param list<HeaderAnnotation> $annotations
     */
    private function add(array $node, array $path, int $location, array &$seen, array &$annotations): void
    {
        if ($location !== self::PROPERTY) {
            throw new SchemaValidationException('x-mcp-header must annotate a statically reachable property.');
        }

        $name = $this->headerName($node['x-mcp-header']);
        if (preg_match('/^[!#$%&\'*+\-.^_`|~0-9A-Za-z]+$/D', $name) !== 1) {
            throw new SchemaValidationException("Invalid x-mcp-header token '{$name}'.");
        }

        $type = $this->headerType($node['type'] ?? null, $name);
        if (!in_array($type, ['string', 'integer', 'boolean'], strict: true)) {
            throw new SchemaValidationException("x-mcp-header '{$name}' must annotate string, integer, or boolean.");
        }

        $normalized = strtolower($name);
        if (array_key_exists($normalized, $seen)) {
            throw new SchemaValidationException("Duplicate x-mcp-header '{$name}'.");
        }

        $seen[$normalized] = true;
        $annotations[] = new HeaderAnnotation($name, $path, $type);
    }

    /**
     * @param list<string> $path
     * @param array<string, true> $seen
     * @param list<HeaderAnnotation> $annotations
     */
    private function walkKeyword(
        int|string $keyword,
        mixed $value,
        array $path,
        int $location,
        array &$seen,
        array &$annotations,
    ): void {
        if (!is_array($value)) {
            return;
        }

        if ($keyword === 'properties') {
            foreach (array_keys($value) as $property) {
                $this->walkProperty($property, $value[$property], $path, $location, $seen, $annotations);
            }

            return;
        }

        $children = array_is_list($value) ? $value : [$value];
        foreach (array_keys($children) as $childKey) {
            $this->walkChild($children[$childKey], $path, $seen, $annotations);
        }
    }

    /**
     * @param list<string> $path
     * @param array<string, true> $seen
     * @param list<HeaderAnnotation> $annotations
     */
    private function walkProperty(
        int|string $property,
        mixed $schema,
        array $path,
        int $location,
        array &$seen,
        array &$annotations,
    ): void {
        if (!is_string($property) || !is_array($schema)) {
            return;
        }

        $next = $location === self::UNREACHABLE ? self::UNREACHABLE : self::PROPERTY;
        $this->walk($schema, [...$path, $property], $next, $seen, $annotations);
    }

    /**
     * @param list<string> $path
     * @param array<string, true> $seen
     * @param list<HeaderAnnotation> $annotations
     */
    private function walkChild(mixed $child, array $path, array &$seen, array &$annotations): void
    {
        if (is_array($child)) {
            $this->walk($child, $path, self::UNREACHABLE, $seen, $annotations);
        }
    }

    private function headerName(mixed $name): string
    {
        if (!is_string($name) || $name === '') {
            throw new SchemaValidationException('x-mcp-header must annotate a statically reachable property.');
        }

        return $name;
    }

    private function headerType(mixed $type, string $name): string
    {
        if (!is_string($type)) {
            throw new SchemaValidationException("x-mcp-header '{$name}' must annotate string, integer, or boolean.");
        }

        return $type;
    }
}
