<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Examples\Assistant;

use Tinywan\Mcp\Prompt\CompletionReference;
use Tinywan\Mcp\Prompt\PromptArgument;
use Tinywan\Mcp\Prompt\PromptDefinition;
use Tinywan\Mcp\Registry\PromptRegistration;
use Tinywan\Mcp\Registry\RegisteredCompletion;
use Tinywan\Mcp\Registry\RegisteredPrompt;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerFeatures;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Security\AllowAllPromptAuthorizer;
use Tinywan\Mcp\Security\AllowAnonymousAuthenticator;

final class AssistantServer
{
    public static function definition(): ServerDefinition
    {
        $reference = new CompletionReference('ref/prompt', 'greet');

        return new ServerDefinition(
            'assistant',
            '/mcp/assistant',
            new ServerIdentity('Assistant', '0.1.0'),
            [],
            new AllowAnonymousAuthenticator(),
            features: new ServerFeatures(
                prompts: new PromptRegistration(
                    [new RegisteredPrompt(
                        new PromptDefinition(
                            'greet',
                            'Create a personalized greeting request.',
                            [new PromptArgument('name', 'Person to greet.', required: true)],
                            'Greeting',
                        ),
                        AssistantPrompt::class,
                    )],
                    [new RegisteredCompletion($reference, AssistantPrompt::class)],
                    new AllowAllPromptAuthorizer(),
                ),
            ),
        );
    }
}
