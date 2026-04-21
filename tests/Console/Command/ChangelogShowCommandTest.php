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

namespace FastForward\DevTools\Tests\Console\Command;

use InvalidArgumentException;
use FastForward\DevTools\Changelog\Manager\ChangelogManagerInterface;
use FastForward\DevTools\Console\Command\ChangelogShowCommand;
use FastForward\DevTools\Console\Output\CommandResult;
use FastForward\DevTools\Console\Output\CommandResultRendererInterface;
use FastForward\DevTools\Console\Output\OutputFormat;
use FastForward\DevTools\Console\Output\OutputFormatResolverInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(CommandResult::class)]
#[CoversClass(ChangelogShowCommand::class)]
#[CoversClass(OutputFormat::class)]
final class ChangelogShowCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ChangelogManagerInterface>
     */
    private ObjectProphecy $changelogManager;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<OutputFormatResolverInterface>
     */
    private ObjectProphecy $outputFormatResolver;

    /**
     * @var ObjectProphecy<CommandResultRendererInterface>
     */
    private ObjectProphecy $commandResultRenderer;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ChangelogShowCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->changelogManager = $this->prophesize(ChangelogManagerInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->outputFormatResolver = $this->prophesize(OutputFormatResolverInterface::class);
        $this->commandResultRenderer = $this->prophesize(CommandResultRendererInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->input->getOption('file')
            ->willReturn('CHANGELOG.md');
        $this->input->getArgument('version')
            ->willReturn('1.2.0');
        $this->filesystem->getAbsolutePath('CHANGELOG.md')
            ->willReturn('/repo/CHANGELOG.md');

        $this->command = new ChangelogShowCommand(
            $this->filesystem->reveal(),
            $this->changelogManager->reveal(),
            $this->outputFormatResolver->reveal(),
            $this->commandResultRenderer->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillWriteRawReleaseNotesInTextMode(): void
    {
        $this->outputFormatResolver->resolve($this->input->reveal())
            ->willReturn(OutputFormat::TEXT);
        $this->changelogManager->renderReleaseNotes('/repo/CHANGELOG.md', '1.2.0')
            ->willReturn("### Added\n\n- Ship it\n")
            ->shouldBeCalledOnce();
        $this->output->write("### Added\n\n- Ship it\n")
            ->shouldBeCalledOnce();
        $this->commandResultRenderer->render(Argument::cetera())
            ->shouldNotBeCalled();

        self::assertSame(ChangelogShowCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRenderStructuredJsonPayloadInJsonMode(): void
    {
        $this->outputFormatResolver->resolve($this->input->reveal())
            ->willReturn(OutputFormat::JSON);
        $this->changelogManager->renderReleaseNotes('/repo/CHANGELOG.md', '1.2.0')
            ->willReturn("### Added\n\n- Ship it\n")
            ->shouldBeCalledOnce();
        $this->output->write(Argument::cetera())
            ->shouldNotBeCalled();
        $this->commandResultRenderer->render(
            $this->output->reveal(),
            Argument::that(static fn(CommandResult $result): bool => 'success' === $result->status
                && "### Added\n\n- Ship it\n" === $result->message
                && [
                    'command' => 'changelog:show',
                    'file' => 'CHANGELOG.md',
                    'version' => '1.2.0',
                    'release_notes' => "### Added\n\n- Ship it\n",
                ] === $result->context),
            OutputFormat::JSON,
        )->shouldBeCalledOnce();

        self::assertSame(ChangelogShowCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenFormatIsInvalid(): void
    {
        $this->outputFormatResolver->resolve($this->input->reveal())
            ->willThrow(new InvalidArgumentException('The --output-format option MUST be one of: text, json.'));
        $this->changelogManager->renderReleaseNotes(Argument::cetera())
            ->shouldNotBeCalled();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The --output-format option MUST be one of: text, json.');

        $this->invokeExecute();
    }

    /**
     * @return int
     */
    private function invokeExecute(): int
    {
        return (new ReflectionMethod($this->command, 'execute'))
            ->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
