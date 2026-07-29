<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use InvalidArgumentException;
use Tinywan\Mcp\Contracts\AuthenticatorInterface;
use Tinywan\Mcp\Contracts\AuthorizerInterface;
use Tinywan\Mcp\Security\DenyAllAuthenticator;
use Tinywan\Mcp\Security\DenyAllAuthorizer;

final readonly class ServerDefinition
{
    public string $path;

    /** @var list<RegisteredTool> */
    private array $registeredTools;

    public AuthenticatorInterface $authenticator;

    public AuthorizerInterface $authorizer;

    public ServerOptions $options;

    /**
     * @param list<RegisteredTool> $tools
     */
    public function __construct(
        public string $id,
        string $path,
        public ServerIdentity $identity,
        array $tools,
        ?AuthenticatorInterface $authenticator = null,
        ?AuthorizerInterface $authorizer = null,
        ?ServerOptions $options = null,
    ) {
        if ($id === '') {
            throw new InvalidArgumentException('A Server ID cannot be empty.');
        }

        $normalizedPath = '/' . trim($path, characters: '/');
        $this->path = $normalizedPath === '/' ? '/' : rtrim($normalizedPath, characters: '/');
        $this->registeredTools = array_values($tools);
        $this->authenticator = $authenticator ?? new DenyAllAuthenticator();
        $this->authorizer = $authorizer ?? new DenyAllAuthorizer();
        $this->options = $options ?? new ServerOptions();
    }

    /**
     * @return list<RegisteredTool>
     */
    public function tools(): array
    {
        return $this->registeredTools;
    }

    public function tool(string $name): ?RegisteredTool
    {
        foreach ($this->registeredTools as $tool) {
            if ($tool->definition->name === $name) {
                return $tool;
            }
        }

        return null;
    }
}
