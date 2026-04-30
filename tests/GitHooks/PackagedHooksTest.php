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

namespace FastForward\DevTools\Tests\GitHooks;

use FastForward\DevTools\GitHooks\HookContentRenderer;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Safe\file_get_contents;

#[CoversNothing]
final class PackagedHooksTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function packagedPreCommitHookWillPreferProjectConfigAndFallbackToTheManagedPlaceholder(): void
    {
        $contents = file_get_contents(\dirname(__DIR__, 2) . '/resources/git-hooks/pre-commit');

        self::assertStringContainsString("./grumphp.yml' ]; then", $contents);
        self::assertStringContainsString(
            'DEVTOOLS_GRUMPHP_CONFIG=' . HookContentRenderer::MANAGED_GRUMPHP_CONFIG_PLACEHOLDER,
            $contents,
        );
        self::assertStringContainsString("'--config' \"\${GRUMPHP_CONFIG_FILE}\" 'git:pre-commit'", $contents);
    }

    /**
     * @return void
     */
    #[Test]
    public function packagedCommitMsgHookWillPreferProjectConfigAndFallbackToTheManagedPlaceholder(): void
    {
        $contents = file_get_contents(\dirname(__DIR__, 2) . '/resources/git-hooks/commit-msg');

        self::assertStringContainsString("./grumphp.yml' ]; then", $contents);
        self::assertStringContainsString(
            'DEVTOOLS_GRUMPHP_CONFIG=' . HookContentRenderer::MANAGED_GRUMPHP_CONFIG_PLACEHOLDER,
            $contents,
        );
        self::assertStringContainsString("'--config' \"\${GRUMPHP_CONFIG_FILE}\" 'git:commit-msg'", $contents);
    }
}
