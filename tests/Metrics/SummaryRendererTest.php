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

namespace FastForward\DevTools\Tests\Metrics;

use FastForward\DevTools\Metrics\Report;
use FastForward\DevTools\Metrics\SummaryRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Report::class)]
#[CoversClass(SummaryRenderer::class)]
final class SummaryRendererTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function renderWillFormatTheExpectedSummary(): void
    {
        $renderer = new SummaryRenderer();

        self::assertSame(
            "<info>Metrics summary</info>\n"
            . "Average cyclomatic complexity by class: 4.50\n"
            . "Average maintainability index by class: 78.25\n"
            . "Classes analyzed: 8\n"
            . "Functions analyzed: 3",
            $renderer->render(new Report(4.5, 78.25, 8, 3)),
        );
    }
}
