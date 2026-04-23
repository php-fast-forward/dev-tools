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

namespace FastForward\DevTools\Tests\Changelog;

use FastForward\DevTools\Changelog\DependabotChangelogEntryMessageResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DependabotChangelogEntryMessageResolver::class)]
final class DependabotChangelogEntryMessageResolverTest extends TestCase
{
    #[Test]
    public function resolveWillAppendThePullRequestNumberWhenTheTitleHasNoSuffix(): void
    {
        $resolver = new DependabotChangelogEntryMessageResolver();

        self::assertSame(
            'GitHub Actions(deps): Bump actions/github-script from 8 to 9 (#183)',
            $resolver->resolve("  GitHub Actions(deps):  Bump actions/github-script from 8 to 9 \n", 183),
        );
    }

    #[Test]
    public function resolveWillPreserveAnExistingPullRequestSuffix(): void
    {
        $resolver = new DependabotChangelogEntryMessageResolver();

        self::assertSame(
            'Bump symfony/console from 7.3.0 to 7.3.1 (#123)',
            $resolver->resolve('Bump symfony/console from 7.3.0 to 7.3.1 (#123)', 123),
        );
    }

    #[Test]
    public function resolveWillTrimTrailingPunctuationBeforeAppendingTheSuffix(): void
    {
        $resolver = new DependabotChangelogEntryMessageResolver();

        self::assertSame(
            'Bump peter-evans/create-pull-request from 7 to 8 (#181)',
            $resolver->resolve('Bump peter-evans/create-pull-request from 7 to 8.', 181),
        );
    }
}
