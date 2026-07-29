<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Transport;

final class HeaderValueDecoder
{
    private const PREFIX = '=?base64?';

    private const SUFFIX = '?=';

    public function decode(string $value): string
    {
        if (!str_starts_with($value, self::PREFIX)) {
            return $value;
        }

        if (!str_ends_with($value, self::SUFFIX)) {
            throw new HttpTransportException(400, 'Malformed Base64 Header sentinel.');
        }

        $encoded = substr($value, strlen(self::PREFIX), -strlen(self::SUFFIX));
        $decoded = base64_decode($encoded, strict: true);
        if ($decoded === false || preg_match('//u', $decoded) !== 1) {
            throw new HttpTransportException(400, 'Malformed Base64 Header value.');
        }

        return $decoded;
    }
}
