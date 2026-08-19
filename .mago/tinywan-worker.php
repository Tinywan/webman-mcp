<?php
/**
 * @desc tinywan-worker.php 描述信息
 * @author Tinywan(ShaoBo Wan)
 */
declare(strict_types=1);

use Mago\Sdk\Worker;
use Tinywan\Mago\TinywanExtension;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Worker(
    TinywanExtension::create(),
))->run();