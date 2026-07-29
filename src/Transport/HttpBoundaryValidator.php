<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Transport;

use Tinywan\Mcp\Registry\ServerDefinition;

final class HttpBoundaryValidator
{
    public function validate(HttpRequestContext $request, ServerDefinition $server): void
    {
        if ($request->method !== 'POST') {
            throw new HttpTransportException(405, 'Method Not Allowed', ['Allow' => 'POST']);
        }

        $this->validateMediaTypes($request);
        $this->validateBodySize($request, $server);

        if (!$server->options->originPolicy->allows($request->header('origin'))) {
            throw new HttpTransportException(403, 'Origin is not allowed.');
        }
    }

    private function validateMediaTypes(HttpRequestContext $request): void
    {
        $contentType = $this->mediaType($request->header('content-type'));
        if ($contentType !== 'application/json') {
            throw new HttpTransportException(415, 'Content-Type must be application/json.');
        }

        $accepted = $this->acceptedTypes($request->header('accept'));
        if (
            !in_array('application/json', $accepted, strict: true)
            || !in_array('text/event-stream', $accepted, strict: true)
        ) {
            throw new HttpTransportException(406, 'Accept must include application/json and text/event-stream.');
        }
    }

    private function validateBodySize(HttpRequestContext $request, ServerDefinition $server): void
    {
        $declared = $request->header('content-length');
        if ($declared !== null && (!ctype_digit($declared) || (int) $declared > $server->options->bodyLimit)) {
            throw new HttpTransportException(413, 'Request body is too large.');
        }

        if (strlen($request->body) > $server->options->bodyLimit) {
            throw new HttpTransportException(413, 'Request body is too large.');
        }
    }

    private function mediaType(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return strtolower(trim(explode(';', $value, limit: 2)[0]));
    }

    /**
     * @return list<string>
     */
    private function acceptedTypes(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        return array_values(array_filter(array_map($this->mediaType(...), explode(',', $value))));
    }
}
