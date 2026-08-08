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

    public ServerFeatures $features;

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
        ?ServerFeatures $features = null,
    ) {
        if ($id === '') {
            throw new InvalidArgumentException('A Server ID cannot be empty.');
        }

        $normalizedPath = '/' . trim($path, characters: '/');
        $this->path = $normalizedPath === '/' ? '/' : rtrim($normalizedPath, characters: '/');
        $this->registeredTools = array_values($tools);
        $features ??= new ServerFeatures();
        $this->features = $features;
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

    /**
     * @return list<RegisteredResource>
     */
    public function resources(): array
    {
        return $this->features->resources->resources;
    }

    public function resource(string $uri): ?RegisteredResource
    {
        foreach ($this->features->resources->resources as $resource) {
            if ($resource->definition->uri === $uri) {
                return $resource;
            }
        }

        return null;
    }

    /**
     * @return list<RegisteredResourceTemplate>
     */
    public function resourceTemplates(): array
    {
        return $this->features->resources->templates;
    }

    /** @return list<RegisteredPrompt> */
    public function prompts(): array
    {
        return $this->features->prompts->prompts;
    }

    public function prompt(string $name): ?RegisteredPrompt
    {
        foreach ($this->features->prompts->prompts as $prompt) {
            if ($prompt->definition->name === $name) {
                return $prompt;
            }
        }

        return null;
    }

    /** @return list<RegisteredCompletion> */
    public function completions(): array
    {
        return $this->features->prompts->completions;
    }

    public function completion(string $key): ?RegisteredCompletion
    {
        foreach ($this->features->prompts->completions as $completion) {
            if ($completion->reference->key() === $key) {
                return $completion;
            }
        }

        return null;
    }

    public function subscription(): ?RegisteredSubscription
    {
        return $this->features->subscriptions->subscription;
    }
}
