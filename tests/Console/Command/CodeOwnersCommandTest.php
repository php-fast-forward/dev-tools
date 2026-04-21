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

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use FastForward\DevTools\CodeOwners\CodeOwnersGenerator;
use FastForward\DevTools\Console\Command\CodeOwnersCommand;
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
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(CodeOwnersCommand::class)]
#[UsesClass(FileDiff::class)]
final class CodeOwnersCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CodeOwnersGenerator>
     */
    private ObjectProphecy $generator;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<InputInterface>
     */
    private ObjectProphecy $input;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    /**
     * @var ObjectProphecy<FileDiffer>
     */
    private ObjectProphecy $fileDiffer;

    /**
     * @var ObjectProphecy<LoggerInterface>
     */
    private ObjectProphecy $logger;

    /**
     * @var ObjectProphecy<QuestionHelper>
     */
    private ObjectProphecy $questionHelper;

    private CodeOwnersCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = $this->prophesize(CodeOwnersGenerator::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->fileDiffer = $this->prophesize(FileDiffer::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->questionHelper = $this->prophesize(QuestionHelper::class);

        $this->input->getOption('file')
            ->willReturn('.github/CODEOWNERS');
        $this->input->getOption('overwrite')
            ->willReturn(false);
        $this->input->getOption('dry-run')
            ->willReturn(false);
        $this->input->getOption('check')
            ->willReturn(false);
        $this->input->getOption('interactive')
            ->willReturn(false);
        $this->input->isInteractive()
            ->willReturn(false);

        $this->output->isDecorated()
            ->willReturn(false);
        $this->output->writeln(Argument::any());
        $this->fileDiffer->formatForConsole(Argument::cetera())->willReturn(null);
        $this->logger->info(Argument::cetera())->will(static function (): void {});
        $this->logger->notice(Argument::cetera())->will(static function (): void {});
        $this->logger->error(Argument::cetera())->will(static function (): void {});
        $this->questionHelper->getName()
            ->willReturn('question');
        $this->questionHelper->setHelperSet(Argument::type(HelperSet::class))
            ->shouldBeCalled();

        $this->command = new CodeOwnersCommand(
            $this->generator->reveal(),
            $this->filesystem->reveal(),
            $this->fileDiffer->reveal(),
            $this->logger->reveal(),
        );
        $this->command->setHelperSet(new HelperSet([
            'question' => $this->questionHelper->reveal(),
        ]));
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('codeowners', $this->command->getName());
        self::assertSame(
            'Generates .github/CODEOWNERS from local project metadata.',
            $this->command->getDescription(),
        );
        self::assertSame(
            'This command infers CODEOWNERS entries from composer.json metadata, falls back to a commented template, and supports drift-aware preview and overwrite flows.',
            $this->command->getHelp(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillWriteGeneratedCodeOwnersWhenFileIsMissing(): void
    {
        $targetPath = '/project/.github/CODEOWNERS';
        $targetDirectory = '/project/.github';
        $generatedContent = "* @php-fast-forward\n";

        $this->filesystem->getAbsolutePath('.github/CODEOWNERS')
            ->willReturn($targetPath);
        $this->filesystem->dirname($targetPath)
            ->willReturn($targetDirectory);
        $this->filesystem->exists($targetPath)
            ->willReturn(false, false);
        $this->filesystem->exists($targetDirectory)
            ->willReturn(false);
        $this->generator->inferOwners()
            ->willReturn(['@php-fast-forward']);
        $this->generator->generate(['@php-fast-forward'])
            ->willReturn($generatedContent);
        $this->fileDiffer->diffContents(
            'generated CODEOWNERS content',
            $targetPath,
            $generatedContent,
            null,
            'Managed file ' . $targetPath . ' will be created from generated CODEOWNERS content.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Managed file ' . $targetPath . ' will be created from generated CODEOWNERS content.',
        ))->shouldBeCalledOnce();
        $this->filesystem->mkdir($targetDirectory)
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile($targetPath, $generatedContent)
            ->shouldBeCalledOnce();

        self::assertSame(CodeOwnersCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipExistingCodeOwnersByDefault(): void
    {
        $targetPath = '/project/.github/CODEOWNERS';

        $this->filesystem->getAbsolutePath('.github/CODEOWNERS')
            ->willReturn($targetPath);
        $this->filesystem->dirname($targetPath)
            ->willReturn('/project/.github');
        $this->filesystem->exists($targetPath)
            ->willReturn(true);

        $this->logger->notice(
            'Managed file {target_path} already exists. Skipping CODEOWNERS generation.',
            [
                'command' => 'codeowners',
                'target_path' => $targetPath,
            ],
        )->shouldBeCalledOnce();
        $this->generator->inferOwners()
            ->shouldNotBeCalled();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(CodeOwnersCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillFailCheckModeWhenDriftIsDetected(): void
    {
        $targetPath = '/project/.github/CODEOWNERS';
        $generatedContent = "* @php-fast-forward\n";
        $existingContent = "* @legacy-owner\n";

        $this->input->getOption('check')
            ->willReturn(true);
        $this->filesystem->getAbsolutePath('.github/CODEOWNERS')
            ->willReturn($targetPath);
        $this->filesystem->dirname($targetPath)
            ->willReturn('/project/.github');
        $this->filesystem->exists($targetPath)
            ->willReturn(true);
        $this->filesystem->readFile($targetPath)
            ->willReturn($existingContent);
        $this->generator->inferOwners()
            ->willReturn(['@php-fast-forward']);
        $this->generator->generate(['@php-fast-forward'])
            ->willReturn($generatedContent);
        $this->fileDiffer->diffContents(
            'generated CODEOWNERS content',
            $targetPath,
            $generatedContent,
            $existingContent,
            'Updating managed file ' . $targetPath . ' from generated CODEOWNERS content.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file ' . $targetPath . ' from generated CODEOWNERS content.',
            "--- Original\n+++ Generated\n",
        ))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(CodeOwnersCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillPromptForOwnersWhenInteractiveInferenceFails(): void
    {
        $targetPath = '/project/.github/CODEOWNERS';
        $targetDirectory = '/project/.github';
        $generatedContent = "* @php-fast-forward @mentordosnerds\n";

        $this->input->getOption('interactive')
            ->willReturn(true);
        $this->input->isInteractive()
            ->willReturn(true);
        $this->filesystem->getAbsolutePath('.github/CODEOWNERS')
            ->willReturn($targetPath);
        $this->filesystem->dirname($targetPath)
            ->willReturn($targetDirectory);
        $this->filesystem->exists($targetPath)
            ->willReturn(false, false);
        $this->filesystem->exists($targetDirectory)
            ->willReturn(true);
        $this->generator->inferOwners()
            ->willReturn([]);
        $this->questionHelper->ask(
            $this->input->reveal(),
            $this->output->reveal(),
            Argument::type(Question::class),
        )->willReturn('php-fast-forward @mentordosnerds')
            ->shouldBeCalledOnce();
        $this->generator->normalizeOwners('php-fast-forward @mentordosnerds')
            ->willReturn(['@php-fast-forward', '@mentordosnerds'])
            ->shouldBeCalledOnce();
        $this->generator->generate(['@php-fast-forward', '@mentordosnerds'])
            ->willReturn($generatedContent)
            ->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated CODEOWNERS content',
            $targetPath,
            $generatedContent,
            null,
            'Managed file ' . $targetPath . ' will be created from generated CODEOWNERS content.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Managed file ' . $targetPath . ' will be created from generated CODEOWNERS content.',
        ))->shouldBeCalledOnce();
        $this->filesystem->mkdir(Argument::cetera())->shouldNotBeCalled();
        $this->filesystem->dumpFile($targetPath, $generatedContent)
            ->shouldBeCalledOnce();

        self::assertSame(CodeOwnersCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessOnDryRunWhenDriftIsDetected(): void
    {
        $targetPath = '/project/.github/CODEOWNERS';
        $generatedContent = "* @php-fast-forward\n";
        $existingContent = "* @legacy-owner\n";

        $this->input->getOption('dry-run')
            ->willReturn(true);
        $this->filesystem->getAbsolutePath('.github/CODEOWNERS')
            ->willReturn($targetPath);
        $this->filesystem->dirname($targetPath)
            ->willReturn('/project/.github');
        $this->filesystem->exists($targetPath)
            ->willReturn(true);
        $this->filesystem->readFile($targetPath)
            ->willReturn($existingContent);
        $this->generator->inferOwners()
            ->willReturn(['@php-fast-forward']);
        $this->generator->generate(['@php-fast-forward'])
            ->willReturn($generatedContent);
        $this->fileDiffer->diffContents(
            'generated CODEOWNERS content',
            $targetPath,
            $generatedContent,
            $existingContent,
            'Updating managed file ' . $targetPath . ' from generated CODEOWNERS content.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file ' . $targetPath . ' from generated CODEOWNERS content.',
        ))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(CodeOwnersCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipReplacingExistingCodeOwnersWhenConfirmationIsDeclined(): void
    {
        $targetPath = '/project/.github/CODEOWNERS';
        $generatedContent = "* @php-fast-forward\n";
        $existingContent = "* @legacy-owner\n";

        $this->input->getOption('interactive')
            ->willReturn(true);
        $this->input->isInteractive()
            ->willReturn(true);
        $this->filesystem->getAbsolutePath('.github/CODEOWNERS')
            ->willReturn($targetPath);
        $this->filesystem->dirname($targetPath)
            ->willReturn('/project/.github');
        $this->filesystem->exists($targetPath)
            ->willReturn(true);
        $this->filesystem->readFile($targetPath)
            ->willReturn($existingContent);
        $this->generator->inferOwners()
            ->willReturn(['@php-fast-forward']);
        $this->generator->generate(['@php-fast-forward'])
            ->willReturn($generatedContent);
        $this->fileDiffer->diffContents(
            'generated CODEOWNERS content',
            $targetPath,
            $generatedContent,
            $existingContent,
            'Updating managed file ' . $targetPath . ' from generated CODEOWNERS content.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file ' . $targetPath . ' from generated CODEOWNERS content.',
        ))->shouldBeCalledOnce();
        $this->questionHelper->ask(
            $this->input->reveal(),
            $this->output->reveal(),
            Argument::type(ConfirmationQuestion::class),
        )->willReturn(false)
            ->shouldBeCalledOnce();
        $this->logger->notice(
            'Skipped updating {target_path}.',
            [
                'command' => 'codeowners',
                'target_path' => $targetPath,
            ],
        )->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(CodeOwnersCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return int
     */
    private function invokeExecute(): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
