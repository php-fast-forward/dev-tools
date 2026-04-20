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

namespace FastForward\DevTools\Tests\Agent\Sync;

use FastForward\DevTools\Agent\Sync\SynchronizeResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SynchronizeResult::class)]
final class SynchronizeResultTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function addCreatedLinkWillRecordTheLinkName(): void
    {
        $result = new SynchronizeResult();

        $result->addCreatedLink('issue-editor');

        self::assertSame(['issue-editor'], $result->getCreatedLinks());
    }

    /**
     * @return void
     */
    #[Test]
    public function addPreservedLinkWillRecordTheLinkName(): void
    {
        $result = new SynchronizeResult();

        $result->addPreservedLink('issue-editor');

        self::assertSame(['issue-editor'], $result->getPreservedLinks());
    }

    /**
     * @return void
     */
    #[Test]
    public function addRemovedBrokenLinkWillRecordTheLinkName(): void
    {
        $result = new SynchronizeResult();

        $result->addRemovedBrokenLink('issue-editor');

        self::assertSame(['issue-editor'], $result->getRemovedBrokenLinks());
    }

    /**
     * @return void
     */
    #[Test]
    public function markFailedWillSetTheFailedFlag(): void
    {
        $result = new SynchronizeResult();

        self::assertFalse($result->failed());

        $result->markFailed();

        self::assertTrue($result->failed());
    }
}
