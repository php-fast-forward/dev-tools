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

use Prophecy\Argument;
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Console\Command\GitAttributesCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\GitAttributes\CandidateProviderInterface;
use FastForward\DevTools\GitAttributes\ExistenceCheckerInterface;
use FastForward\DevTools\GitAttributes\ExportIgnoreFilterInterface;
use FastForward\DevTools\GitAttributes\MergerInterface;
use FastForward\DevTools\GitAttributes\ReaderInterface;
use FastForward\DevTools\GitAttributes\WriterInterface;
use FastForward\DevTools\Resource\FileDiff;
use FastForward\DevTools\Resource\FileDiffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use function Safe\getcwd;

#[CoversClass(GitAttributesCommand::class)]
#[UsesClass(FileDiff::class)]
final class GitAttributesCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CandidateProviderInterface>
     */
    private ObjectProphecy $candidateProvider;

    /**
     * @var ObjectProphecy<ExistenceCheckerInterface>
     */
    private ObjectProphecy $existenceChecker;

    /**
     * @var ObjectProphecy<ExportIgnoreFilterInterface>
     */
    private ObjectProphecy $exportIgnoreFilter;

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
     * @var ObjectProphecy<ComposerJsonInterface>
     */
    private ObjectProphecy $composerJson;

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

    private ObjectProphecy $questionHelper;

    private GitAttributesCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->candidateProvider = $this->prophesize(CandidateProviderInterface::class);
        $this->existenceChecker = $this->prophesize(ExistenceCheckerInterface::class);
        $this->exportIgnoreFilter = $this->prophesize(ExportIgnoreFilterInterface::class);
        $this->merger = $this->prophesize(MergerInterface::class);
        $this->reader = $this->prophesize(ReaderInterface::class);
        $this->writer = $this->prophesize(WriterInterface::class);
        $this->composerJson = $this->prophesize(ComposerJsonInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->fileDiffer = $this->prophesize(FileDiffer::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->questionHelper = $this->prophesize(QuestionHelper::class);
        $this->output->isDecorated()
            ->willReturn(false);
        $this->output->writeln(Argument::any());
        $this->fileDiffer->formatForConsole(Argument::cetera())
            ->willReturn(null);
        $this->logger->info(Argument::cetera())->will(static function (): void {});
        $this->logger->notice(Argument::cetera())->will(static function (): void {});
        $this->logger->error(Argument::cetera())->will(static function (): void {});
        $this->questionHelper->getName()
            ->willReturn('question');
        $this->questionHelper->setHelperSet(Argument::type(HelperSet::class))
            ->shouldBeCalled();
        $this->input->getOption('dry-run')
            ->willReturn(false);
        $this->input->getOption('check')
            ->willReturn(false);
        $this->input->getOption('interactive')
            ->willReturn(false);
        $this->input->isInteractive()
            ->willReturn(false);

        $this->composerJson->getExtra('gitattributes')
            ->willReturn([]);

        $this->command = new GitAttributesCommand(
            $this->candidateProvider->reveal(),
            $this->existenceChecker->reveal(),
            $this->exportIgnoreFilter->reveal(),
            $this->merger->reveal(),
            $this->reader->reveal(),
            $this->writer->reveal(),
            $this->composerJson->reveal(),
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
        self::assertSame('gitattributes', $this->command->getName());
        self::assertSame(
            'Manages .gitattributes export-ignore rules for leaner package archives.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command adds export-ignore entries for repository-only files and directories to keep them out of Composer package archives. Only paths that exist in the repository are added, existing custom rules are preserved, and "extra.gitattributes.keep-in-export" paths stay in exported archives.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessAndWriteMergedGitattributes(): void
    {
        $folders = ['/docs/', '/.github/'];
        $files = ['/README.md', '/.editorconfig'];
        $entries = ['/docs/', '/.github/', '/README.md', '/.editorconfig'];
        $gitattributesPath = getcwd() . '/.gitattributes';

        $this->candidateProvider->folders()
            ->willReturn($folders);
        $this->candidateProvider->files()
            ->willReturn($files);
        $this->exportIgnoreFilter->filter($folders, [])
            ->willReturn($folders);
        $this->exportIgnoreFilter->filter($files, [])
            ->willReturn($files);
        $this->existenceChecker->filterExisting(getcwd(), $folders)
            ->willReturn($folders);
        $this->existenceChecker->filterExisting(getcwd(), $files)
            ->willReturn($files);
        $this->filesystem->getAbsolutePath('.gitattributes')
            ->willReturn($gitattributesPath);
        $this->reader->read($gitattributesPath)
            ->willReturn("custom-entry\n");
        $this->merger->merge("custom-entry\n", $entries, [])
            ->willReturn("custom-entry\n/.github/ export-ignore");
        $this->writer->render("custom-entry\n/.github/ export-ignore")
            ->willReturn("custom-entry\n/.github/ export-ignore\n");
        $this->writer->render("custom-entry\n")
            ->willReturn("custom-entry\n");
        $this->fileDiffer->diffContents(
            'generated .gitattributes synchronization',
            $gitattributesPath,
            "custom-entry\n/.github/ export-ignore\n",
            "custom-entry\n",
            'Updating managed file ' . $gitattributesPath . ' from generated .gitattributes synchronization.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file ' . $gitattributesPath . ' from generated .gitattributes synchronization.',
        ))->shouldBeCalledOnce();
        $this->writer->write($gitattributesPath, "custom-entry\n/.github/ export-ignore")
            ->shouldBeCalledOnce();

        $this->logger->info('Synchronizing .gitattributes export-ignore rules...', [
            'command' => 'gitattributes',
        ])
            ->shouldBeCalled();
        $this->logger->notice(
            'Updating managed file ' . $gitattributesPath . ' from generated .gitattributes synchronization.',
            Argument::type('array'),
        )->shouldBeCalled();
        $this->logger->info('Added {entries_count} export-ignore entries to .gitattributes.', Argument::type('array'))
            ->shouldBeCalled();

        self::assertSame(GitAttributesCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRespectKeepInExportComposerConfiguration(): void
    {
        $folders = ['/docs/', '/.github/'];
        $files = ['/README.md', '/.editorconfig'];
        $keepInExportPaths = ['/README.md', '/.github/'];
        $filteredFolders = ['/docs/'];
        $filteredFiles = ['/.editorconfig'];
        $entries = ['/docs/', '/.editorconfig'];
        $gitattributesPath = getcwd() . '/.gitattributes';

        $this->composerJson->getExtra('gitattributes')
            ->willReturn([
                'keep-in-export' => $keepInExportPaths,
            ]);
        $this->candidateProvider->folders()
            ->willReturn($folders);
        $this->candidateProvider->files()
            ->willReturn($files);
        $this->exportIgnoreFilter->filter($folders, $keepInExportPaths)
            ->willReturn($filteredFolders);
        $this->exportIgnoreFilter->filter($files, $keepInExportPaths)
            ->willReturn($filteredFiles);
        $this->existenceChecker->filterExisting(getcwd(), $filteredFolders)
            ->willReturn($filteredFolders);
        $this->existenceChecker->filterExisting(getcwd(), $filteredFiles)
            ->willReturn($filteredFiles);
        $this->filesystem->getAbsolutePath('.gitattributes')
            ->willReturn($gitattributesPath);
        $this->reader->read($gitattributesPath)
            ->willReturn('');
        $this->merger->merge('', $entries, $keepInExportPaths)
            ->willReturn("/docs/ export-ignore\n/.editorconfig export-ignore");
        $this->writer->render("/docs/ export-ignore\n/.editorconfig export-ignore")
            ->willReturn("/docs/ export-ignore\n/.editorconfig export-ignore\n");
        $this->fileDiffer->diffContents(
            'generated .gitattributes synchronization',
            $gitattributesPath,
            "/docs/ export-ignore\n/.editorconfig export-ignore\n",
            null,
            'Updating managed file ' . $gitattributesPath . ' from generated .gitattributes synchronization.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file ' . $gitattributesPath . ' from generated .gitattributes synchronization.',
        ))->shouldBeCalledOnce();
        $this->writer->write($gitattributesPath, "/docs/ export-ignore\n/.editorconfig export-ignore")
            ->shouldBeCalledOnce();

        self::assertSame(GitAttributesCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithNoCandidatesWillSkipSynchronization(): void
    {
        $folders = ['/docs/'];
        $files = ['/README.md'];

        $this->candidateProvider->folders()
            ->willReturn($folders);
        $this->candidateProvider->files()
            ->willReturn($files);
        $this->exportIgnoreFilter->filter($folders, [])
            ->willReturn([]);
        $this->exportIgnoreFilter->filter($files, [])
            ->willReturn([]);
        $this->existenceChecker->filterExisting(getcwd(), [])
            ->willReturn([]);

        $this->reader->read(Argument::cetera())
            ->shouldNotBeCalled();
        $this->merger->merge(Argument::cetera())
            ->shouldNotBeCalled();
        $this->writer->write(Argument::cetera())
            ->shouldNotBeCalled();

        $this->logger->info('Synchronizing .gitattributes export-ignore rules...', [
            'command' => 'gitattributes',
        ])
            ->shouldBeCalled();
        $this->logger->notice(
            'No candidate paths found in repository. Skipping .gitattributes sync.',
            [
                'command' => 'gitattributes',
            ],
        )->shouldBeCalled();

        self::assertSame(GitAttributesCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureInCheckModeWhenGitattributesWouldChange(): void
    {
        $entries = ['/docs/'];
        $gitattributesPath = getcwd() . '/.gitattributes';

        $this->input->getOption('check')
            ->willReturn(true);
        $this->candidateProvider->folders()
            ->willReturn($entries);
        $this->candidateProvider->files()
            ->willReturn([]);
        $this->exportIgnoreFilter->filter($entries, [])
            ->willReturn($entries);
        $this->exportIgnoreFilter->filter([], [])
            ->willReturn([]);
        $this->existenceChecker->filterExisting(getcwd(), $entries)
            ->willReturn($entries);
        $this->existenceChecker->filterExisting(getcwd(), [])
            ->willReturn([]);
        $this->filesystem->getAbsolutePath('.gitattributes')
            ->willReturn($gitattributesPath);
        $this->reader->read($gitattributesPath)
            ->willReturn('');
        $this->merger->merge('', $entries, [])
            ->willReturn('/docs/ export-ignore');
        $this->writer->render('/docs/ export-ignore')
            ->willReturn("/docs/ export-ignore\n");
        $this->fileDiffer->diffContents(Argument::cetera())
            ->willReturn(new FileDiff(
                FileDiff::STATUS_CHANGED,
                'Managed file needs update.',
                '@@ diff @@',
            ))->shouldBeCalledOnce();
        $this->fileDiffer->formatForConsole('@@ diff @@', false)
            ->willReturn('@@ diff @@')
            ->shouldBeCalledOnce();
        $this->logger->notice('@@ diff @@', Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->writer->write(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(GitAttributesCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessInDryRunModeWhenGitattributesWouldChange(): void
    {
        $entries = ['/docs/'];
        $gitattributesPath = getcwd() . '/.gitattributes';

        $this->input->getOption('dry-run')
            ->willReturn(true);
        $this->candidateProvider->folders()
            ->willReturn($entries);
        $this->candidateProvider->files()
            ->willReturn([]);
        $this->exportIgnoreFilter->filter($entries, [])
            ->willReturn($entries);
        $this->exportIgnoreFilter->filter([], [])
            ->willReturn([]);
        $this->existenceChecker->filterExisting(getcwd(), $entries)
            ->willReturn($entries);
        $this->existenceChecker->filterExisting(getcwd(), [])
            ->willReturn([]);
        $this->filesystem->getAbsolutePath('.gitattributes')
            ->willReturn($gitattributesPath);
        $this->reader->read($gitattributesPath)
            ->willReturn('');
        $this->merger->merge('', $entries, [])
            ->willReturn('/docs/ export-ignore');
        $this->writer->render('/docs/ export-ignore')
            ->willReturn("/docs/ export-ignore\n");
        $this->fileDiffer->diffContents(Argument::cetera())
            ->willReturn(new FileDiff(
                FileDiff::STATUS_CHANGED,
                'Managed file needs update.',
            ))->shouldBeCalledOnce();
        $this->writer->write(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(GitAttributesCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipWritingWhenInteractiveConfirmationIsDeclined(): void
    {
        $entries = ['/docs/'];
        $gitattributesPath = getcwd() . '/.gitattributes';

        $this->input->getOption('interactive')
            ->willReturn(true);
        $this->input->isInteractive()
            ->willReturn(true);
        $this->candidateProvider->folders()
            ->willReturn($entries);
        $this->candidateProvider->files()
            ->willReturn([]);
        $this->exportIgnoreFilter->filter($entries, [])
            ->willReturn($entries);
        $this->exportIgnoreFilter->filter([], [])
            ->willReturn([]);
        $this->existenceChecker->filterExisting(getcwd(), $entries)
            ->willReturn($entries);
        $this->existenceChecker->filterExisting(getcwd(), [])
            ->willReturn([]);
        $this->filesystem->getAbsolutePath('.gitattributes')
            ->willReturn($gitattributesPath);
        $this->reader->read($gitattributesPath)
            ->willReturn('');
        $this->merger->merge('', $entries, [])
            ->willReturn('/docs/ export-ignore');
        $this->writer->render('/docs/ export-ignore')
            ->willReturn("/docs/ export-ignore\n");
        $this->fileDiffer->diffContents(Argument::cetera())
            ->willReturn(new FileDiff(
                FileDiff::STATUS_CHANGED,
                'Managed file needs update.',
            ))->shouldBeCalledOnce();
        $this->questionHelper->ask(
            $this->input->reveal(),
            $this->output->reveal(),
            Argument::type(ConfirmationQuestion::class),
        )->willReturn(false)
            ->shouldBeCalledOnce();
        $this->logger->notice('Skipped updating {gitattributes_path}.', Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->writer->write(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(GitAttributesCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function configuredKeepInExportPathsWillMergePrimaryAndCompatibilityKeys(): void
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'configuredKeepInExportPaths');

        $this->composerJson->getExtra('gitattributes')
            ->willReturn([
                'keep-in-export' => ['/README.md', '/docs/'],
                'no-export-ignore' => ['/README.md', '/AGENTS.md'],
            ]);

        self::assertSame(
            ['/README.md', '/docs/', '/AGENTS.md'],
            array_values($reflectionMethod->invoke($this->command)),
        );
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
