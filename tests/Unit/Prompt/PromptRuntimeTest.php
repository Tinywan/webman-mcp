<?php

declare(strict_types=1);

use Tinywan\Mcp\Prompt\CompletionCall;
use Tinywan\Mcp\Prompt\CompletionReference;
use Tinywan\Mcp\Prompt\FactoryPromptResolver;
use Tinywan\Mcp\Prompt\PromptArgument;
use Tinywan\Mcp\Prompt\PromptDefinition;
use Tinywan\Mcp\Prompt\PromptRuntime;
use Tinywan\Mcp\Protocol\ClientCapabilities;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\RequestId;
use Tinywan\Mcp\Registry\PromptRegistration;
use Tinywan\Mcp\Registry\RegisteredCompletion;
use Tinywan\Mcp\Registry\RegisteredPrompt;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerFeatures;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Registry\ServerOptions;
use Tinywan\Mcp\Runtime\Deadline;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Tests\Fixtures\SelectivePromptAuthorizer;
use Tinywan\Mcp\Tests\Fixtures\TestPrompt;

function prompt_context(): ExecutionContext
{
    return new ExecutionContext(
        new Principal('prompter'),
        'trace-prompt',
        '2026-07-28',
        null,
        new ClientCapabilities([]),
        Deadline::none(),
    );
}

/**
 * @param list<string> $listable
 * @param list<string> $gettable
 * @param list<string> $completable
 */
function prompt_server(array $listable, array $gettable, array $completable): ServerDefinition
{
    $arguments = [new PromptArgument('name', required: true)];
    $promptReference = new CompletionReference('ref/prompt', 'greet');
    $resourceReference = new CompletionReference('ref/resource', 'memory://users/{id}');

    return new ServerDefinition(
        'prompts',
        '/prompts',
        new ServerIdentity('Prompts', '0.1.0'),
        [],
        options: new ServerOptions(prompts: new \Tinywan\Mcp\Registry\PromptLimits(1, 2)),
        features: new ServerFeatures(
            prompts: new PromptRegistration(
                [
                    new RegisteredPrompt(new PromptDefinition('greet', arguments: $arguments), TestPrompt::class),
                    new RegisteredPrompt(new PromptDefinition('second'), TestPrompt::class),
                ],
                [
                    new RegisteredCompletion($promptReference, TestPrompt::class),
                    new RegisteredCompletion($resourceReference, TestPrompt::class),
                ],
                new SelectivePromptAuthorizer($listable, $gettable, $completable),
            ),
        ),
    );
}

it('paginates only visible Prompts and rejects invalid cursors', function (): void {
    $runtime = new PromptRuntime(new FactoryPromptResolver());
    $server = prompt_server(['greet', 'second'], [], []);
    $first = $runtime->list($server, prompt_context(), null, RequestId::from(1));
    assert(is_string($first['nextCursor']), description: 'First Prompt page must include a cursor.');
    $second = $runtime->list($server, prompt_context(), $first['nextCursor'], RequestId::from(1));

    expect($first['prompts'])
        ->toHaveCount(1)
        ->and($second['prompts'])
        ->toHaveCount(1)
        ->and(fn() => $runtime->list($server, prompt_context(), 'invalid', RequestId::from(1)))
        ->toThrow(ProtocolException::class);
});

it('validates Prompt arguments before resolving a fresh renderer', function (): void {
    TestPrompt::$instances = 0;
    $runtime = new PromptRuntime(new FactoryPromptResolver());
    $server = prompt_server([], ['greet'], []);

    expect(fn() => $runtime->get($server, prompt_context(), 'greet', [], RequestId::from(2)))
        ->toThrow(ProtocolException::class)
        ->and(TestPrompt::$instances)
        ->toBe(0);

    $runtime->get($server, prompt_context(), 'greet', ['name' => 'Ada'], RequestId::from(2));
    $runtime->get($server, prompt_context(), 'greet', ['name' => 'Alan'], RequestId::from(3));
    expect(TestPrompt::$instances)->toBe(2);
});

it('authorizes Prompt and Resource completions and bounds unique values', function (): void {
    TestPrompt::$instances = 0;
    $runtime = new PromptRuntime(new FactoryPromptResolver());
    $promptReference = new CompletionReference('ref/prompt', 'greet');
    $resourceReference = new CompletionReference('ref/resource', 'memory://users/{id}');
    $server = prompt_server([], [], [$promptReference->key(), $resourceReference->key()]);
    $prompt = $runtime->complete(
        $server,
        prompt_context(),
        new CompletionCall($promptReference, 'name', 'A'),
        RequestId::from(4),
    );
    $resource = $runtime->complete(
        $server,
        prompt_context(),
        new CompletionCall($resourceReference, 'id', '4'),
        RequestId::from(5),
    );
    assert(
        is_array($prompt['completion']) && is_array($resource['completion']),
        description: 'Completion payloads must be objects.',
    );

    expect($prompt['completion']['values'])
        ->toBe(['Ada', 'Alan'])
        ->and($prompt['completion']['hasMore'])
        ->toBeTrue()
        ->and($resource['completion']['values'])
        ->toBe(['Ada', 'Alan'])
        ->and(TestPrompt::$instances)
        ->toBe(2);
});
