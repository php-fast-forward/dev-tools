<?php

namespace FastForward\DevTools\Psr\Container;

use DI\Container as DIContainer;
use Psr\Container\ContainerInterface;

final class Container
{
    private const CONFIG_FILE = __DIR__ . '/../../../config/container.php';

    private static ?ContainerInterface $container = null;

    private static function boot(string $path = self::CONFIG_FILE): ContainerInterface
    {
        if (self::$container !== null) {
            return self::$container;
        }

        $config = require_once $path;

        return self::$container = DIContainer::create($config);
    }

    public static function get(string $id): mixed
    {
        return self::boot()->get($id);
    }

    public static function has(string $id): bool
    {

        return self::boot()->has($id);
    }
}
