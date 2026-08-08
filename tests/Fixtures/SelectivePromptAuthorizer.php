<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use Tinywan\Mcp\Contracts\PromptAuthorizerInterface;
use Tinywan\Mcp\Prompt\CompletionReference;
use Tinywan\Mcp\Prompt\PromptDefinition;
use Tinywan\Mcp\Security\Principal;

final readonly class SelectivePromptAuthorizer implements PromptAuthorizerInterface
{
    /**
     * @param list<string> $listable
     * @param list<string> $gettable
     * @param list<string> $completable
     */
    public function __construct(
        private array $listable,
        private array $gettable,
        private array $completable,
    ) {}

    public function canList(Principal $principal, PromptDefinition $prompt): bool
    {
        return in_array($prompt->name, $this->listable, strict: true);
    }

    public function canGet(Principal $principal, PromptDefinition $prompt): bool
    {
        return in_array($prompt->name, $this->gettable, strict: true);
    }

    public function canComplete(Principal $principal, CompletionReference $reference): bool
    {
        return in_array($reference->key(), $this->completable, strict: true);
    }
}
