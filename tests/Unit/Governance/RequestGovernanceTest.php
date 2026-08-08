<?php

declare(strict_types=1);

use Tinywan\Mcp\Contracts\RateLimiterInterface;
use Tinywan\Mcp\Examples\Status\StatusServer;
use Tinywan\Mcp\Governance\FixedWindowRateLimiter;
use Tinywan\Mcp\Governance\GovernanceOptions;
use Tinywan\Mcp\Governance\MemoryIdempotencyStore;
use Tinywan\Mcp\Governance\ProcessConcurrencyLimiter;
use Tinywan\Mcp\Governance\RateLimitDecision;
use Tinywan\Mcp\Governance\RequestDescriptor;
use Tinywan\Mcp\Registry\RegisteredTool;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Registry\ServerOptions;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Security\AllowAllAuthorizer;
use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Security\StaticBearerAuthenticator;
use Tinywan\Mcp\Security\StaticBearerTokenVerifier;
use Tinywan\Mcp\Tests\Fixtures\EchoTool;
use Tinywan\Mcp\Tests\Fixtures\SlowTool;
use Tinywan\Mcp\Tool\FactoryToolResolver;
use Tinywan\Mcp\Transport\HttpRequestContext;
use Tinywan\Mcp\Transport\StreamableHttpTransport;

function governed_server(
    GovernanceOptions $governance,
    ?\Tinywan\Mcp\Contracts\ToolInterface $handler = null,
): ServerDefinition {
    $handler ??= new EchoTool();
    $tokens = StaticBearerTokenVerifier::fromTokens([
        'principal-one-credential' => new Principal('principal-one'),
        'principal-two-credential' => new Principal('principal-two'),
    ]);

    return new ServerDefinition(
        'governed',
        '/governed',
        new ServerIdentity('Governed', '0.1.0'),
        [new RegisteredTool($handler->definition(), $handler::class)],
        new StaticBearerAuthenticator($tokens),
        new AllowAllAuthorizer(),
        new ServerOptions(governance: $governance),
    );
}

/** @param array<string, string> $headers */
function governed_request(
    string $credential,
    string $body,
    array $headers = [],
    string $path = '/governed',
): HttpRequestContext {
    return new HttpRequestContext(
        'POST',
        $path,
        [
            'Authorization' => "Bearer {$credential}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/event-stream',
            'MCP-Protocol-Version' => '2026-07-28',
            ...$headers,
        ],
        $body,
    );
}

function governed_body(string $tool = 'echo', string $message = 'hello', int $id = 1): string
{
    $arguments = $tool === 'echo' ? ['message' => $message] : [];

    return json_encode([
        'jsonrpc' => '2.0',
        'id' => $id,
        'method' => 'tools/call',
        'params' => [
            '_meta' => [
                'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                'io.modelcontextprotocol/clientCapabilities' => (object) [],
            ],
            'name' => $tool,
            'arguments' => $arguments,
        ],
    ], JSON_THROW_ON_ERROR);
}

function governed_transport(ServerDefinition $server): StreamableHttpTransport
{
    return new StreamableHttpTransport(new ServerRegistry([$server]), new FactoryToolResolver());
}

it('isolates rate limits by Principal and rejects before parsing', function (): void {
    $limiter = new FixedWindowRateLimiter(1);
    $transport = governed_transport(governed_server(new GovernanceOptions(rateLimiter: $limiter)));
    $headers = ['Mcp-Method' => 'tools/call', 'Mcp-Name' => 'echo'];

    expect($transport->handle(governed_request('principal-one-credential', governed_body(), $headers))->status)
        ->toBe(200)
        ->and($transport->handle(governed_request(
            'principal-one-credential',
            body: '{invalid',
            headers: $headers,
        ))->status)
        ->toBe(429)
        ->and($transport->handle(governed_request('principal-two-credential', governed_body(id: 2), $headers))->status)
        ->toBe(200);
});

it('fails closed when an admission dependency throws', function (): void {
    $limiter = new class implements RateLimiterInterface {
        public function decide(RequestDescriptor $request): RateLimitDecision
        {
            throw new RuntimeException('rate backend unavailable with private details');
        }
    };
    $transport = governed_transport(governed_server(new GovernanceOptions(rateLimiter: $limiter)));
    $response = $transport->handle(governed_request('principal-one-credential', governed_body(), [
        'Mcp-Method' => 'tools/call',
        'Mcp-Name' => 'echo',
    ]));

    expect($response->status)->toBe(503)->and($response->body)->toBe('Service Unavailable');
});

it('releases concurrency leases after success and parse failure', function (): void {
    $limiter = new ProcessConcurrencyLimiter(1);
    $transport = governed_transport(governed_server(new GovernanceOptions(concurrencyLimiter: $limiter)));
    $headers = ['Mcp-Method' => 'tools/call', 'Mcp-Name' => 'echo'];
    $descriptor = new RequestDescriptor(
        'governed',
        hash(algo: 'sha256', data: 'principal-one'),
        'tools/call',
        '/governed',
        'trace',
    );

    expect($transport->handle(governed_request('principal-one-credential', governed_body(), $headers))->status)
        ->toBe(200)
        ->and($limiter->active($descriptor))
        ->toBe(0)
        ->and($transport->handle(governed_request(
            'principal-one-credential',
            body: '{invalid',
            headers: $headers,
        ))->status)
        ->toBe(400)
        ->and($limiter->active($descriptor))
        ->toBe(0);
});

it('replays an identical configured method without resolving a Tool again', function (): void {
    $store = new MemoryIdempotencyStore();
    $transport = governed_transport(governed_server(new GovernanceOptions(
        idempotentMethods: ['tools/call'],
        idempotencyStore: $store,
    )));
    $headers = [
        'Mcp-Method' => 'tools/call',
        'Mcp-Name' => 'echo',
        'Idempotency-Key' => 'operation-1',
    ];
    $before = EchoTool::$instances;
    $first = $transport->handle(governed_request('principal-one-credential', governed_body(), $headers));
    $second = $transport->handle(governed_request('principal-one-credential', governed_body(), $headers));
    $otherPrincipal = $transport->handle(governed_request('principal-two-credential', governed_body(), $headers));
    $conflict = $transport->handle(governed_request(
        'principal-one-credential',
        governed_body(message: 'different'),
        $headers,
    ));

    expect($second->body)
        ->toBe($first->body)
        ->and(EchoTool::$instances - $before)
        ->toBe(2)
        ->and($conflict->status)
        ->toBe(400)
        ->and(EchoTool::$instances - $before)
        ->toBe(2);
    expect($otherPrincipal->body)->not->toBe($first->body);
});

it('replaces slow and oversized JSON or stream responses with sanitized errors', function (): void {
    $headers = ['Mcp-Method' => 'tools/call', 'Mcp-Name' => 'slow'];
    $slow = governed_transport(governed_server(new GovernanceOptions(deadlineMs: 1), new SlowTool()));
    $large = governed_transport(governed_server(new GovernanceOptions(responseBytes: 256)));
    $base = StatusServer::definition();
    $streamServer = new ServerDefinition(
        $base->id,
        $base->path,
        $base->identity,
        $base->tools(),
        $base->authenticator,
        $base->authorizer,
        new ServerOptions(governance: new GovernanceOptions(responseBytes: 256)),
        $base->features,
    );
    $stream = governed_transport($streamServer);
    $listen = json_encode([
        'jsonrpc' => '2.0',
        'id' => 41,
        'method' => 'subscriptions/listen',
        'params' => [
            '_meta' => [
                'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                'io.modelcontextprotocol/clientCapabilities' => (object) [],
            ],
            'notifications' => ['toolsListChanged' => true, 'resourceSubscriptions' => ['status://service']],
        ],
    ], JSON_THROW_ON_ERROR);

    $slowResponse = $slow->handle(governed_request('principal-one-credential', governed_body('slow'), $headers));
    $largeResponse = $large->handle(governed_request(
        'principal-one-credential',
        governed_body(message: str_repeat('x', times: 200)),
        ['Mcp-Method' => 'tools/call', 'Mcp-Name' => 'echo'],
    ));
    $streamResponse = $stream->handle(
        new HttpRequestContext(
            'POST',
            '/mcp/status',
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json, text/event-stream',
                'MCP-Protocol-Version' => '2026-07-28',
                'Mcp-Method' => 'subscriptions/listen',
            ],
            $listen,
        ),
    );

    foreach ([$slowResponse, $largeResponse, $streamResponse] as $response) {
        expect($response->status)->toBe(500)->and($response->body)->toContain('Internal error', 'traceId');
        expect($response->body)->not->toContain(str_repeat('x', times: 50), 'complete', 'status://service');
    }
});
