<?php

declare(strict_types=1);

use Pest\PendingCalls\TestCall;
use Tinywan\Mcp\Contracts\BearerTokenVerifierInterface;
use Tinywan\Mcp\Security\AuthenticationException;
use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Security\StaticBearerAuthenticator;
use Tinywan\Mcp\Security\StaticBearerTokenVerifier;
use Tinywan\Mcp\Transport\HttpRequestContext;

/** @param string|list<string>|null $authorization */
function bearer_request(string|array|null $authorization): HttpRequestContext
{
    /** @var array<string, string|list<string>> $headers */
    $headers = [];
    if ($authorization !== null) {
        $headers['Authorization'] = $authorization;
    }

    return new HttpRequestContext('POST', '/mcp', $headers, '{}');
}

it('maps one valid Bearer credential to a configured Principal without retaining plaintext', function (): void {
    $credential = implode('-', ['production', 'credential', 'value']);
    $principal = new Principal('service-account', ['scope' => 'mcp']);
    $verifier = StaticBearerTokenVerifier::fromTokens([$credential => $principal]);
    $authenticator = new StaticBearerAuthenticator($verifier);

    expect($authenticator->authenticate(bearer_request("Bearer {$credential}")))
        ->toBe($principal)
        ->and(serialize($verifier))
        ->not->toContain($credential)->and($principal->attributes)
        ->not->toContain($credential);
});

/** @var TestCall $invalidBearer */
$invalidBearer = it('rejects missing, duplicate, malformed, invalid, and unsupported Bearer credentials', function (string|array|null $authorization): void {
    if (is_array($authorization)) {
        $values = [];
        foreach (array_keys($authorization) as $key) {
            /** @var mixed $value */
            $value = $authorization[$key];
            assert(is_string($value), description: 'Authorization dataset values must be strings.');
            $values[] = $value;
        }
        $authorization = $values;
    }
    $authenticator = new StaticBearerAuthenticator(StaticBearerTokenVerifier::fromTokens([
        'valid-token' => new Principal('valid'),
    ]));

    expect(fn() => $authenticator->authenticate(bearer_request($authorization)))
        ->toThrow(AuthenticationException::class, 'Bearer authentication failed.');
});
assert($invalidBearer instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$invalidBearer->with([
    'missing' => [null],
    'duplicate' => [['Bearer valid-token', 'Bearer other-token']],
    'basic' => ['Basic dXNlcjpwYXNz'],
    'empty' => ['Bearer '],
    'extra whitespace' => ['Bearer  valid-token'],
    'invalid token' => ['Bearer invalid-token'],
    'control character' => ["Bearer valid-token\r\nX-Test: injected"],
]);
unset($invalidBearer);

it('fails closed and redacts verifier exceptions', function (): void {
    $verifier = new class implements BearerTokenVerifierInterface {
        public function verify(#[\SensitiveParameter] string $token): ?Principal
        {
            throw new RuntimeException("remote verifier leaked {$token}");
        }
    };
    $authenticator = new StaticBearerAuthenticator($verifier);

    try {
        $authenticator->authenticate(bearer_request('Bearer private-token'));
        expect(false)->toBeTrue();
    } catch (AuthenticationException $exception) {
        expect($exception->getMessage())->toBe('Bearer authentication failed.');
        expect($exception->getMessage())->not->toContain('private-token');
    }
});
