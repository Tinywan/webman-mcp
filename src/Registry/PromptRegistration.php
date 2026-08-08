<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use Tinywan\Mcp\Contracts\PromptAuthorizerInterface;
use Tinywan\Mcp\Security\DenyAllPromptAuthorizer;

final readonly class PromptRegistration
{
    public PromptAuthorizerInterface $authorizer;

    /**
     * @param list<RegisteredPrompt> $prompts
     * @param list<RegisteredCompletion> $completions
     */
    public function __construct(
        public array $prompts = [],
        public array $completions = [],
        ?PromptAuthorizerInterface $authorizer = null,
    ) {
        $this->authorizer = $authorizer ?? new DenyAllPromptAuthorizer();
    }
}
