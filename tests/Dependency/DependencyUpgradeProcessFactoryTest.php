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

namespace FastForward\DevTools\Tests\Dependency;

use FastForward\DevTools\Dependency\DependencyUpgradeProcessFactory;
use FastForward\DevTools\Process\ProcessBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DependencyUpgradeProcessFactory::class)]
final class DependencyUpgradeProcessFactoryTest extends TestCase
{
    #[Test]
    public function createWillBuildPreviewUpgradeProcesses(): void
    {
        $factory = new DependencyUpgradeProcessFactory(new ProcessBuilder());

        $processes = $factory->create(false, true);

        self::assertCount(2, $processes);
        self::assertSame("'vendor/bin/jack' 'open-versions' '--dev' '--dry-run'", $processes[0]->getCommandLine());
        self::assertSame("'vendor/bin/jack' 'raise-to-installed' '--dry-run'", $processes[1]->getCommandLine());
    }

    #[Test]
    public function createWillBuildFixUpgradeProcesses(): void
    {
        $factory = new DependencyUpgradeProcessFactory(new ProcessBuilder());

        $processes = $factory->create(true, false);

        self::assertCount(3, $processes);
        self::assertSame("'vendor/bin/jack' 'open-versions'", $processes[0]->getCommandLine());
        self::assertSame("'vendor/bin/jack' 'raise-to-installed'", $processes[1]->getCommandLine());
        self::assertSame("'composer' 'update' '-W' '--no-progress'", $processes[2]->getCommandLine());
    }
}
