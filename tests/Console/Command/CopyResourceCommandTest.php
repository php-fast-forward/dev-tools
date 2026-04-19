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

use FastForward\DevTools\Console\Command\CopyResourceCommand;
use FastForward\DevTools\Filesystem\FinderFactoryInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Resource\FileDiff;
use FastForward\DevTools\Resource\FileDiffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

use function Safe\mkdir;
use function Safe\file_put_contents;
use function Safe\unlink;
use function Safe\rmdir;

#[CoversClass(CopyResourceCommand::class)]
#[UsesClass(FileDiff::class)]
final class CopyResourceCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $finderFactory;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $fileDiffer;

    private CopyResourceCommand $command;

    private string $sourceDirectory;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->sourceDirectory = sys_get_temp_dir() . '/copy-resource-command-test-' . bin2hex(random_bytes(4));
        mkdir($this->sourceDirectory . '/nested', 0o777, true);
        file_put_contents($this->sourceDirectory . '/nested/example.yml', 'name: example');

        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->finderFactory = $this->prophesize(FinderFactoryInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->fileDiffer = $this->prophesize(FileDiffer::class);
        $this->output->isDecorated()
            ->willReturn(false);
        $this->output->writeln(Argument::any());
        $this->input->getOption('dry-run')
            ->willReturn(false);
        $this->input->getOption('check')
            ->willReturn(false);
        $this->input->getOption('interactive')
            ->willReturn(false);
        $this->fileDiffer->formatForConsole(Argument::cetera())
            ->will(static fn(array $arguments): ?string => $arguments[0]);

        $this->command = new CopyResourceCommand(
            $this->filesystem->reveal(),
            $this->fileLocator->reveal(),
            $this->finderFactory->reveal(),
            $this->fileDiffer->reveal(),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        if (is_dir($this->sourceDirectory)) {
            unlink($this->sourceDirectory . '/nested/example.yml');
            rmdir($this->sourceDirectory . '/nested');
            rmdir($this->sourceDirectory);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('copy-resource', $this->command->getName());
        self::assertSame(
            'Copies a file or directory resource into the current project.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command copies a configured source file or every file in a source directory into the target path.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCopyDirectoryContentsIntoTarget(): void
    {
        $this->input->getOption('source')
            ->willReturn('resources/github-actions');
        $this->input->getOption('target')
            ->willReturn('.github/workflows');
        $this->input->getOption('overwrite')
            ->willReturn(false);

        $this->fileLocator->locate('resources/github-actions')
            ->willReturn($this->sourceDirectory);
        $this->finderFactory->create()
            ->willReturn(new Finder())
            ->shouldBeCalledOnce();
        $this->filesystem->getAbsolutePath('.github/workflows')
            ->willReturn('/app/.github/workflows');
        $this->filesystem->exists('/app/.github/workflows/nested/example.yml')
            ->willReturn(false);
        $this->filesystem->copy(
            Argument::containingString('/nested/example.yml'),
            '/app/.github/workflows/nested/example.yml',
            false,
        )->shouldBeCalledOnce();
        $this->output->writeln(Argument::containingString('Copied resource'))
            ->shouldBeCalled();

        self::assertSame(CopyResourceCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillShowDiffBeforeOverwritingChangedFile(): void
    {
        $this->input->getOption('source')
            ->willReturn('.editorconfig');
        $this->input->getOption('target')
            ->willReturn('.editorconfig');
        $this->input->getOption('overwrite')
            ->willReturn(true);

        $this->fileLocator->locate('.editorconfig')
            ->willReturn('/package/.editorconfig');
        $this->filesystem->getAbsolutePath('.editorconfig')
            ->willReturn('/project/.editorconfig');
        $this->filesystem->exists('/project/.editorconfig')
            ->willReturn(true);

        $this->fileDiffer->diff('/package/.editorconfig', '/project/.editorconfig')
            ->willReturn(new FileDiff(
                FileDiff::STATUS_CHANGED,
                'Overwriting resource /project/.editorconfig from /package/.editorconfig.',
                "--- Current: /project/.editorconfig\n+++ Source: /package/.editorconfig\n@@ -1 +1 @@\n-old\n+new"
            ))
            ->shouldBeCalledOnce();

        $this->output->writeln(
            '<comment>Overwriting resource /project/.editorconfig from /package/.editorconfig.</comment>'
        )
            ->shouldBeCalledOnce();
        $this->output->writeln(
            "--- Current: /project/.editorconfig\n+++ Source: /package/.editorconfig\n@@ -1 +1 @@\n-old\n+new"
        )
            ->shouldBeCalledOnce();
        $this->filesystem->copy('/package/.editorconfig', '/project/.editorconfig', true)
            ->shouldBeCalledOnce();
        $this->output->writeln('<info>Copied resource /project/.editorconfig.</info>')
            ->shouldBeCalledOnce();

        self::assertSame(CopyResourceCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReportUnchangedOverwriteAsNoOp(): void
    {
        $this->input->getOption('source')
            ->willReturn('.editorconfig');
        $this->input->getOption('target')
            ->willReturn('.editorconfig');
        $this->input->getOption('overwrite')
            ->willReturn(true);

        $this->fileLocator->locate('.editorconfig')
            ->willReturn('/package/.editorconfig');
        $this->filesystem->getAbsolutePath('.editorconfig')
            ->willReturn('/project/.editorconfig');
        $this->filesystem->exists('/project/.editorconfig')
            ->willReturn(true);

        $this->fileDiffer->diff('/package/.editorconfig', '/project/.editorconfig')
            ->willReturn(new FileDiff(
                FileDiff::STATUS_UNCHANGED,
                'Target /project/.editorconfig already matches source /package/.editorconfig; overwrite skipped.',
            ))
            ->shouldBeCalledOnce();

        $this->output->writeln(
            '<comment>Target /project/.editorconfig already matches source /package/.editorconfig; overwrite skipped.</comment>'
        )
            ->shouldBeCalledOnce();
        $this->filesystem->copy(Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled();
        $this->output->writeln('<info>Copied resource /project/.editorconfig.</info>')
            ->shouldNotBeCalled();

        self::assertSame(CopyResourceCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillHandleBinaryOverwriteGracefully(): void
    {
        $this->input->getOption('source')
            ->willReturn('.editorconfig');
        $this->input->getOption('target')
            ->willReturn('.editorconfig');
        $this->input->getOption('overwrite')
            ->willReturn(true);

        $this->fileLocator->locate('.editorconfig')
            ->willReturn('/package/.editorconfig');
        $this->filesystem->getAbsolutePath('.editorconfig')
            ->willReturn('/project/.editorconfig');
        $this->filesystem->exists('/project/.editorconfig')
            ->willReturn(true);

        $this->fileDiffer->diff('/package/.editorconfig', '/project/.editorconfig')
            ->willReturn(new FileDiff(
                FileDiff::STATUS_BINARY,
                'Target /project/.editorconfig will be overwritten from /package/.editorconfig, but a text diff is unavailable for binary content.',
            ))
            ->shouldBeCalledOnce();

        $this->output->writeln(
            '<comment>Target /project/.editorconfig will be overwritten from /package/.editorconfig, but a text diff is unavailable for binary content.</comment>'
        )
            ->shouldBeCalledOnce();
        $this->filesystem->copy('/package/.editorconfig', '/project/.editorconfig', true)
            ->shouldBeCalledOnce();
        $this->output->writeln('<info>Copied resource /project/.editorconfig.</info>')
            ->shouldBeCalledOnce();

        self::assertSame(CopyResourceCommand::SUCCESS, $this->executeCommand());
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
