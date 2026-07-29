<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Webman;

use Tinywan\Mcp\Registry\RegistryException;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerRegistry;

final class RegistryProvider
{
    public static function load(): ServerRegistry
    {
        return self::fromConfig(function_exists('config') ? config('plugin.tinywan.webman-mcp.servers', []) : []);
    }

    public static function fromConfig(mixed $configured): ServerRegistry
    {
        if (is_array($configured) && array_key_exists('servers', $configured)) {
            return new ServerRegistry(self::serverDefinitions($configured['servers']));
        }

        return new ServerRegistry(self::serverDefinitions($configured));
    }

    /**
     * @return list<ServerDefinition>
     */
    private static function serverDefinitions(mixed $configured): array
    {
        if (!is_array($configured)) {
            throw new RegistryException('MCP Server configuration must be an array.');
        }

        $servers = [];
        foreach (array_keys($configured) as $key) {
            if (!$configured[$key] instanceof ServerDefinition) {
                throw new RegistryException("MCP Server configuration entry '{$key}' must be a ServerDefinition.");
            }

            $servers[] = $configured[$key];
        }

        return $servers;
    }

    private function __construct() {}
}
