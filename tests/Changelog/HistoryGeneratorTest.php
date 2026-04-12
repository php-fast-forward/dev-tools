<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\Changelog;

use FastForward\DevTools\Changelog\CommitClassifierInterface;
use FastForward\DevTools\Changelog\GitReleaseCollectorInterface;
use FastForward\DevTools\Changelog\HistoryGenerator;
use FastForward\DevTools\Changelog\MarkdownRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(HistoryGenerator::class)]
#[UsesClass(MarkdownRenderer::class)]
final class HistoryGeneratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<GitReleaseCollectorInterface>
     */
    private ObjectProphecy $gitReleaseCollector;

    /**
     * @var ObjectProphecy<CommitClassifierInterface>
     */
    private ObjectProphecy $commitClassifier;

    private HistoryGenerator $historyGenerator;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->gitReleaseCollector = $this->prophesize(GitReleaseCollectorInterface::class);
        $this->commitClassifier = $this->prophesize(CommitClassifierInterface::class);
        $this->historyGenerator = new HistoryGenerator(
            $this->gitReleaseCollector->reveal(),
            $this->commitClassifier->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function generateWillRenderCollectedReleaseHistoryAsMarkdown(): void
    {
        $this->gitReleaseCollector->collect('/tmp/project')
            ->willReturn([
                [
                    'version' => '1.0.0',
                    'tag' => 'v1.0.0',
                    'date' => '2026-04-08',
                    'commits' => ['feat: add bootstrap', 'fix: validate changelog'],
                ],
            ]);
        $this->commitClassifier->classify('feat: add bootstrap')
            ->willReturn('Added');
        $this->commitClassifier->normalize('feat: add bootstrap')
            ->willReturn('Add bootstrap');
        $this->commitClassifier->classify('fix: validate changelog')
            ->willReturn('Fixed');
        $this->commitClassifier->normalize('fix: validate changelog')
            ->willReturn('Validate changelog');

        $markdown = $this->historyGenerator->generate('/tmp/project');

        self::assertStringContainsString('## Unreleased - TBD', $markdown);
        self::assertStringContainsString('## 1.0.0 - 2026-04-08', $markdown);
        self::assertStringContainsString('- Add bootstrap', $markdown);
        self::assertStringContainsString('- Validate changelog', $markdown);
    }
}
