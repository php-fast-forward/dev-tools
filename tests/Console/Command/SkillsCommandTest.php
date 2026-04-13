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

namespace FastForward\DevTools\Tests\Console\Command;

use Composer\IO\IOInterface;
use FastForward\DevTools\Agent\Skills\SkillsSynchronizer;
use FastForward\DevTools\Agent\Skills\SynchronizeResult;
use FastForward\DevTools\Console\Command\SkillsCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

use function Safe\getcwd;

#[CoversClass(SkillsCommand::class)]
#[UsesClass(SkillsSynchronizer::class)]
#[UsesClass(SynchronizeResult::class)]
final class SkillsCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SkillsSynchronizer>
     */
    private ObjectProphecy $synchronizer;

    /**
     * @var ObjectProphecy<IOInterface>
     */
    private ObjectProphecy $io;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->synchronizer = $this->prophesize(SkillsSynchronizer::class);
        $this->io = $this->prophesize(IOInterface::class);

        parent::setUp();

        $this->application->getIO()
            ->willReturn($this->io->reveal());
    }

    /**
     * @return SkillsCommand
     */
    protected function getCommandClass(): SkillsCommand
    {
        return new SkillsCommand($this->synchronizer->reveal(), $this->filesystem->reveal());
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'skills';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Synchronizes Fast Forward skills into .agents/skills directory.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command ensures the consumer repository contains linked Fast Forward skills '
            . 'by creating symlinks to the packaged skills and removing broken links.';
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillFailWhenPackagedSkillsDirectoryDoesNotExist(): void
    {
        $skillsPath = getcwd() . '/.agents/skills';

        $this->filesystem->exists($skillsPath)
            ->willReturn(false);
        $this->output->writeln('<info>Starting skills synchronization...</info>')
            ->shouldBeCalledOnce();
        $this->output->writeln('<comment>No packaged skills found at: ' . $skillsPath . '</comment>')
            ->shouldBeCalledOnce();
        $this->synchronizer->setLogger(Argument::cetera())->shouldNotBeCalled();
        $this->synchronizer->synchronize(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(SkillsCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCreateSkillsDirectoryWhenItDoesNotExist(): void
    {
        $skillsPath = getcwd() . '/.agents/skills';
        $result = new SynchronizeResult();

        $this->filesystem->exists($skillsPath)
            ->willReturn(true, false);
        $this->filesystem->mkdir($skillsPath)
            ->shouldBeCalledOnce();
        $this->synchronizer->setLogger($this->io->reveal())
            ->shouldBeCalledOnce();
        $this->synchronizer->synchronize($skillsPath, $skillsPath)
            ->willReturn($result)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Starting skills synchronization...</info>')
            ->shouldBeCalledOnce();
        $this->output->writeln('<info>Created .agents/skills directory.</info>')
            ->shouldBeCalledOnce();
        $this->output->writeln('<info>Skills synchronization completed successfully.</info>')
            ->shouldBeCalledOnce();

        self::assertSame(SkillsCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenSynchronizerFails(): void
    {
        $skillsPath = getcwd() . '/.agents/skills';
        $result = new SynchronizeResult();
        $result->markFailed();

        $this->filesystem->exists($skillsPath)
            ->willReturn(true, true);
        $this->synchronizer->setLogger($this->io->reveal())
            ->shouldBeCalledOnce();
        $this->synchronizer->synchronize($skillsPath, $skillsPath)
            ->willReturn($result)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Starting skills synchronization...</info>')
            ->shouldBeCalledOnce();
        $this->output->writeln('<error>Skills synchronization failed.</error>')
            ->shouldBeCalledOnce();

        self::assertSame(SkillsCommand::FAILURE, $this->invokeExecute());
    }
}
