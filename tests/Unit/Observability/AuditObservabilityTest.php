<?php

declare(strict_types=1);

use Tinywan\Mcp\Contracts\AuditSinkInterface;
use Tinywan\Mcp\Examples\Status\StatusServer;
use Tinywan\Mcp\Observability\AuditEvent;
use Tinywan\Mcp\Observability\TelemetryEvent;
use Tinywan\Mcp\Registry\RegisteredTool;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Security\AllowAllAuthorizer;
use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Security\StaticBearerAuthenticator;
use Tinywan\Mcp\Security\StaticBearerTokenVerifier;
use Tinywan\Mcp\Tests\Fixtures\CollectingAuditSink;
use Tinywan\Mcp\Tests\Fixtures\CollectingTelemetry;
use Tinywan\Mcp\Tests\Fixtures\EchoTool;
use Tinywan\Mcp\Tool\FactoryToolResolver;
use Tinywan\Mcp\Transport\HttpRequestContext;
use Tinywan\Mcp\Transport\StreamableHttpTransport;
use Tinywan\Mcp\Transport\TransportServices;
use Tinywan\Mcp\Webman\WebmanAuditSink;
use Tinywan\Mcp\Webman\WebmanTelemetryAdapter;

function audit_server(): ServerDefinition
{
    $tool = new EchoTool();

    return new ServerDefinition(
        'audit',
        '/audit',
        new ServerIdentity('Audit', '0.1.0'),
        [new RegisteredTool($tool->definition(), EchoTool::class)],
        new StaticBearerAuthenticator(StaticBearerTokenVerifier::fromTokens([
            'audit-test-credential' => new Principal('audited-principal', ['private' => 'attribute']),
        ])),
        new AllowAllAuthorizer(),
    );
}

function audit_request(string $credential = 'audit-test-credential'): HttpRequestContext
{
    $body = json_encode([
        'jsonrpc' => '2.0',
        'id' => 7,
        'method' => 'tools/call',
        'params' => [
            '_meta' => [
                'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                'io.modelcontextprotocol/clientCapabilities' => (object) [],
            ],
            'name' => 'echo',
            'arguments' => ['message' => 'private-argument'],
        ],
    ], JSON_THROW_ON_ERROR);

    return new HttpRequestContext(
        'POST',
        '/audit',
        [
            'Authorization' => "Bearer {$credential}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/event-stream',
            'MCP-Protocol-Version' => '2026-07-28',
            'Mcp-Method' => 'tools/call',
            'Mcp-Name' => 'echo',
        ],
        $body,
    );
}

it('correlates lifecycle stages without recording credentials, arguments, or Principal attributes', function (): void {
    $sink = new CollectingAuditSink();
    $telemetry = new CollectingTelemetry();
    $transport = new StreamableHttpTransport(
        new ServerRegistry([audit_server()]),
        new FactoryToolResolver(),
        services: new TransportServices(audit: $sink, telemetry: $telemetry),
    );

    expect($transport->handle(audit_request())->status)
        ->toBe(200)
        ->and($transport->handle(audit_request())->status)
        ->toBe(200);
    $stages = array_map(static fn(AuditEvent $event): string => $event->stage, $sink->events);
    $traceIds = array_values(array_unique(array_map(
        static fn(AuditEvent $event): string => $event->traceId,
        $sink->events,
    )));
    $serialized = json_encode(array_map(
        static fn(AuditEvent $event): array => $event->toArray(),
        $sink->events,
    ), JSON_THROW_ON_ERROR);

    expect($stages)
        ->toContain('authentication', 'authorization', 'dispatch', 'handler', 'response')
        ->and($traceIds)
        ->toHaveCount(2)
        ->and($telemetry->events)
        ->not->toBeEmpty();
    expect($serialized)->not->toContain(
        'audit-test-credential',
        'private-argument',
        'audited-principal',
        'attribute',
        'Authorization',
    );
});

it('records sanitized authentication rejection and stream cancellation boundaries', function (): void {
    $sink = new CollectingAuditSink();
    $services = new TransportServices(audit: $sink);
    $authentication = new StreamableHttpTransport(
        new ServerRegistry([audit_server()]),
        new FactoryToolResolver(),
        services: $services,
    );
    expect($authentication->handle(audit_request('rejected-credential'))->status)->toBe(401);

    $streaming = new StreamableHttpTransport(
        new ServerRegistry([StatusServer::definition()]),
        new FactoryToolResolver(),
        services: $services,
    );
    $meta = [
        'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
        'io.modelcontextprotocol/clientCapabilities' => (object) [],
    ];
    $listen = json_encode([
        'jsonrpc' => '2.0',
        'id' => 41,
        'method' => 'subscriptions/listen',
        'params' => [
            '_meta' => $meta,
            'notifications' => ['toolsListChanged' => true],
        ],
    ], JSON_THROW_ON_ERROR);
    $cancel = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'notifications/cancelled',
        'params' => ['_meta' => $meta, 'requestId' => 41],
    ], JSON_THROW_ON_ERROR);
    $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json, text/event-stream',
        'MCP-Protocol-Version' => '2026-07-28',
    ];
    expect($streaming->handle(
        new HttpRequestContext(
            'POST',
            '/mcp/status',
            [
                ...$headers,
                'Mcp-Method' => 'subscriptions/listen',
            ],
            $listen,
        ),
    )->status)
        ->toBe(200)
        ->and($streaming->handle(
            new HttpRequestContext(
                'POST',
                '/mcp/status',
                [
                    ...$headers,
                    'Mcp-Method' => 'notifications/cancelled',
                ],
                $cancel,
            ),
        )->status)
        ->toBe(202);

    $serialized = json_encode(array_map(
        static fn(AuditEvent $event): array => $event->toArray(),
        $sink->events,
    ), JSON_THROW_ON_ERROR);
    expect(array_map(static fn(AuditEvent $event): string => $event->stage, $sink->events))
        ->toContain('authentication', 'stream', 'cancellation');
    expect($serialized)->not->toContain('rejected-credential', 'Authorization');
});

it('isolates sink failures and attempts a safe telemetry failure counter', function (): void {
    $sink = new class implements AuditSinkInterface {
        public function record(AuditEvent $event): void
        {
            throw new RuntimeException('sink-private-failure');
        }
    };
    $telemetry = new CollectingTelemetry();
    $transport = new StreamableHttpTransport(
        new ServerRegistry([audit_server()]),
        new FactoryToolResolver(),
        services: new TransportServices(audit: $sink, telemetry: $telemetry),
    );

    $response = $transport->handle(audit_request());
    expect($response->status)
        ->toBe(200)
        ->and(array_map(static fn(TelemetryEvent $event): string => $event->name, $telemetry->events))
        ->toContain('mcp.observability.failure');
    expect($response->body)->not->toContain('sink-private-failure');
});

it('provides Webman-friendly callback adapters behind SDK contracts', function (): void {
    $logs = [];
    $metrics = [];
    $audit = new WebmanAuditSink(static function (string $message, array $context) use (&$logs): void {
        $logs[] = [$message, $context];
    });
    $telemetry = new WebmanTelemetryAdapter(static function (TelemetryEvent $event) use (&$metrics): void {
        $metrics[] = $event;
    });
    $event = new AuditEvent('server', 'trace', 'response', '200', 1, 'tools/list');

    $audit->record($event);
    $telemetry->record(new TelemetryEvent('requests', 'counter', 1));
    expect($logs)->toHaveCount(1)->and($metrics)->toHaveCount(1);
});
