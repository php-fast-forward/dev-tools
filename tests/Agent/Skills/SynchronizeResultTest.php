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

namespace FastForward\DevTools\Tests\Agent\Skills;

use FastForward\DevTools\Agent\Skills\SynchronizeResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SynchronizeResult::class)]
final class SynchronizeResultTest extends TestCase
{
    private SynchronizeResult $result;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->result = new SynchronizeResult();
    }

    /**
     * @return void
     */
    #[Test]
    public function newResultWillHaveEmptyListsAndNotFailed(): void
    {
        self::assertSame([], $this->result->getCreatedLinks());
        self::assertSame([], $this->result->getPreservedLinks());
        self::assertSame([], $this->result->getRemovedBrokenLinks());
        self::assertFalse($this->result->failed());
    }

    /**
     * @return void
     */
    #[Test]
    public function addCreatedLinkWillAddToCreatedList(): void
    {
        $this->result->addCreatedLink('skill-one');
        $this->result->addCreatedLink('skill-two');

        self::assertSame(['skill-one', 'skill-two'], $this->result->getCreatedLinks());
    }

    /**
     * @return void
     */
    #[Test]
    public function addPreservedLinkWillAddToPreservedList(): void
    {
        $this->result->addPreservedLink('existing-skill');

        self::assertSame(['existing-skill'], $this->result->getPreservedLinks());
    }

    /**
     * @return void
     */
    #[Test]
    public function addRemovedBrokenLinkWillAddToRemovedList(): void
    {
        $this->result->addRemovedBrokenLink('broken-skill');

        self::assertSame(['broken-skill'], $this->result->getRemovedBrokenLinks());
    }

    /**
     * @return void
     */
    #[Test]
    public function markFailedWillSetFailedFlag(): void
    {
        self::assertFalse($this->result->failed());

        $this->result->markFailed();

        self::assertTrue($this->result->failed());
    }

    /**
     * @return void
     */
    #[Test]
    public function failedWillReturnFalseAfterMultipleOperations(): void
    {
        $this->result->addCreatedLink('new-skill');
        $this->result->addPreservedLink('old-skill');
        $this->result->addRemovedBrokenLink('broken-skill');

        self::assertFalse($this->result->failed());
        self::assertSame(['new-skill'], $this->result->getCreatedLinks());
        self::assertSame(['old-skill'], $this->result->getPreservedLinks());
        self::assertSame(['broken-skill'], $this->result->getRemovedBrokenLinks());
    }
}
