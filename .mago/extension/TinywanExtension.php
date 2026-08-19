<?php
/**
 * @desc TinywanExtension.php 描述信息
 * @author Tinywan(ShaoBo Wan)
 */
declare(strict_types=1);

namespace Tinywan\Mago;

use Mago\Sdk\Extension;
use Tinywan\Mago\Linter\Rules\NoEvalRule;

final class TinywanExtension
{
    private function __construct() {}

    public static function create(): Extension
    {
        return new Extension(
            identifier: 'tinywan/project-rules',
            name: 'Tinywan project rules',
            version: '1.0.0',
            linterRules: [new NoEvalRule()],
        );
    }
}