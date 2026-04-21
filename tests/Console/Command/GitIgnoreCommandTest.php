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

use FastForward\DevTools\Console\Command\GitIgnoreCommand;
use FastForward\DevTools\GitIgnore\GitIgnoreInterface;
use FastForward\DevTools\GitIgnore\MergerInterface;
use FastForward\DevTools\GitIgnore\ReaderInterface;
use FastForward\DevTools\GitIgnore\WriterInterface;
use FastForward\DevTools\Resource\FileDiff;
use FastForward\DevTools\Resource\FileDiffer;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[CoversClass(GitIgnoreCommand::class)]
#[UsesClass(FileDiff::class)]
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
     * @var ObjectProphecy<FileDiffer>
     */
    private ObjectProphecy $fileDiffer;

    private ObjectProphecy $logger;

    /**
     * @var ObjectProphecy<QuestionHelper>
     */
    private ObjectProphecy $questionHelper;

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
        $this->fileDiffer = $this->prophesize(FileDiffer::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->questionHelper = $this->prophesize(QuestionHelper::class);
        $this->output->isDecorated()
            ->willReturn(false);
        $this->fileDiffer->formatForConsole(Argument::cetera())
            ->willReturn(null);
        $this->logger->info(Argument::cetera())->will(static function (): void {});
        $this->logger->notice(Argument::cetera())->will(static function (): void {});
        $this->logger->error(Argument::cetera())->will(static function (): void {});
        $this->questionHelper->getName()
            ->willReturn('question');
        $this->questionHelper->setHelperSet(Argument::type(HelperSet::class))
            ->shouldBeCalled();

        $this->gitIgnoreSource = $this->prophesize(GitIgnoreInterface::class);
        $this->gitIgnoreTarget = $this->prophesize(GitIgnoreInterface::class);
        $this->gitIgnoreMerged = $this->prophesize(GitIgnoreInterface::class);

        $this->input->getOption('source')
            ->willReturn(self::SOURCE_PATH);
        $this->input->getOption('target')
            ->willReturn(self::TARGET_PATH);
        $this->input->getOption('dry-run')
            ->willReturn(false);
        $this->input->getOption('check')
            ->willReturn(false);
        $this->input->getOption('interactive')
            ->willReturn(false);
        $this->input->isInteractive()
            ->willReturn(false);

        $this->reader->read(self::SOURCE_PATH)
            ->willReturn($this->gitIgnoreSource->reveal());
        $this->reader->read(self::TARGET_PATH)
            ->willReturn($this->gitIgnoreTarget->reveal());

        $this->merger->merge($this->gitIgnoreSource->reveal(), $this->gitIgnoreTarget->reveal())
            ->willReturn($this->gitIgnoreMerged->reveal());

        $this->writer->write(Argument::any());
        $this->writer->render($this->gitIgnoreMerged->reveal())
            ->willReturn("vendor/\n");
        $this->writer->render($this->gitIgnoreTarget->reveal())
            ->willReturn('');
        $this->gitIgnoreMerged->path()
            ->willReturn(self::TARGET_PATH);
        $this->fileDiffer->diffContents(
            'generated .gitignore synchronization',
            self::TARGET_PATH,
            "vendor/\n",
            '',
            'Updating managed file /path/to/target/.gitignore from generated .gitignore synchronization.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file /path/to/target/.gitignore from generated .gitignore synchronization.',
        ));
        $this->command = new GitIgnoreCommand(
            $this->merger->reveal(),
            $this->reader->reveal(),
            $this->writer->reveal(),
            $this->fileLocator->reveal(),
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

        $this->logger->info('Merging .gitignore files...', [
            'input' => $this->input->reveal(),
        ])
            ->shouldBeCalled();
        $this->logger->info(
            'Successfully merged .gitignore file.',
            [
                'input' => $this->input->reveal(),
                'target_path' => self::TARGET_PATH,
            ],
        )
            ->shouldBeCalled();

        $result = $this->executeCommand();

        self::assertSame(GitIgnoreCommand::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenGitIgnoreIsAlreadySynchronized(): void
    {
        $this->fileDiffer->diffContents(
            'generated .gitignore synchronization',
            self::TARGET_PATH,
            "vendor/\n",
            '',
            'Updating managed file /path/to/target/.gitignore from generated .gitignore synchronization.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_UNCHANGED,
            'Target /path/to/target/.gitignore already matches source generated .gitignore synchronization; overwrite skipped.',
        ));

        $this->writer->write(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(GitIgnoreCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureInCheckModeWhenDriftIsDetected(): void
    {
        $this->input->getOption('check')
            ->willReturn(true);

        $this->writer->write(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(GitIgnoreCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessInDryRunModeWhenDriftIsDetected(): void
    {
        $this->input->getOption('dry-run')
            ->willReturn(true);

        $this->writer->write(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(GitIgnoreCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipWritingWhenInteractiveConfirmationIsDeclined(): void
    {
        $this->input->getOption('interactive')
            ->willReturn(true);
        $this->input->isInteractive()
            ->willReturn(true);
        $this->questionHelper->ask(
            $this->input->reveal(),
            $this->output->reveal(),
            Argument::type(ConfirmationQuestion::class),
        )->willReturn(false)
            ->shouldBeCalledOnce();
        $this->logger->notice(
            'Skipped updating {target_path}.',
            [
                'input' => $this->input->reveal(),
                'target_path' => self::TARGET_PATH,
            ],
        )
            ->shouldBeCalledOnce();
        $this->writer->write(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(GitIgnoreCommand::SUCCESS, $this->executeCommand());
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
