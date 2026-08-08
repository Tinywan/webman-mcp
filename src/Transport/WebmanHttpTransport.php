<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Transport;

use Webman\Http\Request;
use Webman\Http\Response;
use Workerman\Protocols\Http\Chunk;
use Workerman\Timer;

final readonly class WebmanHttpTransport
{
    public function __construct(
        private StreamableHttpTransport $transport,
    ) {}

    public function handle(Request $request): Response
    {
        $response = $this->transport->handle(
            new HttpRequestContext(
                $request->method(),
                $request->path(),
                $this->headers($request->header()),
                $request->rawBody(),
            ),
        );

        if (($response->headers['Content-Type'] ?? null) === 'text/event-stream' && $request->connection !== null) {
            $connection = $request->connection;
            Timer::add(
                0.001,
                static function () use ($connection, $response): void {
                    if ($connection->send(new Chunk($response->body)) === false) {
                        $connection->close();

                        return;
                    }
                    $connection->send(new Chunk(''));
                },
                persistent: false,
            );

            return new Response($response->status, [...$response->headers, 'Transfer-Encoding' => 'chunked']);
        }

        return new Response($response->status, $response->headers, $response->body);
    }

    /**
     * @return array<string, string|list<string>>
     */
    private function headers(mixed $headers): array
    {
        if (!is_array($headers)) {
            return [];
        }

        $normalized = [];
        foreach (array_keys($headers) as $name) {
            if (!is_string($name)) {
                continue;
            }

            $value = $this->headerValue($headers[$name]);
            if ($value === null) {
                continue;
            }

            $normalized[$name] = $value;
        }

        return $normalized;
    }

    /**
     * @phpstan-assert-if-true list<string> $value
     */
    private function isStringList(mixed $value): bool
    {
        if (!is_array($value) || !array_is_list($value)) {
            return false;
        }

        foreach (array_keys($value) as $key) {
            if (!is_string($value[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return null|string|list<string>
     */
    private function headerValue(mixed $value): string|array|null
    {
        if (is_string($value)) {
            return $value;
        }

        return $this->isStringList($value) ? $value : null;
    }
}
