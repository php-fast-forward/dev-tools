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

namespace FastForward\DevTools\Tests\Console\Command;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Safe\glob;
use function Safe\preg_match_all;
use function Safe\file_get_contents;

#[CoversNothing]
final class CommandAttributeCompatibilityTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function asCommandAttributesWillNotUseTheHelpNamedParameter(): void
    {
        foreach (glob(__DIR__ . '/../../../src/Console/Command/*.php') as $commandFile) {
            self::assertIsString($commandFile);

            $content = file_get_contents($commandFile);

            preg_match_all('/#\[AsCommand\((.*?)\)\]/s', $content, $matches);

            foreach ($matches[0] as $attribute) {
                self::assertStringNotContainsString(
                    'help:',
                    $attribute,
                    \sprintf(
                        'The command attribute in %s MUST remain compatible with Composer-discovered Symfony Console versions.',
                        basename($commandFile)
                    ),
                );
            }
        }
    }
}
