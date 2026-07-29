<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Transport;

final readonly class HttpRequestContext
{
    public string $method;

    public string $path;

    /** @var array<string, string> */
    public array $headers;

    public string $body;

    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(string $method, string $path, array $headers, string $body)
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower($name)] = is_array($value) ? implode(', ', $value) : $value;
        }

        $this->method = strtoupper($method);
        $this->path = $path;
        $this->headers = $normalized;
        $this->body = $body;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
