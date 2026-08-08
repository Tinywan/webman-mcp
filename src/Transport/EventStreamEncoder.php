<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Transport;

final readonly class EventStreamEncoder
{
    /** @param list<array<string, mixed>> $events */
    public function encode(array $events): string
    {
        $stream = '';
        foreach ($events as $event) {
            $stream .= 'event: message' . "\n";
            $stream .=
                'data: '
                . json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                . "\n\n";
        }

        return $stream;
    }
}
