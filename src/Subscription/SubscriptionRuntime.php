<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Subscription;

use JsonException;
use Throwable;
use Tinywan\Mcp\Contracts\CancellationCoordinatorInterface;
use Tinywan\Mcp\Contracts\NotificationInterface;
use Tinywan\Mcp\Contracts\SubscriptionResolverInterface;
use Tinywan\Mcp\Notification\NotificationEnvelope;
use Tinywan\Mcp\Notification\SubscriptionAcknowledgedNotification;
use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\Result\StreamResult;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Runtime\CancellationToken;
use Tinywan\Mcp\Runtime\ExecutionContext;

final readonly class SubscriptionRuntime
{
    public function __construct(
        private SubscriptionResolverInterface $resolver,
        private CancellationCoordinatorInterface $cancellations,
    ) {}

    public function listen(ServerDefinition $server, ExecutionContext $context, SubscriptionCall $call): StreamResult
    {
        $registered = $server->subscription();
        if (
            $registered === null
            || !$server->features->subscriptions->authorizer->canListen($context->principal, $call->notifications)
        ) {
            throw new ProtocolException(ProtocolErrors::invalidParams(
                $call->requestId,
                'Unknown or unauthorized subscription.',
            ));
        }

        $token = $context->cancellation instanceof CancellationToken ? $context->cancellation : new CancellationToken();
        if (!$this->cancellations->register($call->requestId, $token)) {
            throw new ProtocolException(ProtocolErrors::invalidParams(
                $call->requestId,
                'Subscription ID is already active.',
            ));
        }
        $requestContext = new ExecutionContext(
            $context->principal,
            $context->traceId,
            $context->protocolVersion,
            $context->clientInfo,
            $context->clientCapabilities,
            $context->deadline,
            $token,
            $context->progress,
        );

        try {
            return $this->collect($server, $requestContext, $call, $registered);
        } finally {
            $this->cancellations->release($call->requestId);
        }
    }

    private function collect(
        ServerDefinition $server,
        ExecutionContext $context,
        SubscriptionCall $call,
        \Tinywan\Mcp\Registry\RegisteredSubscription $registered,
    ): StreamResult {
        $limits = $server->options->subscriptionLimits;
        $agreed = $call->notifications->intersect($registered->definition->supportedNotifications);
        $events = [];
        $bytes = 0;
        $started = hrtime(as_number: true);
        $acknowledgement = new SubscriptionAcknowledgedNotification($agreed);
        if (!$this->append($events, $bytes, $acknowledgement, $call, $limits)) {
            throw new ProtocolException(ProtocolErrors::internal($call->requestId, $context->traceId));
        }

        try {
            $provider = $this->resolver->resolve($registered);
            foreach ($provider->notifications(
                new SubscriptionCall($call->requestId, $agreed),
                $context,
            ) as $notification) {
                if ($context->cancellation->isCancelled() || $context->deadline->isExpired()) {
                    break;
                }
                if (((hrtime(as_number: true) - $started) / 1_000_000) >= $limits->lifetimeMs) {
                    break;
                }
                if (!$agreed->allows($notification)) {
                    continue;
                }
                if ((count($events) - 1) >= $limits->eventCount) {
                    break;
                }
                if (!$this->append($events, $bytes, $notification, $call, $limits)) {
                    break;
                }
            }
        } catch (ProtocolException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new ProtocolException(ProtocolErrors::internal($call->requestId, $context->traceId));
        }

        if (!$context->cancellation->isCancelled()) {
            $this->appendEnvelope($events, $bytes, $this->completion($server, $call), $limits);
        }

        return new StreamResult($events);
    }

    /**
     * @param list<array<string, mixed>> $events
     */
    private function append(
        array &$events,
        int &$totalBytes,
        NotificationInterface $notification,
        SubscriptionCall $call,
        SubscriptionLimits $limits,
    ): bool {
        $envelope = (new NotificationEnvelope($notification, $call->requestId))->toArray();
        return $this->appendEnvelope($events, $totalBytes, $envelope, $limits);
    }

    /**
     * @param list<array<string, mixed>> $events
     * @param array<string, mixed> $envelope
     */
    private function appendEnvelope(array &$events, int &$totalBytes, array $envelope, SubscriptionLimits $limits): bool
    {
        try {
            $encoded = json_encode($envelope, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            return false;
        }
        $bytes = strlen($encoded);
        if ($bytes > $limits->eventBytes || ($totalBytes + $bytes) > $limits->totalBytes) {
            return false;
        }

        $events[] = $envelope;
        $totalBytes += $bytes;

        return true;
    }

    /** @return array<string, mixed> */
    private function completion(ServerDefinition $server, SubscriptionCall $call): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $call->requestId->value(),
            'result' => [
                '_meta' => [
                    'io.modelcontextprotocol/serverInfo' => $server->identity->toArray(),
                    'io.modelcontextprotocol/subscriptionId' => $call->requestId->value(),
                ],
                'resultType' => 'complete',
            ],
        ];
    }
}
