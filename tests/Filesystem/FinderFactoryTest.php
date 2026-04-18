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

namespace FastForward\DevTools\Tests\Filesystem;

use FastForward\DevTools\Filesystem\FinderFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

#[CoversClass(FinderFactory::class)]
final class FinderFactoryTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function createWillReturnFreshFinderInstances(): void
    {
        $factory = new FinderFactory();

        $firstFinder = $factory->create();
        $secondFinder = $factory->create();

        self::assertInstanceOf(Finder::class, $firstFinder);
        self::assertInstanceOf(Finder::class, $secondFinder);
        self::assertNotSame($firstFinder, $secondFinder);
    }
}
