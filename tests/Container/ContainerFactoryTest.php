<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\Container;

use FastForward\DevTools\Container\ContainerFactory;
use FastForward\DevTools\Container\ServiceProvider\DevToolsServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Config\FileLocatorInterface;

#[CoversClass(ContainerFactory::class)]
#[UsesClass(DevToolsServiceProvider::class)]
final class ContainerFactoryTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        ContainerFactory::reset();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        ContainerFactory::reset();
    }

    /**
     * @return void
     */
    #[Test]
    public function createWillReturnTheSharedContainerInstance(): void
    {
        self::assertSame(ContainerFactory::create(), ContainerFactory::create());
    }

    /**
     * @return void
     */
    #[Test]
    public function getWillResolveServicesFromTheSharedContainer(): void
    {
        self::assertInstanceOf(FileLocatorInterface::class, ContainerFactory::get(FileLocatorInterface::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasWillReturnWhetherTheSharedContainerCanResolveAService(): void
    {
        self::assertTrue(ContainerFactory::has(FileLocatorInterface::class));
        self::assertFalse(ContainerFactory::has('dev-tools.missing-service'));
    }

    /**
     * @return void
     */
    #[Test]
    public function setWillOverrideSharedContainerEntries(): void
    {
        $service = new stdClass();

        ContainerFactory::set('dev-tools.custom-service', $service);

        self::assertTrue(ContainerFactory::has('dev-tools.custom-service'));
        self::assertSame($service, ContainerFactory::get('dev-tools.custom-service'));
    }
}
