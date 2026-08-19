<?php
/**
 * @desc NoEvalRule.php 描述信息
 * @author Tinywan(ShaoBo Wan)
 */
declare(strict_types=1);

namespace Tinywan\Mago\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Syntax\NodeKind;
use Mago\Sdk\Reporting\TextEdit;

final class NoEvalRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'tinywan/no-eval',
            name: 'No eval',
            description: 'Disallows evaluating dynamically generated PHP code.',
            defaultLevel: Level::Error,
            defaultEnabled: true,
            targets: [NodeKind::EvalConstruct],
        );
    }

    public function lint(LintContext $context): void
    {
        // 合作式取消检查，长循环中建议加上
        $context->cancellation->throwIfCancelled();

        $context->report(
        Issue::new(
                'Avoid evaluating dynamically generated PHP code.',
                $context->node->span,
            )->withEdit(TextEdit::replace($context->node->span, '// eval() removed'))
        );
    }
}