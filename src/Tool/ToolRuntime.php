<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tool;

use Throwable;
use Tinywan\Mcp\Contracts\ToolResolverInterface;
use Tinywan\Mcp\Protocol\Error\ProtocolErrors;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\RequestId;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Tool\Schema\ToolSchemaValidator;

final readonly class ToolRuntime
{
    public function __construct(
        private ToolSchemaValidator $schemaValidator,
        private ToolResolverInterface $resolver,
    ) {}

    /**
     * @return list<ToolDefinition>
     */
    public function list(ServerDefinition $server, ExecutionContext $context): array
    {
        $definitions = [];
        foreach ($server->tools() as $tool) {
            if (!$server->authorizer->canList($context->principal, $tool->definition)) {
                continue;
            }

            $definitions[] = $tool->definition;
        }

        return $definitions;
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    public function call(
        ServerDefinition $server,
        string $name,
        array $arguments,
        ExecutionContext $context,
        ?RequestId $requestId,
    ): ToolResult {
        $registered = $server->tool($name);
        if ($registered === null || !$server->authorizer->canCall($context->principal, $registered->definition)) {
            throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Unknown or unauthorized tool.'));
        }

        if ($arguments !== [] && array_is_list($arguments)) {
            throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Invalid tool arguments.'));
        }

        /** @var array<string, mixed> $arguments */
        if (!$this->schemaValidator->validateArguments($registered->definition, $arguments)) {
            throw new ProtocolException(ProtocolErrors::invalidParams($requestId, 'Invalid tool arguments.'));
        }

        if ($context->deadline->isExpired()) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $context->traceId));
        }

        try {
            $result = $this->resolver->resolve($registered)->call(new ToolCall($name, $arguments), $context);
        } catch (ProtocolException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $context->traceId));
        }

        if (
            $registered->definition->outputSchema !== null
            && $result->hasStructuredContent
            && !$this->schemaValidator->validateOutput($registered->definition, $result->structuredContent)
        ) {
            throw new ProtocolException(ProtocolErrors::internal($requestId, $context->traceId));
        }

        return $result;
    }
}
