<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Examples\Calculator;

use Tinywan\Mcp\Registry\RegisteredTool;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Security\AllowAllAuthorizer;
use Tinywan\Mcp\Security\AllowAnonymousAuthenticator;

final class CalculatorServer
{
    public static function definition(): ServerDefinition
    {
        $calculator = new CalculatorTool();

        return new ServerDefinition(
            'calculator',
            '/mcp/calculator',
            new ServerIdentity('Calculator', '0.1.0'),
            [new RegisteredTool($calculator->definition(), CalculatorTool::class)],
            new AllowAnonymousAuthenticator(),
            new AllowAllAuthorizer(),
        );
    }
}
