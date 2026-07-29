<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Webman;

use Closure;
use RuntimeException;
use support\Container as SupportContainer;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Tool\ContainerToolResolver;
use Tinywan\Mcp\Transport\StreamableHttpTransport;
use Tinywan\Mcp\Transport\WebmanHttpTransport;
use Webman\Container;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\Route;

final class McpBootstrap
{
    public static function register(mixed $configured): ServerRegistry
    {
        $registry = RegistryProvider::fromConfig($configured);
        $transport = self::transport($registry, self::container());

        foreach ($registry->servers() as $server) {
            Route::any($server->path, $transport->handle(...));
        }

        return $registry;
    }

    public static function transport(ServerRegistry $registry, Container $container): WebmanHttpTransport
    {
        return new WebmanHttpTransport(new StreamableHttpTransport($registry, new ContainerToolResolver($container)));
    }

    /**
     * @return array<string, Closure(Request): Response>
     */
    public static function routeCallbacks(ServerRegistry $registry, Container $container): array
    {
        $transport = self::transport($registry, $container);
        $routes = [];
        foreach ($registry->servers() as $server) {
            $routes[$server->path] = $transport->handle(...);
        }

        return $routes;
    }

    private static function container(): Container
    {
        $container = SupportContainer::instance();
        if (!$container instanceof Container) {
            throw new RuntimeException('The Webman container must be an instance of Webman\\Container.');
        }

        return $container;
    }

    private function __construct() {}
}
