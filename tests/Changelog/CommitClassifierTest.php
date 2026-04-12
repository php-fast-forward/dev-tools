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

use FastForward\DevTools\Changelog\CommitClassifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommitClassifier::class)]
final class CommitClassifierTest extends TestCase
{
    private CommitClassifier $commitClassifier;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->commitClassifier = new CommitClassifier();
    }

    /**
     * @return void
     */
    #[Test]
    public function classifyWillMapSupportedCommitPrefixesToExpectedSections(): void
    {
        self::assertSame('Added', $this->commitClassifier->classify('feat(command): add changelog command'));
        self::assertSame('Fixed', $this->commitClassifier->classify('fix(workflow): guard against missing token'));
        self::assertSame('Removed', $this->commitClassifier->classify('remove deprecated bootstrap path'));
        self::assertSame('Security', $this->commitClassifier->classify('fix: patch security token leak'));
        self::assertSame('Changed', $this->commitClassifier->classify('docs: explain changelog workflow'));
    }

    /**
     * @return void
     */
    #[Test]
    public function normalizeWillStripConventionalPrefixesAndBracketedAreas(): void
    {
        self::assertSame(
            'Add changelog bootstrap command (#28)',
            $this->commitClassifier->normalize('[command] feat(changelog): add changelog bootstrap command (#28)'),
        );
    }
}
