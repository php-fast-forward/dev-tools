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

namespace FastForward\DevTools\Tests\Command;

use FastForward\DevTools\Changelog\UnreleasedEntryCheckerInterface;
use FastForward\DevTools\Console\Command\ChangelogCheckCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(ChangelogCheckCommand::class)]
final class ChangelogCheckCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<UnreleasedEntryCheckerInterface>
     */
    private ObjectProphecy $unreleasedEntryChecker;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->unreleasedEntryChecker = $this->prophesize(UnreleasedEntryCheckerInterface::class);

        parent::setUp();
    }

    /**
     * @return ChangelogCheckCommand
     */
    protected function getCommandClass(): ChangelogCheckCommand
    {
        return new ChangelogCheckCommand($this->unreleasedEntryChecker->reveal(), $this->filesystem->reveal());
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'changelog:check';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Checks whether CHANGELOG.md contains meaningful unreleased entries.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command validates the current Unreleased section and may compare it against a base git reference to enforce pull request changelog updates.';
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenUnreleasedEntriesExist(): void
    {
        $this->unreleasedEntryChecker->hasPendingChanges(Argument::type('string'), null)
            ->willReturn(true);
        $this->output->writeln(Argument::containingString('ready for review'))
            ->shouldBeCalled();

        self::assertSame(ChangelogCheckCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenUnreleasedEntriesAreMissing(): void
    {
        $this->unreleasedEntryChecker->hasPendingChanges(Argument::type('string'), null)
            ->willReturn(false);
        $this->output->writeln(Argument::containingString('must add a meaningful entry'))
            ->shouldBeCalled();

        self::assertSame(ChangelogCheckCommand::FAILURE, $this->invokeExecute());
    }
}
