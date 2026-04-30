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
use FastForward\DevTools\Path\DevToolsPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HookContentRenderer::class)]
#[UsesClass(DevToolsPathResolver::class)]
final class HookContentRendererTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function renderWillReplaceTheManagedGrumPhpConfigPlaceholder(): void
    {
        $renderer = new HookContentRenderer();

        self::assertSame(
            'DEVTOOLS_GRUMPHP_CONFIG=' . escapeshellarg(DevToolsPathResolver::getPackagePath('grumphp.yml')),
            $renderer->render('DEVTOOLS_GRUMPHP_CONFIG=' . HookContentRenderer::MANAGED_GRUMPHP_CONFIG_PLACEHOLDER),
        );
    }
}
