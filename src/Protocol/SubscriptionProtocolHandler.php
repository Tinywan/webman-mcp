<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol;

use InvalidArgumentException;
use Tinywan\Mcp\Contracts\CancellationCoordinatorInterface;
use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\Result\StreamResult;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Subscription\SubscriptionCall;
use Tinywan\Mcp\Subscription\SubscriptionFilter;
use Tinywan\Mcp\Subscription\SubscriptionRuntime;

final readonly class SubscriptionProtocolHandler
{
    public function __construct(
        private ServerDefinition $server,
        private SubscriptionRuntime $runtime,
        private CancellationCoordinatorInterface $cancellations,
    ) {}

    /** @return null|array<string, mixed>|StreamResult */
    public function dispatch(ProtocolRequest $request, ExecutionContext $context): array|StreamResult|null
    {
        return match ($request->method) {
            'subscriptions/listen' => $this->listen($request, $context),
            'notifications/cancelled' => $this->cancel($request),
            default => null,
        };
    }

    private function listen(ProtocolRequest $request, ExecutionContext $context): StreamResult
    {
        if ($request->id === null) {
            throw new ProtocolException(ProtocolErrors::invalidRequest());
        }

        return $this->runtime->listen(
            $this->server,
            $context,
            new SubscriptionCall($request->id, $this->filter($request)),
        );
    }

    /** @return array<string, mixed> */
    private function cancel(ProtocolRequest $request): array
    {
        $params = new ProtocolParams($request);
        /** @var mixed $rawId */
        $rawId = $request->params['requestId'] ?? null;
        try {
            $requestId = RequestId::from($rawId);
        } catch (InvalidArgumentException) {
            throw new ProtocolException(ProtocolErrors::invalidParams(
                $request->id,
                'Cancellation requestId is required.',
            ));
        }
        $this->cancellations->cancel($requestId, $params->optionalString('reason'));

        return [];
    }

    private function filter(ProtocolRequest $request): SubscriptionFilter
    {
        $notifications = (new ProtocolParams($request))->object('notifications');

        return new SubscriptionFilter(
            $this->boolean($notifications, 'promptsListChanged', $request),
            $this->stringList($notifications, 'resourceSubscriptions', $request),
            $this->boolean($notifications, 'resourcesListChanged', $request),
            $this->boolean($notifications, 'toolsListChanged', $request),
        );
    }

    /** @param array<string, mixed> $values */
    private function boolean(array $values, string $name, ProtocolRequest $request): bool
    {
        if (!array_key_exists($name, $values)) {
            return false;
        }
        if (!is_bool($values[$name])) {
            throw new ProtocolException(ProtocolErrors::invalidParams($request->id, "{$name} must be boolean."));
        }

        return $values[$name];
    }

    /**
     * @param array<string, mixed> $values
     * @return list<string>
     */
    private function stringList(array $values, string $name, ProtocolRequest $request): array
    {
        if (!array_key_exists($name, $values)) {
            return [];
        }
        /** @var mixed $list */
        $list = $values[$name];
        if (!is_array($list) || !array_is_list($list)) {
            throw new ProtocolException(ProtocolErrors::invalidParams($request->id, "{$name} must be a string list."));
        }
        $strings = [];
        foreach (array_keys($list) as $key) {
            /** @var mixed $value */
            $value = $list[$key];
            if (!is_string($value) || $value === '') {
                throw new ProtocolException(ProtocolErrors::invalidParams(
                    $request->id,
                    "{$name} must be a string list.",
                ));
            }
            $strings[] = $value;
        }

        return $strings;
    }
}
