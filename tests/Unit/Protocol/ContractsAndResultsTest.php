<?php

declare(strict_types=1);

use Tinywan\Mcp\Contracts\AuditSinkInterface;
use Tinywan\Mcp\Contracts\AuthenticatorInterface;
use Tinywan\Mcp\Contracts\AuthorizerInterface;
use Tinywan\Mcp\Contracts\BearerTokenVerifierInterface;
use Tinywan\Mcp\Contracts\CompletionProviderInterface;
use Tinywan\Mcp\Contracts\ConcurrencyLimiterInterface;
use Tinywan\Mcp\Contracts\IdempotencyStoreInterface;
use Tinywan\Mcp\Contracts\PromptAuthorizerInterface;
use Tinywan\Mcp\Contracts\PromptRendererInterface;
use Tinywan\Mcp\Contracts\PromptResolverInterface;
use Tinywan\Mcp\Contracts\ProtocolDriverInterface;
use Tinywan\Mcp\Contracts\RateLimiterInterface;
use Tinywan\Mcp\Contracts\ResourceAuthorizerInterface;
use Tinywan\Mcp\Contracts\ResourceHandlerInterface;
use Tinywan\Mcp\Contracts\ResourceResolverInterface;
use Tinywan\Mcp\Contracts\TelemetryInterface;
use Tinywan\Mcp\Contracts\ToolInterface;
use Tinywan\Mcp\Protocol\Result\AcceptedResult;
use Tinywan\Mcp\Protocol\Result\JsonResult;
use Tinywan\Mcp\Protocol\Result\StreamResult;

it('exposes the public extension contracts', function (): void {
    expect(interface_exists(ProtocolDriverInterface::class))
        ->toBeTrue()
        ->and(interface_exists(ToolInterface::class))
        ->toBeTrue()
        ->and(interface_exists(AuthenticatorInterface::class))
        ->toBeTrue()
        ->and(interface_exists(AuthorizerInterface::class))
        ->toBeTrue()
        ->and(interface_exists(ResourceHandlerInterface::class))
        ->toBeTrue()
        ->and(interface_exists(ResourceResolverInterface::class))
        ->toBeTrue()
        ->and(interface_exists(ResourceAuthorizerInterface::class))
        ->toBeTrue()
        ->and(interface_exists(PromptRendererInterface::class))
        ->toBeTrue()
        ->and(interface_exists(CompletionProviderInterface::class))
        ->toBeTrue()
        ->and(interface_exists(PromptResolverInterface::class))
        ->toBeTrue()
        ->and(interface_exists(PromptAuthorizerInterface::class))
        ->toBeTrue()
        ->and(interface_exists(BearerTokenVerifierInterface::class))
        ->toBeTrue()
        ->and(interface_exists(RateLimiterInterface::class))
        ->toBeTrue()
        ->and(interface_exists(ConcurrencyLimiterInterface::class))
        ->toBeTrue()
        ->and(interface_exists(IdempotencyStoreInterface::class))
        ->toBeTrue()
        ->and(interface_exists(AuditSinkInterface::class))
        ->toBeTrue()
        ->and(interface_exists(TelemetryInterface::class))
        ->toBeTrue();
});

it('keeps public contracts on SDK domain types', function (): void {
    $dispatch = new ReflectionMethod(ProtocolDriverInterface::class, 'dispatch');
    $toolCall = new ReflectionMethod(ToolInterface::class, 'call');
    $authenticate = new ReflectionMethod(AuthenticatorInterface::class, 'authenticate');
    $canCall = new ReflectionMethod(AuthorizerInterface::class, 'canCall');

    expect((string) $dispatch->getReturnType())
        ->toBe('Tinywan\Mcp\Protocol\Result\ProtocolDispatchResult')
        ->and((string) $toolCall->getReturnType())
        ->toBe('Tinywan\Mcp\Tool\ToolResult')
        ->and((string) $authenticate->getReturnType())
        ->toBe('Tinywan\Mcp\Security\Principal')
        ->and((string) $canCall->getReturnType())
        ->toBe('bool');
});

it('models JSON, accepted, and reserved stream dispatch results', function (): void {
    $json = new JsonResult(['jsonrpc' => '2.0']);
    $accepted = new AcceptedResult();
    $stream = new StreamResult([]);

    expect($json->status())
        ->toBe(200)
        ->and($json->payload())
        ->toBe(['jsonrpc' => '2.0'])
        ->and($accepted->status())
        ->toBe(202)
        ->and($accepted->payload())
        ->toBeNull()
        ->and($stream->headers()['Content-Type'])
        ->toBe('text/event-stream');
});
