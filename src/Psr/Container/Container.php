<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Psr\Container;

use DI\Container as DIContainer;
use Psr\Container\ContainerInterface;

final class Container
{
    private const string CONFIG_FILE = __DIR__ . '/../../../config/container.php';

    private static ?ContainerInterface $container = null;

    /**
     * @param string $path
     *
     * @return ContainerInterface
     */
    private static function boot(string $path = self::CONFIG_FILE): ContainerInterface
    {
        if (self::$container instanceof ContainerInterface) {
            return self::$container;
        }

        $config = require_once $path;

        return self::$container = DIContainer::create($config);
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    public static function get(string $id): mixed
    {
        return self::boot()->get($id);
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public static function has(string $id): bool
    {

        return self::boot()->has($id);
    }
}
