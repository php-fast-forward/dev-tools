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

use FastForward\DevTools\Changelog\BootstrapperInterface;
use FastForward\DevTools\Changelog\BootstrapResult;
use FastForward\DevTools\Console\Command\ChangelogInitCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(ChangelogInitCommand::class)]
#[UsesClass(BootstrapResult::class)]
final class ChangelogInitCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<BootstrapperInterface>
     */
    private ObjectProphecy $bootstrapper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->bootstrapper = $this->prophesize(BootstrapperInterface::class);

        parent::setUp();
    }

    /**
     * @return ChangelogInitCommand
     */
    protected function getCommandClass(): ChangelogInitCommand
    {
        return new ChangelogInitCommand($this->bootstrapper->reveal(), $this->filesystem->reveal());
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'changelog:init';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Bootstraps keep-a-changelog configuration and CHANGELOG.md.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command creates .keep-a-changelog.ini, generates CHANGELOG.md from git release history when missing, and restores an Unreleased section when necessary.';
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReportCreatedArtifacts(): void
    {
        $this->bootstrapper->bootstrap(Argument::type('string'))
            ->willReturn(new BootstrapResult(true, true, false));
        $this->output->writeln(Argument::type('string'))->shouldBeCalledTimes(2);

        self::assertSame(ChangelogInitCommand::SUCCESS, $this->invokeExecute());
    }
}
