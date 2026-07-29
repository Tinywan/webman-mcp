<?php

declare(strict_types=1);

use Pest\PendingCalls\TestCall;
use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\RequestId;

/** @var TestCall $errorCodes */
$errorCodes = it('creates standard and MCP error codes', function (string $factory, int $code): void {
    $id = RequestId::from(7);
    $error = match ($factory) {
        'parse' => ProtocolErrors::parse(),
        'invalidRequest' => ProtocolErrors::invalidRequest($id),
        'methodNotFound' => ProtocolErrors::methodNotFound($id, 'unknown'),
        'invalidParams' => ProtocolErrors::invalidParams($id, 'Invalid parameters'),
        'internal' => ProtocolErrors::internal($id, 'trace-1'),
        'headerMismatch' => ProtocolErrors::headerMismatch($id, 'Mcp-Method'),
        'unsupportedVersion' => ProtocolErrors::unsupportedVersion($id, '1900-01-01'),
        default => throw new InvalidArgumentException("Unknown error factory: {$factory}"),
    };

    expect($error->code)->toBe($code);
});
assert($errorCodes instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$errorCodes->with([
    ['parse', -32_700],
    ['invalidRequest', -32_600],
    ['methodNotFound', -32_601],
    ['invalidParams', -32_602],
    ['internal', -32_603],
    ['headerMismatch', -32_020],
    ['unsupportedVersion', -32_022],
]);
unset($errorCodes);

it('omits an unsafe ID and preserves a valid ID', function (): void {
    expect(ProtocolErrors::parse()->toEnvelope())
        ->not
        ->toHaveKey('id')
        ->and(ProtocolErrors::invalidParams(RequestId::from('safe'), 'Invalid')->toEnvelope()['id'])
        ->toBe('safe');
});

it('lists the requested and supported protocol versions', function (): void {
    $error = ProtocolErrors::unsupportedVersion(RequestId::from(1), '1900-01-01');

    expect($error->data)->toBe([
        'supported' => ['2026-07-28'],
        'requested' => '1900-01-01',
    ]);
});
