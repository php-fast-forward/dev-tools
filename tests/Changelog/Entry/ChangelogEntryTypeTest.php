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

namespace FastForward\DevTools\Tests\Changelog\Entry;

use FastForward\DevTools\Changelog\Entry\ChangelogEntryType;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChangelogEntryType::class)]
final class ChangelogEntryTypeTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function orderedWillReturnKeepAChangelogSectionOrder(): void
    {
        self::assertSame(
            [
                ChangelogEntryType::Added,
                ChangelogEntryType::Changed,
                ChangelogEntryType::Deprecated,
                ChangelogEntryType::Removed,
                ChangelogEntryType::Fixed,
                ChangelogEntryType::Security,
            ],
            ChangelogEntryType::ordered(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function fromInputWillNormalizeSupportedValues(): void
    {
        self::assertSame(ChangelogEntryType::Fixed, ChangelogEntryType::fromInput(' fixed '));
        self::assertSame(ChangelogEntryType::Security, ChangelogEntryType::fromInput('security'));
    }

    /**
     * @return void
     */
    #[Test]
    public function fromInputWillRejectUnsupportedValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported changelog type "unknown".');

        ChangelogEntryType::fromInput('unknown');
    }
}
