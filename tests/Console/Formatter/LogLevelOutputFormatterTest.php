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

namespace FastForward\DevTools\Tests\Console\Formatter;

use FastForward\DevTools\Console\Formatter\LogLevelOutputFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

#[CoversClass(LogLevelOutputFormatter::class)]
final class LogLevelOutputFormatterTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function constructorWillEnableDecoratedOutputAndRegisterExpectedStyles(): void
    {
        $formatter = new LogLevelOutputFormatter();

        self::assertTrue($formatter->isDecorated());
        self::assertTrue($formatter->hasStyle('emergency'));
        self::assertTrue($formatter->hasStyle('alert'));
        self::assertTrue($formatter->hasStyle('critical'));
        self::assertTrue($formatter->hasStyle('error'));
        self::assertTrue($formatter->hasStyle('warning'));
        self::assertTrue($formatter->hasStyle('notice'));
        self::assertTrue($formatter->hasStyle('info'));
        self::assertTrue($formatter->hasStyle('debug'));
    }

    /**
     * @return void
     */
    #[Test]
    public function formatWillApplyTheRegisteredInfoStyle(): void
    {
        $formatter = new LogLevelOutputFormatter();
        $expected = (new OutputFormatterStyle('green'))->apply('[INFO] Ready');

        self::assertSame($expected, $formatter->format('<info>[INFO] Ready</info>'));
    }

    /**
     * @return void
     */
    #[Test]
    public function formatWillApplyTheRegisteredAlertStyle(): void
    {
        $formatter = new LogLevelOutputFormatter();
        $expected = (new OutputFormatterStyle('red', null, ['bold']))->apply('[ALERT] Stop');

        self::assertSame($expected, $formatter->format('<alert>[ALERT] Stop</alert>'));
    }
}
