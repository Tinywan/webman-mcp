<?php

declare(strict_types=1);

use Tinywan\Mcp\Notification\ListChangedNotification;
use Tinywan\Mcp\Notification\NotificationEnvelope;
use Tinywan\Mcp\Notification\ProgressNotification;
use Tinywan\Mcp\Notification\ResourceUpdatedNotification;
use Tinywan\Mcp\Notification\ServerMessageNotification;
use Tinywan\Mcp\Protocol\ClientCapabilities;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\RequestId;
use Tinywan\Mcp\Registry\RegisteredSubscription;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerFeatures;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Registry\ServerOptions;
use Tinywan\Mcp\Registry\SubscriptionRegistration;
use Tinywan\Mcp\Runtime\CallbackProgressReporter;
use Tinywan\Mcp\Runtime\CancellationToken;
use Tinywan\Mcp\Runtime\Deadline;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Runtime\ProcessCancellationCoordinator;
use Tinywan\Mcp\Security\AllowAllSubscriptionAuthorizer;
use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Subscription\FactorySubscriptionResolver;
use Tinywan\Mcp\Subscription\SubscriptionCall;
use Tinywan\Mcp\Subscription\SubscriptionDefinition;
use Tinywan\Mcp\Subscription\SubscriptionFilter;
use Tinywan\Mcp\Subscription\SubscriptionLimits;
use Tinywan\Mcp\Subscription\SubscriptionRuntime;
use Tinywan\Mcp\Tests\Fixtures\CountingSubscriptionResolver;
use Tinywan\Mcp\Tests\Fixtures\SequenceSubscription;

function subscription_context(#[\SensitiveParameter] ?CancellationToken $token = null): ExecutionContext
{
    return new ExecutionContext(
        new Principal('subscriber'),
        'trace-subscription',
        '2026-07-28',
        null,
        new ClientCapabilities([]),
        Deadline::none(),
        $token ?? new CancellationToken(),
    );
}

function subscription_server(
    ?\Tinywan\Mcp\Contracts\SubscriptionAuthorizerInterface $authorizer = null,
    ?SubscriptionLimits $limits = null,
): ServerDefinition {
    $registered = new RegisteredSubscription(
        new SubscriptionDefinition(new SubscriptionFilter(
            resourceSubscriptions: ['status://service'],
            resourcesListChanged: true,
            toolsListChanged: true,
        )),
        SequenceSubscription::class,
    );

    return new ServerDefinition(
        'subscription-test',
        '/subscription-test',
        new ServerIdentity('Subscription Test', '0.1.0'),
        [],
        options: new ServerOptions(subscriptionLimits: $limits ?? new SubscriptionLimits()),
        features: new ServerFeatures(subscriptions: new SubscriptionRegistration($registered, $authorizer)),
    );
}

function subscription_call(): SubscriptionCall
{
    return new SubscriptionCall(
        RequestId::from(41),
        new SubscriptionFilter(resourceSubscriptions: ['status://service'], toolsListChanged: true),
    );
}

it('authorizes before resolving a fresh Subscription provider', function (): void {
    $resolver = new CountingSubscriptionResolver(new SequenceSubscription());
    $runtime = new SubscriptionRuntime($resolver, new ProcessCancellationCoordinator());

    expect(fn() => $runtime->listen(subscription_server(), subscription_context(), subscription_call()))
        ->toThrow(ProtocolException::class)
        ->and($resolver->resolutions)
        ->toBe(0);

    $factory = new FactorySubscriptionResolver();
    $registered = subscription_server(new AllowAllSubscriptionAuthorizer())->subscription();
    assert($registered instanceof RegisteredSubscription, description: 'The test Server must register a subscription.');
    expect($factory->resolve($registered))->not->toBe($factory->resolve($registered));
});

it('acknowledges first and preserves requested event order while filtering unrequested events', function (): void {
    $provider = new SequenceSubscription([
        new ListChangedNotification(ListChangedNotification::TOOLS),
        new ListChangedNotification(ListChangedNotification::RESOURCES),
        new ResourceUpdatedNotification('status://service'),
    ]);
    $runtime = new SubscriptionRuntime(
        new CountingSubscriptionResolver($provider),
        new ProcessCancellationCoordinator(),
    );
    $result = $runtime->listen(
        subscription_server(new AllowAllSubscriptionAuthorizer()),
        subscription_context(),
        subscription_call(),
    );

    $methods = [];
    foreach ($result->events as $event) {
        /** @var mixed $method */
        $method = $event['method'] ?? null;
        $methods[] = is_string($method) ? $method : null;
    }
    /** @var mixed $parameters */
    $parameters = $result->events[0]['params'] ?? null;
    assert(is_array($parameters), description: 'Acknowledgement parameters must be an object.');
    /** @var mixed $meta */
    $meta = $parameters['_meta'] ?? null;
    assert(is_array($meta), description: 'Acknowledgement metadata must be an object.');

    expect($methods)
        ->toBe([
            'notifications/subscriptions/acknowledged',
            'notifications/tools/list_changed',
            'notifications/resources/updated',
            null,
        ])
        ->and($meta['io.modelcontextprotocol/subscriptionId'] ?? null)
        ->toBe(41);
});

it('enforces event count bounds and stops encoding after cancellation', function (): void {
    $events = [
        new ListChangedNotification(ListChangedNotification::TOOLS),
        new ResourceUpdatedNotification('status://service'),
    ];
    $bounded = new SubscriptionRuntime(
        new CountingSubscriptionResolver(new SequenceSubscription($events)),
        new ProcessCancellationCoordinator(),
    );
    $cancelled = new SubscriptionRuntime(
        new CountingSubscriptionResolver(new SequenceSubscription($events, cancelAfterFirst: true)),
        new ProcessCancellationCoordinator(),
    );

    expect($bounded->listen(
        subscription_server(new AllowAllSubscriptionAuthorizer(), new SubscriptionLimits(eventCount: 1)),
        subscription_context(),
        subscription_call(),
    )->events)
        ->toHaveCount(3)
        ->and($cancelled->listen(
            subscription_server(new AllowAllSubscriptionAuthorizer()),
            subscription_context(),
            subscription_call(),
        )->events)
        ->toHaveCount(2);
});

it('rejects progress after cancellation and serializes only sanitized server message data', function (): void {
    $token = new CancellationToken();
    $emitted = [];
    $reporter = new CallbackProgressReporter(
        'progress-secret',
        $token,
        static function (ProgressNotification $notification) use (&$emitted): void {
            $emitted[] = $notification;
        },
    );
    expect($reporter->report(1, 2, 'working'))->toBeTrue();
    $token->cancel('disconnect');
    expect($reporter->report(2, 2, 'done'))->toBeFalse()->and($emitted)->toHaveCount(1);

    $message = (new NotificationEnvelope(
        new ServerMessageNotification('error', 'trace-safe', 'handler-failed', 'mcp'),
    ))->toArray();
    /** @var mixed $messageParams */
    $messageParams = $message['params'] ?? null;
    assert(is_array($messageParams), description: 'Server message parameters must be an object.');
    expect($messageParams['data'] ?? null)
        ->toBe(['traceId' => 'trace-safe', 'outcome' => 'handler-failed'])
        ->and(json_encode($message, JSON_THROW_ON_ERROR))
        ->not->toContain('password', 'stack', 'exception');
});
