<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Prompt\CompletionReference;
use Tinywan\Mcp\Prompt\PromptDefinition;
use Tinywan\Mcp\Security\Principal;

interface PromptAuthorizerInterface
{
    public function canList(Principal $principal, PromptDefinition $prompt): bool;

    public function canGet(Principal $principal, PromptDefinition $prompt): bool;

    public function canComplete(Principal $principal, CompletionReference $reference): bool;
}
