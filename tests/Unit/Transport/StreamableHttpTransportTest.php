<?php

declare(strict_types=1);

use Pest\PendingCalls\TestCall;
use Tinywan\Mcp\Examples\Status\StatusServer;
use Tinywan\Mcp\Registry\OriginPolicy;
use Tinywan\Mcp\Registry\RegisteredTool;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Registry\ServerOptions;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Security\AllowAllAuthorizer;
use Tinywan\Mcp\Security\AllowAnonymousAuthenticator;
use Tinywan\Mcp\Tests\Fixtures\EchoTool;
use Tinywan\Mcp\Tool\FactoryToolResolver;
use Tinywan\Mcp\Transport\HttpRequestContext;
use Tinywan\Mcp\Transport\HttpResponse;
use Tinywan\Mcp\Transport\StreamableHttpTransport;
use Tinywan\Mcp\Transport\WebmanHttpTransport;
use Webman\Http\Request;

/**
 * @param list<string> $origins
 */
function transport_server(
    string $id = 'primary',
    string $path = '/mcp',
    array $origins = ['https://allowed.example'],
    int $bodyLimit = 4_096,
): ServerDefinition {
    $echo = new EchoTool();

    return new ServerDefinition(
        $id,
        $path,
        new ServerIdentity("{$id} server", '0.1.0'),
        [new RegisteredTool($echo->definition(), EchoTool::class)],
        new AllowAnonymousAuthenticator(),
        new AllowAllAuthorizer(),
        new ServerOptions(new OriginPolicy($origins), bodyLimit: $bodyLimit),
    );
}

/**
 * @param list<ServerDefinition> $servers
 */
function http_transport(array $servers = []): StreamableHttpTransport
{
    return new StreamableHttpTransport(
        new ServerRegistry($servers === [] ? [transport_server()] : $servers),
        new FactoryToolResolver(),
    );
}

/**
 * @param array<string, mixed> $params
 */
function transport_body(string $method = 'tools/list', array $params = [], ?int $id = 11): string
{
    $message = [
        'jsonrpc' => '2.0',
        'method' => $method,
        'params' => [
            '_meta' => [
                'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                'io.modelcontextprotocol/clientCapabilities' => (object) [],
            ],
            ...$params,
        ],
    ];
    if ($id !== null) {
        $message['id'] = $id;
    }

    return json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
}

/**
 * @param array<string, null|string|list<string>> $overrides
 */
function transport_request(
    string $method = 'POST',
    string $path = '/mcp',
    ?string $body = null,
    array $overrides = [],
): HttpRequestContext {
    $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json, text/event-stream',
        'MCP-Protocol-Version' => '2026-07-28',
        'Mcp-Method' => 'tools/list',
        'Origin' => 'https://allowed.example',
    ];
    foreach ($overrides as $name => $value) {
        if ($value === null) {
            unset($headers[$name]);
            continue;
        }

        $headers[$name] = $value;
    }

    return new HttpRequestContext($method, $path, $headers, $body ?? transport_body());
}

/**
 * @return array<string, mixed>
 */
function response_json(HttpResponse $response): array
{
    return response_json_array(json_decode($response->body, associative: true, flags: JSON_THROW_ON_ERROR));
}

/**
 * @return array<string, mixed>
 */
function response_json_array(mixed $decoded): array
{
    assert(is_array($decoded) && !array_is_list($decoded), description: 'Response body must be a JSON object.');

    /** @var array<string, mixed> $decoded */
    return $decoded;
}

it('routes independent Server paths through the full request pipeline', function (): void {
    $transport = http_transport([
        transport_server('one', path: '/one'),
        transport_server('two', path: '/two'),
    ]);
    $body = transport_body('server/discover');
    $response = $transport->handle(transport_request(path: '/two', body: $body, overrides: [
        'Mcp-Method' => 'server/discover',
    ]));
    $json = response_json($response);
    assert(is_array($json['result']), description: 'Discovery result must be an object.');
    assert(is_array($json['result']['_meta']), description: 'Discovery metadata must be an object.');

    expect($response->status)
        ->toBe(200)
        ->and($json['result']['_meta']['io.modelcontextprotocol/serverInfo'])
        ->toMatchArray(['name' => 'two server']);
});

/** @var TestCall $unsupportedMethods */
$unsupportedMethods = it('rejects every non-POST method and advertises POST', function (string $method): void {
    $response = http_transport()->handle(transport_request(method: $method));

    expect($response->status)->toBe(405)->and($response->headers)->toBe(['Allow' => 'POST']);
});
assert($unsupportedMethods instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$unsupportedMethods->with([['GET'], ['DELETE'], ['PUT']]);
unset($unsupportedMethods);

/** @var TestCall $invalidMedia */
$invalidMedia = it('rejects invalid media negotiation', function (array $headers, int $status): void {
    /** @var array<string, null|string|list<string>> $headers */
    expect(http_transport()->handle(transport_request(overrides: $headers))->status)->toBe($status);
});
assert($invalidMedia instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$invalidMedia->with([
    'wrong content type' => [['Content-Type' => 'text/plain'], 415],
    'missing event stream' => [['Accept' => 'application/json'], 406],
    'missing JSON' => [['Accept' => 'text/event-stream'], 406],
]);
unset($invalidMedia);

it('handles HTTP Header and media type casing according to HTTP rules', function (): void {
    $response = http_transport()->handle(
        new HttpRequestContext(
            'post',
            '/mcp',
            [
                'content-TYPE' => 'Application/JSON; Charset=UTF-8',
                'aCcEpT' => 'Application/JSON, Text/Event-Stream',
                'mcp-PROTOCOL-version' => '2026-07-28',
                'MCP-METHOD' => 'tools/list',
                'ORIGIN' => 'https://allowed.example',
            ],
            transport_body(),
        ),
    );

    expect($response->status)->toBe(200);
});

it('enforces declared and observed body limits before dispatch', function (): void {
    $transport = http_transport([transport_server(bodyLimit: 128)]);

    expect($transport->handle(transport_request(overrides: ['Content-Length' => '129']))->status)
        ->toBe(413)
        ->and($transport->handle(transport_request(body: str_repeat('x', times: 129)))->status)
        ->toBe(413);
});

it('rejects disallowed Origins before dispatch', function (): void {
    $response = http_transport()->handle(transport_request(overrides: ['Origin' => 'https://blocked.example']));

    expect($response->status)->toBe(403);
});

/** @var TestCall $headerMismatches */
$headerMismatches = it('maps missing and conflicting protocol mirrors to HeaderMismatch', function (
    string $body,
    array $headers,
): void {
    /** @var array<string, null|string|list<string>> $headers */
    $response = http_transport()->handle(transport_request(body: $body, overrides: $headers));
    $json = response_json($response);
    assert(is_array($json['error']), description: 'Header mismatch must contain an error object.');

    expect($response->status)->toBe(400)->and($json['id'])->toBe(11)->and($json['error']['code'])->toBe(-32_020);
});
assert($headerMismatches instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$headerMismatches->with([
    'missing version' => [transport_body(), ['MCP-Protocol-Version' => null]],
    'wrong version mirror' => [transport_body(), ['MCP-Protocol-Version' => '2025-11-25']],
    'wrong method mirror' => [transport_body(), ['Mcp-Method' => 'server/discover']],
    'missing Tool name' => [
        transport_body('tools/call', ['name' => 'echo', 'arguments' => ['message' => 'hello']]),
        ['Mcp-Method' => 'tools/call', 'Mcp-Name' => null],
    ],
    'wrong Tool name' => [
        transport_body('tools/call', ['name' => 'echo', 'arguments' => ['message' => 'hello']]),
        ['Mcp-Method' => 'tools/call', 'Mcp-Name' => 'other'],
    ],
]);
unset($headerMismatches);

it('decodes non-ASCII Base64 parameter mirrors losslessly', function (): void {
    $message = '你好，Webman';
    $body = transport_body('tools/call', ['name' => 'echo', 'arguments' => ['message' => $message]]);
    $encoded = '=?base64?' . base64_encode($message) . '?=';
    $response = http_transport()->handle(transport_request(body: $body, overrides: [
        'Mcp-Method' => 'tools/call',
        'Mcp-Name' => 'echo',
        'Mcp-Param-Message' => $encoded,
    ]));
    $json = response_json($response);
    assert(is_array($json['result']), description: 'Tool call must contain a result.');

    expect($response->status)
        ->toBe(200)
        ->and($json['result']['structuredContent'])
        ->toMatchArray(['message' => $message]);
});

/** @var TestCall $invalidParameterMirrors */
$invalidParameterMirrors = it('rejects malformed or conflicting parameter mirrors', function (string $header): void {
    $body = transport_body('tools/call', ['name' => 'echo', 'arguments' => ['message' => 'expected']]);
    $response = http_transport()->handle(transport_request(body: $body, overrides: [
        'Mcp-Method' => 'tools/call',
        'Mcp-Name' => 'echo',
        'Mcp-Param-Message' => $header,
    ]));

    expect($response->status)->toBe(400);
});
assert($invalidParameterMirrors instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$invalidParameterMirrors->with([
    ['different'],
    ['=?base64?not-valid!?='],
    ['=?base64?missing-suffix'],
]);
unset($invalidParameterMirrors);

it('returns an empty 202 notification response and never echoes legacy session state', function (): void {
    $response = http_transport()->handle(transport_request(body: transport_body(id: null), overrides: [
        'Mcp-Session-Id' => 'legacy-session',
        'Last-Event-ID' => 'legacy-event',
    ]));

    expect($response->status)
        ->toBe(202)
        ->and($response->body)
        ->toBe('')
        ->and(array_change_key_case($response->headers))
        ->not->toHaveKeys(['mcp-session-id', 'last-event-id', 'content-type']);
});

it('maps a Webman request and response without session or SSE behavior', function (): void {
    $body = transport_body();
    $raw =
        "POST /mcp HTTP/1.1\r\n"
        . "Host: localhost\r\n"
        . "Content-Type: application/json\r\n"
        . "Accept: application/json, text/event-stream\r\n"
        . "MCP-Protocol-Version: 2026-07-28\r\n"
        . "Mcp-Method: tools/list\r\n"
        . "Origin: https://allowed.example\r\n"
        . 'Content-Length: '
        . strlen($body)
        . "\r\n\r\n"
        . $body;
    $response = (new WebmanHttpTransport(http_transport()))->handle(new Request($raw));

    expect($response->getStatusCode())
        ->toBe(200)
        ->and($response->getHeader('Content-Type'))
        ->toBe('application/json')
        ->and($response->getHeader('Mcp-Session-Id'))
        ->toBeNull();
});

it('uses SSE only for Subscription listening and keeps ordinary paths as JSON', function (): void {
    $transport = http_transport([StatusServer::definition()]);
    $listenBody = transport_body(
        'subscriptions/listen',
        [
            'notifications' => [
                'resourceSubscriptions' => ['status://service'],
                'toolsListChanged' => true,
            ],
        ],
        id: 41,
    );
    $stream = $transport->handle(transport_request(path: '/mcp/status', body: $listenBody, overrides: [
        'Mcp-Method' => 'subscriptions/listen',
        'Origin' => null,
    ]));
    $discover = $transport->handle(transport_request(
        path: '/mcp/status',
        body: transport_body('server/discover'),
        overrides: ['Mcp-Method' => 'server/discover', 'Origin' => null],
    ));
    $raw =
        "POST /mcp/status HTTP/1.1\r\n"
        . "Host: localhost\r\n"
        . "Content-Type: application/json\r\n"
        . "Accept: application/json, text/event-stream\r\n"
        . "MCP-Protocol-Version: 2026-07-28\r\n"
        . "Mcp-Method: subscriptions/listen\r\n"
        . 'Content-Length: '
        . strlen($listenBody)
        . "\r\n\r\n"
        . $listenBody;
    $webman = (new WebmanHttpTransport($transport))->handle(new Request($raw));

    expect($stream->headers['Content-Type'] ?? null)
        ->toBe('text/event-stream')
        ->and($stream->body)
        ->toStartWith(
            "event: message\ndata: {\"jsonrpc\":\"2.0\",\"method\":\"notifications/subscriptions/acknowledged\"",
        )
        ->and($stream->body)
        ->not
        ->toContain('id: ', 'Mcp-Session-Id', 'Last-Event-ID')
        ->and($discover->headers['Content-Type'] ?? null)
        ->toBe('application/json')
        ->and($webman->getHeader('Content-Type'))
        ->toBe('text/event-stream')
        ->and($webman->getHeader('Mcp-Session-Id'))
        ->toBeNull();
});
