<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Security;

use Tinywan\Mcp\Contracts\PromptAuthorizerInterface;
use Tinywan\Mcp\Prompt\CompletionReference;
use Tinywan\Mcp\Prompt\PromptDefinition;

final class DenyAllPromptAuthorizer implements PromptAuthorizerInterface
{
    public function canList(Principal $principal, PromptDefinition $prompt): bool
    {
        return false;
    }

    public function canGet(Principal $principal, PromptDefinition $prompt): bool
    {
        return false;
    }

    public function canComplete(Principal $principal, CompletionReference $reference): bool
    {
        return false;
    }
}
