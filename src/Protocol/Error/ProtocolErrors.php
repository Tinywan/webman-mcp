<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol\Error;

use Tinywan\Mcp\Protocol\RequestId;

final class ProtocolErrors
{
    public static function parse(): ProtocolError
    {
        return new ProtocolError(-32_700, 'Parse error: Invalid JSON', 400);
    }

    public static function invalidRequest(?RequestId $id = null, string $message = 'Invalid Request'): ProtocolError
    {
        return new ProtocolError(-32_600, $message, 400, $id);
    }

    public static function methodNotFound(RequestId $id, string $method): ProtocolError
    {
        return new ProtocolError(-32_601, "Method not found: {$method}", 404, $id);
    }

    public static function invalidParams(?RequestId $id, string $message): ProtocolError
    {
        return new ProtocolError(-32_602, $message, 400, $id);
    }

    public static function internal(?RequestId $id, string $traceId): ProtocolError
    {
        return new ProtocolError(-32_603, 'Internal error', 500, $id, ['traceId' => $traceId]);
    }

    public static function headerMismatch(?RequestId $id, string $message): ProtocolError
    {
        return new ProtocolError(-32_020, "Header mismatch: {$message}", 400, $id);
    }

    public static function unsupportedVersion(?RequestId $id, string $requested): ProtocolError
    {
        return new ProtocolError(-32_022, 'Unsupported protocol version', 400, $id, [
            'supported' => ['2026-07-28'],
            'requested' => $requested,
        ]);
    }

    public static function unauthorized(?RequestId $id = null): ProtocolError
    {
        return new ProtocolError(-32_001, 'Unauthorized', 401, $id);
    }

    private function __construct() {}
}
