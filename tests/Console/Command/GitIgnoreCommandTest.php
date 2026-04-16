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

use FastForward\DevTools\Console\Command\GitIgnoreCommand;
use FastForward\DevTools\GitIgnore\GitIgnoreInterface;
use FastForward\DevTools\GitIgnore\MergerInterface;
use FastForward\DevTools\GitIgnore\ReaderInterface;
use FastForward\DevTools\GitIgnore\WriterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(GitIgnoreCommand::class)]
final class GitIgnoreCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MergerInterface>
     */
    private ObjectProphecy $merger;

    /**
     * @var ObjectProphecy<ReaderInterface>
     */
    private ObjectProphecy $reader;

    /**
     * @var ObjectProphecy<WriterInterface>
     */
    private ObjectProphecy $writer;

    /**
     * @var ObjectProphecy<FileLocatorInterface>
     */
    private ObjectProphecy $fileLocator;

    /**
     * @var ObjectProphecy<InputInterface>
     */
    private ObjectProphecy $input;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    /**
     * @var ObjectProphecy<GitIgnoreInterface>
     */
    private ObjectProphecy $gitIgnoreSource;

    /**
     * @var ObjectProphecy<GitIgnoreInterface>
     */
    private ObjectProphecy $gitIgnoreTarget;

    /**
     * @var ObjectProphecy<GitIgnoreInterface>
     */
    private ObjectProphecy $gitIgnoreMerged;

    private GitIgnoreCommand $command;

    private const string SOURCE_PATH = '/path/to/source/.gitignore';

    private const string TARGET_PATH = '/path/to/target/.gitignore';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->fileLocator->locate(Argument::cetera())
            ->willReturn('/default/path/to/.gitignore');

        $this->merger = $this->prophesize(MergerInterface::class);
        $this->reader = $this->prophesize(ReaderInterface::class);
        $this->writer = $this->prophesize(WriterInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->gitIgnoreSource = $this->prophesize(GitIgnoreInterface::class);
        $this->gitIgnoreTarget = $this->prophesize(GitIgnoreInterface::class);
        $this->gitIgnoreMerged = $this->prophesize(GitIgnoreInterface::class);

        $this->input->getOption('source')
            ->willReturn(self::SOURCE_PATH);
        $this->input->getOption('target')
            ->willReturn(self::TARGET_PATH);

        $this->reader->read(self::SOURCE_PATH)
            ->willReturn($this->gitIgnoreSource->reveal());
        $this->reader->read(self::TARGET_PATH)
            ->willReturn($this->gitIgnoreTarget->reveal());

        $this->merger->merge($this->gitIgnoreSource->reveal(), $this->gitIgnoreTarget->reveal())
            ->willReturn($this->gitIgnoreMerged->reveal());

        $this->writer->write(Argument::any());
        $this->output->writeln(Argument::any());

        $this->command = new GitIgnoreCommand(
            $this->merger->reveal(),
            $this->reader->reveal(),
            $this->writer->reveal(),
            $this->fileLocator->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('gitignore', $this->command->getName());
        self::assertSame('Merges and synchronizes .gitignore files.', $this->command->getDescription());
        self::assertSame(
            "This command merges the canonical .gitignore from dev-tools with the project's existing .gitignore.",
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillHaveExpectedOptions(): void
    {
        $definition = $this->command->getDefinition();

        self::assertTrue($definition->hasOption('source'));
        self::assertTrue($definition->hasOption('target'));
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenMergeSucceeds(): void
    {
        $this->writer->write($this->gitIgnoreMerged->reveal())
            ->shouldBeCalled();

        $this->output->writeln('<info>Merging .gitignore files...</info>')
            ->shouldBeCalled();
        $this->output->writeln('<info>Successfully merged .gitignore file.</info>')
            ->shouldBeCalled();

        $result = $this->executeCommand();

        self::assertSame(GitIgnoreCommand::SUCCESS, $result);
    }

    /**
     * @return int
     */
    private function executeCommand(): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
