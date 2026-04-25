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

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use FastForward\DevTools\Console\Command\FundingCommand;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Funding\ComposerFundingCodec;
use FastForward\DevTools\Funding\FundingProfile;
use FastForward\DevTools\Funding\FundingProfileMerger;
use FastForward\DevTools\Funding\FundingYamlCodec;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Resource\FileDiff;
use FastForward\DevTools\Resource\FileDiffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

use function Safe\json_decode;

#[CoversClass(FundingCommand::class)]
#[UsesClass(FileDiff::class)]
#[UsesClass(ComposerFundingCodec::class)]
#[UsesClass(FundingProfile::class)]
#[UsesClass(FundingProfileMerger::class)]
#[UsesClass(FundingYamlCodec::class)]
#[UsesTrait(LogsCommandResults::class)]
final class FundingCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $fileDiffer;

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $normalizeProcess;

    private ObjectProphecy $io;

    private ObjectProphecy $logger;

    private FundingCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->fileDiffer = $this->prophesize(FileDiffer::class);
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->normalizeProcess = $this->prophesize(Process::class);
        $this->io = $this->prophesize(SymfonyStyle::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->output->isDecorated()
            ->willReturn(false);
        $this->output->writeln(Argument::any());
        $this->fileDiffer->formatForConsole(Argument::cetera())->willReturn(null);
        $this->logger->info(Argument::cetera())->will(static function (): void {});
        $this->logger->log(Argument::cetera())->will(static function (): void {});
        $this->logger->notice(Argument::cetera())->will(static function (): void {});
        $this->logger->error(Argument::cetera())->will(static function (): void {});
        $this->input->getOption('composer-file')
            ->willReturn('composer.json');
        $this->input->getOption('funding-file')
            ->willReturn('.github/FUNDING.yml');
        $this->input->getOption('dry-run')
            ->willReturn(false);
        $this->input->getOption('check')
            ->willReturn(false);
        $this->input->getOption('interactive')
            ->willReturn(false);
        $this->filesystem->dirname('.github/FUNDING.yml')
            ->willReturn('.github');
        $this->filesystem->dirname('composer.json')
            ->willReturn('.');
        $this->filesystem->basename('composer.json')
            ->willReturn('composer.json');
        $this->processBuilder->withArgument(Argument::any())->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument(Argument::any(), Argument::any())->willReturn(
            $this->processBuilder->reveal()
        );
        $this->processBuilder->build('composer normalize')
            ->willReturn($this->normalizeProcess->reveal());

        $this->command = new FundingCommand(
            $this->filesystem->reveal(),
            new ComposerFundingCodec(),
            new FundingYamlCodec(),
            new FundingProfileMerger(),
            $this->fileDiffer->reveal(),
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->logger->reveal(),
            $this->io->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCreateComposerFundingFromFundingYaml(): void
    {
        $composerContents = '{"name":"example/package"}';
        $fundingYaml = "github: foo\ncustom: https://example.com/support\n";

        $this->filesystem->exists('composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('composer.json')
            ->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')
            ->willReturn(true);
        $this->filesystem->readFile('.github/FUNDING.yml')
            ->willReturn($fundingYaml);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::that(static function (string $contents): bool {
                $decoded = json_decode($contents, true);

                return [
                    [
                        'type' => 'github',
                        'url' => 'https://github.com/sponsors/foo',
                    ],
                    [
                        'type' => 'custom',
                        'url' => 'https://example.com/support',
                    ],
                ] === $decoded['funding'];
            }),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Composer changed'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::type('string'),
            $fundingYaml,
            'Updating managed file .github/FUNDING.yml from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Funding unchanged'))->shouldBeCalledOnce();
        $this->processQueue->add($this->normalizeProcess->reveal(), Argument::cetera())
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS)->shouldBeCalledOnce();
        $this->filesystem->dumpFile(
            'composer.json',
            Argument::that(static fn(string $contents): bool => str_contains($contents, '"funding"')),
        )->shouldBeCalledOnce();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCreateFundingYamlFromComposerFunding(): void
    {
        $composerContents = <<<'JSON'
            {"name":"example/package","funding":[{"type":"github","url":"https://github.com/sponsors/foo"},{"type":"custom","url":"https://example.com/support"}]}
            JSON;

        $this->filesystem->exists('composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('composer.json')
            ->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')
            ->willReturn(false);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::type('string'),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Composer unchanged'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::that(static function (string $contents): bool {
                $decoded = Yaml::parse($contents);

                return 'foo' === $decoded['github']
                    && ['https://example.com/support'] === $decoded['custom'];
            }),
            null,
            'Managed file .github/FUNDING.yml will be created from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Funding changed'))->shouldBeCalledOnce();
        $this->filesystem->mkdir('.github')
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile('.github/FUNDING.yml', Argument::type('string'))->shouldBeCalledOnce();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillMergeBothSourcesWithoutDuplicatingEntries(): void
    {
        $composerContents = <<<'JSON'
            {"name":"example/package","funding":[{"type":"github","url":"https://github.com/sponsors/foo"}]}
            JSON;
        $fundingYaml = "custom: https://example.com/support\n";

        $this->filesystem->exists('composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('composer.json')
            ->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')
            ->willReturn(true);
        $this->filesystem->readFile('.github/FUNDING.yml')
            ->willReturn($fundingYaml);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::that(static function (string $contents): bool {
                $decoded = json_decode($contents, true);

                return [
                    [
                        'type' => 'github',
                        'url' => 'https://github.com/sponsors/foo',
                    ],
                    [
                        'type' => 'custom',
                        'url' => 'https://example.com/support',
                    ],
                ] === $decoded['funding'];
            }),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Composer changed'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::that(static function (string $contents): bool {
                $decoded = Yaml::parse($contents);

                return 'foo' === $decoded['github']
                    && ['https://example.com/support'] === $decoded['custom'];
            }),
            $fundingYaml,
            'Updating managed file .github/FUNDING.yml from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Funding changed'))->shouldBeCalledOnce();
        $this->processQueue->add($this->normalizeProcess->reveal(), Argument::cetera())
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS)->shouldBeCalledOnce();
        $this->filesystem->dumpFile('composer.json', Argument::type('string'))->shouldBeCalledOnce();
        $this->filesystem->mkdir('.github')
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile('.github/FUNDING.yml', Argument::type('string'))->shouldBeCalledOnce();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillBeIdempotentWhenFundingMetadataAlreadyMatches(): void
    {
        $composerContents = <<<'JSON'
            {"name":"example/package","funding":[{"type":"github","url":"https://github.com/sponsors/foo"},{"type":"custom","url":"https://example.com/support"}]}
            JSON;
        $fundingYaml = "github: foo\ncustom: https://example.com/support\n";

        $this->filesystem->exists('composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('composer.json')
            ->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')
            ->willReturn(true);
        $this->filesystem->readFile('.github/FUNDING.yml')
            ->willReturn($fundingYaml);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::type('string'),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Composer unchanged'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::type('string'),
            $fundingYaml,
            'Updating managed file .github/FUNDING.yml from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Funding unchanged'))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();
        $this->processQueue->add(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenComposerFileDoesNotExist(): void
    {
        $this->filesystem->exists('composer.json')
            ->willReturn(false);
        $this->logger->info('Synchronizing funding metadata...', [
            'input' => $this->input->reveal(),
        ])
            ->shouldBeCalledOnce();
        $this->logger->notice(
            'Composer file {composer_file} does not exist. Skipping funding synchronization.',
            [
                'input' => $this->input->reveal(),
                'composer_file' => 'composer.json',
                'funding_file' => '.github/FUNDING.yml',
            ],
        )->shouldBeCalledOnce();
        $this->logger->log(
            'notice',
            'Funding synchronization was skipped because composer.json was not found.',
            Argument::type('array'),
        )->shouldBeCalledOnce();
        $this->filesystem->readFile(Argument::cetera())->shouldNotBeCalled();
        $this->fileDiffer->diffContents(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureInCheckModeWhenComposerFileWouldChange(): void
    {
        $composerContents = '{"name":"example/package"}';
        $fundingYaml = "github: foo\n";

        $this->input->getOption('check')
            ->willReturn(true);
        $this->filesystem->exists('composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('composer.json')
            ->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')
            ->willReturn(true);
        $this->filesystem->readFile('.github/FUNDING.yml')
            ->willReturn($fundingYaml);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::type('string'),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Composer changed'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::type('string'),
            $fundingYaml,
            'Updating managed file .github/FUNDING.yml from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Funding unchanged'))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();
        $this->processQueue->add(Argument::cetera())->shouldNotBeCalled();
        $this->logger->error('{composer_file} requires synchronized funding metadata updates.', Argument::type('array'))
            ->shouldBeCalledOnce();

        self::assertSame(FundingCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillNotWriteManagedFilesDuringDryRun(): void
    {
        $composerContents = '{"name":"example/package"}';
        $fundingYaml = "github: foo\n";

        $this->input->getOption('dry-run')
            ->willReturn(true);
        $this->filesystem->exists('composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('composer.json')
            ->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')
            ->willReturn(true);
        $this->filesystem->readFile('.github/FUNDING.yml')
            ->willReturn($fundingYaml);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::type('string'),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Composer changed'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::type('string'),
            $fundingYaml,
            'Updating managed file .github/FUNDING.yml from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Funding unchanged'))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();
        $this->processQueue->add(Argument::cetera())->shouldNotBeCalled();
        $this->logger->log(
            'notice',
            'Funding synchronization preview completed for {composer_file}.',
            Argument::type('array'),
        )->shouldBeCalledOnce();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipComposerWriteWhenInteractiveConfirmationIsDeclined(): void
    {
        $composerContents = '{"name":"example/package"}';
        $fundingYaml = "github: foo\n";

        $this->input->getOption('interactive')
            ->willReturn(true);
        $this->input->isInteractive()
            ->willReturn(true);
        $this->io->askQuestion(Argument::type(ConfirmationQuestion::class))
            ->willReturn(false)
            ->shouldBeCalledOnce();
        $this->filesystem->exists('composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('composer.json')
            ->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')
            ->willReturn(true);
        $this->filesystem->readFile('.github/FUNDING.yml')
            ->willReturn($fundingYaml);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::type('string'),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Composer changed'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::type('string'),
            $fundingYaml,
            'Updating managed file .github/FUNDING.yml from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Funding unchanged'))->shouldBeCalledOnce();
        $this->logger->notice('Skipped updating {composer_file}.', Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->logger->log(
            'notice',
            'Funding synchronization was skipped for {composer_file}.',
            Argument::type('array')
        )
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();
        $this->processQueue->add(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenComposerNormalizeFails(): void
    {
        $composerContents = '{"name":"example/package"}';
        $fundingYaml = "github: foo\n";

        $this->filesystem->exists('composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('composer.json')
            ->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')
            ->willReturn(true);
        $this->filesystem->readFile('.github/FUNDING.yml')
            ->willReturn($fundingYaml);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::type('string'),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Composer changed'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::type('string'),
            $fundingYaml,
            'Updating managed file .github/FUNDING.yml from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Funding unchanged'))->shouldBeCalledOnce();
        $this->processQueue->add($this->normalizeProcess->reveal(), Argument::cetera())
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::FAILURE)->shouldBeCalledOnce();
        $this->filesystem->dumpFile(
            'composer.json',
            Argument::that(static fn(string $contents): bool => str_contains($contents, '"funding"')),
        )->shouldBeCalledOnce();
        $this->logger->log('info', 'Updated funding metadata in {composer_file}.', Argument::type('array'))
            ->shouldNotBeCalled();
        $this->logger->error('Composer normalization failed after updating {composer_file}.', Argument::type('array'))
            ->shouldBeCalledOnce();

        self::assertSame(FundingCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillPassWorkingDirectoryAndAlternateManifestToComposerNormalize(): void
    {
        $composerFile = 'build/custom/composer.alt.json';
        $composerContents = '{"name":"example/package"}';
        $fundingYaml = "github: foo\n";

        $this->input->getOption('composer-file')
            ->willReturn($composerFile);
        $this->filesystem->exists($composerFile)
            ->willReturn(true);
        $this->filesystem->readFile($composerFile)
            ->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')
            ->willReturn(true);
        $this->filesystem->readFile('.github/FUNDING.yml')
            ->willReturn($fundingYaml);
        $this->filesystem->dirname($composerFile)
            ->willReturn('build/custom');
        $this->filesystem->basename($composerFile)
            ->willReturn('composer.alt.json');
        $this->processBuilder->withArgument('--working-dir', 'build/custom')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('--file', 'composer.alt.json')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            $composerFile,
            Argument::type('string'),
            $composerContents,
            'Updating managed file build/custom/composer.alt.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Composer changed'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::type('string'),
            $fundingYaml,
            'Updating managed file .github/FUNDING.yml from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Funding unchanged'))->shouldBeCalledOnce();
        $this->processQueue->add($this->normalizeProcess->reveal(), Argument::cetera())
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS)->shouldBeCalledOnce();
        $this->filesystem->dumpFile(
            $composerFile,
            Argument::that(static fn(string $contents): bool => str_contains($contents, '"funding"')),
        )->shouldBeCalledOnce();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('funding', $this->command->getName());
        self::assertSame(
            'Synchronizes funding metadata between composer.json and .github/FUNDING.yml.',
            $this->command->getDescription(),
        );
        self::assertSame(
            'This command merges supported funding entries across composer.json and .github/FUNDING.yml while preserving unsupported providers.',
            $this->command->getHelp(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipFundingFileSynchronizationWhenNoSupportedFundingMetadataExists(): void
    {
        $composerContents = '{"name":"example/package"}';

        $this->filesystem->exists('composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('composer.json')
            ->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')
            ->willReturn(false);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::type('string'),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Composer unchanged'))->shouldBeCalledOnce();
        $this->logger->notice(
            'No supported funding metadata found. Skipping .github/FUNDING.yml synchronization.',
            [
                'input' => $this->input->reveal(),
                'funding_file' => '.github/FUNDING.yml',
            ],
        )->shouldBeCalledOnce();
        $this->logger->log(
            'notice',
            'Funding synchronization found no supported GitHub funding metadata to write.',
            Argument::type('array'),
        )->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function privateHelpersWillPromptAndNormalizeComposerFileArguments(): void
    {
        $shouldWriteManagedFile = new ReflectionMethod($this->command, 'shouldWriteManagedFile');
        $normalizeComposerFile = new ReflectionMethod($this->command, 'normalizeComposerFile');

        $this->io->askQuestion(Argument::type(ConfirmationQuestion::class))
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->filesystem->dirname('composer.alt.json')
            ->willReturn('.');
        $this->filesystem->basename('composer.alt.json')
            ->willReturn('composer.alt.json');
        $this->processBuilder->withArgument('--file', 'composer.alt.json')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->normalizeProcess->reveal(), Argument::cetera())
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalledOnce();

        self::assertTrue($shouldWriteManagedFile->invoke($this->command, 'composer.alt.json'));
        self::assertSame(
            ProcessQueueInterface::SUCCESS,
            $normalizeComposerFile->invoke($this->command, 'composer.alt.json', $this->output->reveal()),
        );
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
