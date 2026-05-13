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

namespace FastForward\DevTools\Tests\Path;

use FastForward\DevTools\Environment\EnvironmentInterface;
use FastForward\DevTools\Path\ManagedWorkspace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[CoversClass(ManagedWorkspace::class)]
final class ManagedWorkspaceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function itWillExposeCanonicalRepositoryManagedPaths(): void
    {
        $environment = $this->createEnvironment();

        self::assertSame('.dev-tools', ManagedWorkspace::getOutputDirectory(environment: $environment));
        self::assertSame(
            '.dev-tools/coverage',
            ManagedWorkspace::getOutputDirectory(ManagedWorkspace::COVERAGE, environment: $environment),
        );
        self::assertSame(
            '.dev-tools/metrics',
            ManagedWorkspace::getOutputDirectory(ManagedWorkspace::METRICS, environment: $environment),
        );
        self::assertSame(
            'tmp/.dev-tools/metrics',
            ManagedWorkspace::getOutputDirectory(ManagedWorkspace::METRICS, 'tmp', $environment)
        );
        self::assertSame('.dev-tools/cache', ManagedWorkspace::getCacheDirectory(environment: $environment));
        self::assertSame(
            '.dev-tools/cache/phpdoc',
            ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHPDOC, environment: $environment)
        );
        self::assertSame(
            '.dev-tools/cache/phpunit',
            ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHPUNIT, environment: $environment)
        );
        self::assertSame(
            '.dev-tools/cache/rector',
            ManagedWorkspace::getCacheDirectory(ManagedWorkspace::RECTOR, environment: $environment)
        );
        self::assertSame(
            '.dev-tools/cache/php-cs-fixer',
            ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHP_CS_FIXER, environment: $environment)
        );
        self::assertSame(
            'tmp/.dev-tools/cache/rector',
            ManagedWorkspace::getCacheDirectory(ManagedWorkspace::RECTOR, 'tmp', $environment)
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillNormalizePathSeparatorsWhenJoiningManagedPaths(): void
    {
        $environment = $this->createEnvironment();

        self::assertSame(
            'tmp/.dev-tools/metrics',
            ManagedWorkspace::getOutputDirectory('/metrics', 'tmp/', $environment)
        );
        self::assertSame(
            'tmp/.dev-tools/cache/phpunit',
            ManagedWorkspace::getCacheDirectory('/phpunit', 'tmp/', $environment),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillUseConfiguredRelativeWorkspaceRoot(): void
    {
        $environment = $this->createEnvironment('.artifacts');

        self::assertSame('.artifacts', ManagedWorkspace::getWorkspaceRoot(environment: $environment));
        self::assertSame(
            '.artifacts/coverage',
            ManagedWorkspace::getOutputDirectory(ManagedWorkspace::COVERAGE, '', $environment),
        );
        self::assertSame(
            'tmp/.artifacts/cache/phpunit',
            ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHPUNIT, 'tmp', $environment),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillUseConfiguredAbsoluteWorkspaceRoot(): void
    {
        $environment = $this->createEnvironment('/tmp/dev-tools-artifacts');

        self::assertSame('/tmp/dev-tools-artifacts', ManagedWorkspace::getWorkspaceRoot(environment: $environment));
        self::assertSame(
            '/tmp/dev-tools-artifacts/metrics',
            ManagedWorkspace::getOutputDirectory(ManagedWorkspace::METRICS, 'tmp', $environment),
        );
        self::assertSame(
            '/tmp/dev-tools-artifacts/cache/rector',
            ManagedWorkspace::getCacheDirectory(ManagedWorkspace::RECTOR, 'tmp', $environment),
        );
    }

    /**
     * @param string|null $value
     *
     * @return EnvironmentInterface
     */
    private function createEnvironment(?string $value = null): EnvironmentInterface
    {
        $environment = $this->prophesize(EnvironmentInterface::class);

        $environment->get(ManagedWorkspace::ENV_WORKSPACE_DIR)
            ->willReturn($value);

        return $environment->reveal();
    }
}
