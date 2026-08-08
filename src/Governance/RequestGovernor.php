<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Governance;

use Throwable;
use Tinywan\Mcp\Contracts\ConcurrencyLeaseInterface;
use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\RequestId;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Transport\HttpRequestContext;
use Tinywan\Mcp\Transport\HttpResponse;
use Tinywan\Mcp\Transport\HttpTransportException;

final class RequestGovernor
{
    private ?ConcurrencyLeaseInterface $lease = null;

    private ?string $recordKey = null;

    private ?string $fingerprint = null;

    private readonly RequestDescriptor $descriptor;

    private readonly int $startedAt;

    public function __construct(
        private readonly ServerDefinition $server,
        Principal $principal,
        private readonly HttpRequestContext $http,
        private readonly string $traceId,
        string $method,
    ) {
        $this->descriptor = new RequestDescriptor(
            $server->id,
            hash('sha256', $principal->id),
            $method,
            $http->path,
            $traceId,
        );
        $this->startedAt = (int) hrtime(as_number: true);
    }

    public function admit(): void
    {
        $options = $this->server->options->governance;
        try {
            $rate = $options->rateLimiter?->decide($this->descriptor);
            if ($rate !== null && !$rate->allowed) {
                $headers = $rate->retryAfterSeconds === null
                    ? []
                    : ['Retry-After' => (string) $rate->retryAfterSeconds];
                throw new HttpTransportException(429, 'Too Many Requests', $headers);
            }
            $concurrency = $options->concurrencyLimiter?->acquire($this->descriptor);
            if ($concurrency !== null && !$concurrency->allowed) {
                $headers = $concurrency->retryAfterSeconds === null
                    ? []
                    : ['Retry-After' => (string) $concurrency->retryAfterSeconds];
                throw new HttpTransportException(503, 'Service Unavailable', $headers);
            }
            $this->lease = $concurrency?->lease;
        } catch (HttpTransportException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new HttpTransportException(503, 'Service Unavailable');
        }
    }

    public function replay(?RequestId $requestId): ?HttpResponse
    {
        $options = $this->server->options->governance;
        if (!$options->isIdempotent($this->descriptor->method)) {
            return null;
        }
        $clientKey = $this->http->header('idempotency-key');
        if ($clientKey === null) {
            return null;
        }
        if (preg_match('/\A[A-Za-z0-9._:-]{1,128}\z/D', $clientKey) !== 1) {
            throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Invalid Idempotency-Key.'));
        }

        $this->recordKey = hash('sha256', implode("\n", [
            $this->server->id,
            $this->descriptor->principalKey,
            $this->descriptor->method,
            $clientKey,
        ]));
        $this->fingerprint = hash('sha256', $this->http->body);
        try {
            $record = $options->idempotencyStore?->find($this->recordKey);
        } catch (Throwable) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $this->traceId));
        }
        if ($record === null) {
            return null;
        }
        if (!hash_equals($record->fingerprint, $this->fingerprint)) {
            throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Idempotency key conflict.'));
        }

        return new HttpResponse($record->status, $record->headers, $record->body);
    }

    public function store(HttpResponse $response, ?RequestId $requestId): void
    {
        if (
            $this->recordKey === null
            || $this->fingerprint === null
            || $response->status !== 200
            || ($response->headers['Content-Type'] ?? null) !== 'application/json'
        ) {
            return;
        }
        $options = $this->server->options->governance;
        try {
            $options->idempotencyStore?->store(
                $this->recordKey,
                new IdempotencyRecord(
                    $this->fingerprint,
                    $response->status,
                    $response->headers,
                    $response->body,
                    time() + max(1, (int) ceil($options->idempotencyTtlMs / 1_000)),
                ),
            );
        } catch (Throwable) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $this->traceId));
        }
    }

    public function enforce(HttpResponse $response, ?RequestId $requestId): void
    {
        $options = $this->server->options->governance;
        $durationMs = (hrtime(as_number: true) - $this->startedAt) / 1_000_000;
        if ($durationMs > $options->deadlineMs || strlen($response->body) > $options->responseBytes) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $this->traceId));
        }
    }

    public function release(): void
    {
        $this->lease?->release();
        $this->lease = null;
    }
}
