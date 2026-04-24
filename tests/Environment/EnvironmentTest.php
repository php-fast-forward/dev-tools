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

namespace FastForward\DevTools\Tests\Environment;

use FastForward\DevTools\Environment\Environment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Safe\putenv;

#[CoversClass(Environment::class)]
final class EnvironmentTest extends TestCase
{
    private Environment $environment;

    private string|false $previousValue;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->environment = new Environment();
        $this->previousValue = getenv('DEV_TOOLS_ENVIRONMENT_READER_TEST');
        putenv('DEV_TOOLS_ENVIRONMENT_READER_TEST');
    }

    /**
     * @return void
     */
    #[Test]
    public function getReturnsNullForMissingEnvironmentVariable(): void
    {
        self::assertNull($this->environment->get('DEV_TOOLS_ENVIRONMENT_READER_TEST'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getReturnsDefaultForMissingEnvironmentVariable(): void
    {
        self::assertSame('fallback', $this->environment->get('DEV_TOOLS_ENVIRONMENT_READER_TEST', 'fallback'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getReturnsEnvironmentVariableValue(): void
    {
        putenv('DEV_TOOLS_ENVIRONMENT_READER_TEST=enabled');

        self::assertSame('enabled', $this->environment->get('DEV_TOOLS_ENVIRONMENT_READER_TEST'));
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if (false === $this->previousValue) {
            putenv('DEV_TOOLS_ENVIRONMENT_READER_TEST');

            return;
        }

        putenv('DEV_TOOLS_ENVIRONMENT_READER_TEST=' . $this->previousValue);
    }
}
