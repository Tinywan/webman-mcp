<?php

declare(strict_types=1);

use Pest\PendingCalls\TestCall;
use Tinywan\Mcp\Protocol\RequestId;

/** @var TestCall $supportedIds */
$supportedIds = it('preserves supported request IDs', function (string|int $value): void {
    expect(RequestId::from($value)->value())->toBe($value);
});
assert($supportedIds instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$supportedIds->with([
    'string' => ['request-1'],
    'zero' => [0],
    'integer' => [42],
]);
unset($supportedIds);

/** @var TestCall $unsupportedIds */
$unsupportedIds = it('rejects unsupported request IDs', function (mixed $value): void {
    expect(fn(): RequestId => RequestId::from($value))->toThrow(InvalidArgumentException::class);
});
assert($unsupportedIds instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$unsupportedIds->with([
    'float' => [1.5],
    'null' => [null],
    'boolean' => [true],
    'array' => [[]],
    'object' => [(object) []],
]);
unset($unsupportedIds);
