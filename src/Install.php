<?php

declare(strict_types=1);

namespace Tinywan\Mcp;

use Tinywan\Mcp\Webman\ConfigPublisher;

final class Install
{
    public const WEBMAN_PLUGIN = true;

    public static function install(): void
    {
        (new ConfigPublisher())->publish(base_path());
    }

    public static function uninstall(): void {}

    private function __construct() {}
}
