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

use FastForward\DevTools\Command\GitAttributesCommand;
use FastForward\DevTools\GitAttributes\CandidateProviderInterface;
use FastForward\DevTools\GitAttributes\ExistenceCheckerInterface;
use FastForward\DevTools\GitAttributes\ExportIgnoreFilterInterface;
use FastForward\DevTools\GitAttributes\MergerInterface;
use FastForward\DevTools\GitAttributes\ReaderInterface;
use FastForward\DevTools\GitAttributes\WriterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(GitAttributesCommand::class)]
final class GitAttributesCommandTest extends AbstractCommandTestCase
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
     * @var ObjectProphecy<MergerInterface>
     */
    private ObjectProphecy $merger;

    /**
     * @var ObjectProphecy<ExportIgnoreFilterInterface>
     */
    private ObjectProphecy $exportIgnoreFilter;

    /**
     * @var ObjectProphecy<ReaderInterface>
     */
    private ObjectProphecy $reader;

    /**
     * @var ObjectProphecy<WriterInterface>
     */
    private ObjectProphecy $writer;

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

        parent::setUp();

        $this->application->getInitialWorkingDirectory()
            ->willReturn('/project');
    }

    /**
     * @return GitAttributesCommand
     */
    protected function getCommandClass(): GitAttributesCommand
    {
        return new GitAttributesCommand(
            $this->filesystem->reveal(),
            $this->candidateProvider->reveal(),
            $this->existenceChecker->reveal(),
            $this->exportIgnoreFilter->reveal(),
            $this->merger->reveal(),
            $this->reader->reveal(),
            $this->writer->reveal(),
        );
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'gitattributes';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Manages .gitattributes export-ignore rules for leaner package archives.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command adds export-ignore entries for repository-only files and directories to keep them out of Composer package archives. Only paths that exist in the repository are added, existing custom rules are preserved, and "extra.gitattributes.keep-in-export" paths stay in exported archives.';
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

        $this->candidateProvider->folders()
            ->willReturn($folders);
        $this->candidateProvider->files()
            ->willReturn($files);
        $this->exportIgnoreFilter->filter($folders, [])
            ->willReturn($folders);
        $this->exportIgnoreFilter->filter($files, [])
            ->willReturn($files);

        $this->existenceChecker->filterExisting('/project', $folders)
            ->willReturn($folders);
        $this->existenceChecker->filterExisting('/project', $files)
            ->willReturn($files);

        $this->reader->read('/project/.gitattributes')
            ->willReturn("custom-entry\n");

        $this->merger->merge("custom-entry\n", $entries, [])
            ->willReturn("custom-entry\n/.github/ export-ignore");

        $this->writer->write('/project/.gitattributes', "custom-entry\n/.github/ export-ignore")
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Synchronizing .gitattributes export-ignore rules...</info>')
            ->shouldBeCalled();
        $this->output->writeln('<info>Added 4 export-ignore entries to .gitattributes.</info>')
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

        $this->package->getExtra()
            ->willReturn([
                'gitattributes' => [
                    'keep-in-export' => $keepInExportPaths,
                ],
            ]);

        $this->candidateProvider->folders()
            ->willReturn($folders);
        $this->candidateProvider->files()
            ->willReturn($files);
        $this->exportIgnoreFilter->filter($folders, $keepInExportPaths)
            ->willReturn($filteredFolders);
        $this->exportIgnoreFilter->filter($files, $keepInExportPaths)
            ->willReturn($filteredFiles);

        $this->existenceChecker->filterExisting('/project', $filteredFolders)
            ->willReturn($filteredFolders);
        $this->existenceChecker->filterExisting('/project', $filteredFiles)
            ->willReturn($filteredFiles);

        $this->reader->read('/project/.gitattributes')
            ->willReturn('');

        $this->merger->merge('', $entries, $keepInExportPaths)
            ->willReturn("/docs/ export-ignore\n/.editorconfig export-ignore");

        $this->writer->write('/project/.gitattributes', "/docs/ export-ignore\n/.editorconfig export-ignore")
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

        $this->existenceChecker->filterExisting('/project', [])
            ->willReturn([]);

        $this->reader->read('/project/.gitattributes')
            ->shouldNotBeCalled();
        $this->merger->merge('', [], [])
            ->shouldNotBeCalled();
        $this->writer->write('/project/.gitattributes', '')
            ->shouldNotBeCalled();

        $this->output->writeln('<info>Synchronizing .gitattributes export-ignore rules...</info>')
            ->shouldBeCalled();
        $this->output->writeln(
            '<comment>No candidate paths found in repository. Skipping .gitattributes sync.</comment>'
        )
            ->shouldBeCalled();

        self::assertSame(GitAttributesCommand::SUCCESS, $this->invokeExecute());
    }
}
