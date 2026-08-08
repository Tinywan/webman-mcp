<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Resource;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ResourceAnnotations
{
    /**
     * @param list<string> $audience
     */
    public function __construct(
        public array $audience = [],
        public ?float $priority = null,
        public ?string $lastModified = null,
    ) {
        foreach ($audience as $role) {
            if (!in_array($role, ['user', 'assistant'], strict: true)) {
                throw new InvalidArgumentException('A Resource audience role must be user or assistant.');
            }
        }
        if ($priority !== null && ($priority < 0.0 || $priority > 1.0)) {
            throw new InvalidArgumentException('A Resource priority must be between zero and one.');
        }
        if ($lastModified !== null && DateTimeImmutable::createFromFormat(DATE_ATOM, $lastModified) === false) {
            throw new InvalidArgumentException('A Resource last-modified value must be ISO 8601.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [];
        if ($this->audience !== []) {
            $data['audience'] = $this->audience;
        }
        if ($this->priority !== null) {
            $data['priority'] = $this->priority;
        }
        if ($this->lastModified !== null) {
            $data['lastModified'] = $this->lastModified;
        }

        return $data;
    }
}
