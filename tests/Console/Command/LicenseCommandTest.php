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
use FastForward\DevTools\Console\Command\LicenseCommand;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Console\Output\GithubActionOutput;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\License\Generator;
use FastForward\DevTools\License\GeneratorInterface;
use FastForward\DevTools\License\Resolver;
use FastForward\DevTools\Resource\FileDiff;
use FastForward\DevTools\Resource\FileDiffer;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\getcwd;

#[CoversClass(LicenseCommand::class)]
#[UsesClass(FileDiff::class)]
#[UsesClass(Resolver::class)]
#[UsesClass(Generator::class)]
#[UsesClass(GithubActionOutput::class)]
#[UsesTrait(LogsCommandResults::class)]
final class LicenseCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<GeneratorInterface>
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

    private ObjectProphecy $logger;

    private ObjectProphecy $io;

    private LicenseCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = $this->prophesize(GeneratorInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->fileDiffer = $this->prophesize(FileDiffer::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->io = $this->prophesize(SymfonyStyle::class);
        $this->output->isDecorated()
            ->willReturn(false);
        $this->input->getOption('dry-run')
            ->willReturn(false);
        $this->input->getOption('check')
            ->willReturn(false);
        $this->input->getOption('interactive')
            ->willReturn(false);
        $this->input->isInteractive()
            ->willReturn(false);
        $this->fileDiffer->formatForConsole(Argument::cetera())
            ->willReturn(null);
        $this->logger->info(Argument::cetera())->will(static function (): void {});
        $this->logger->log(Argument::cetera())->will(static function (): void {});
        $this->logger->notice(Argument::cetera())->will(static function (): void {});
        $this->logger->error(Argument::cetera())->will(static function (): void {});

        $this->command = new LicenseCommand(
            $this->generator->reveal(),
            $this->filesystem->reveal(),
            $this->fileDiffer->reveal(),
            $this->logger->reveal(),
            $this->io->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('license:generate', $this->command->getName());
        self::assertSame(
            'Generates a LICENSE file from composer.json license information.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command generates a LICENSE file if one does not exist and a supported license is declared in composer.json.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessAndWriteInfo(): void
    {
        $targetPath = getcwd() . '/LICENSE';

        $this->input->getOption('target')
            ->willReturn('LICENSE');
        $this->filesystem->getAbsolutePath('LICENSE')
            ->willReturn($targetPath);
        $this->filesystem->exists($targetPath)
            ->willReturn(false);
        $this->generator->generateContent()
            ->willReturn('MIT License content');
        $this->fileDiffer->diffContents(
            'generated LICENSE content',
            $targetPath,
            'MIT License content',
            null,
            'Managed file ' . $targetPath . ' will be created from generated LICENSE content.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Managed file ' . $targetPath . ' will be created from generated LICENSE content.',
        ))->shouldBeCalledOnce();
        $this->filesystem->dumpFile($targetPath, 'MIT License content')
            ->shouldBeCalledOnce();

        $this->logger->notice(
            'Managed file ' . $targetPath . ' will be created from generated LICENSE content.',
            [
                'input' => $this->input->reveal(),
                'target_path' => $targetPath,
            ],
        )->shouldBeCalledOnce();
        $this->logger->log(
            'info',
            '{file_name} file generated successfully at {target_path}.',
            [
                'input' => $this->input->reveal(),
                'file_name' => 'LICENSE',
                'target_path' => $targetPath,
            ],
        )->shouldBeCalledOnce();

        self::assertSame(LicenseCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipWhenLicenseFileExists(): void
    {
        $targetPath = getcwd() . '/LICENSE';

        $this->input->getOption('target')
            ->willReturn('LICENSE');
        $this->filesystem->getAbsolutePath('LICENSE')
            ->willReturn($targetPath);
        $this->filesystem->exists($targetPath)
            ->willReturn(true);
        $this->filesystem->readFile($targetPath)
            ->willReturn('MIT License content');
        $this->generator->generateContent()
            ->willReturn('MIT License content');
        $this->fileDiffer->diffContents(
            'generated LICENSE content',
            $targetPath,
            'MIT License content',
            'MIT License content',
            'Updating managed file ' . $targetPath . ' from generated LICENSE content.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_UNCHANGED,
            'Target ' . $targetPath . ' already matches source generated LICENSE content; overwrite skipped.',
        ))->shouldBeCalledOnce();

        $this->logger->notice(
            'Target ' . $targetPath . ' already matches source generated LICENSE content; overwrite skipped.',
            [
                'input' => $this->input->reveal(),
                'target_path' => $targetPath,
            ],
        )->shouldBeCalledOnce();

        self::assertSame(LicenseCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillHandleUnsupportedLicense(): void
    {
        $targetPath = getcwd() . '/LICENSE';

        $this->input->getOption('target')
            ->willReturn('LICENSE');
        $this->filesystem->getAbsolutePath('LICENSE')
            ->willReturn($targetPath);
        $this->filesystem->exists($targetPath)
            ->willReturn(false);
        $this->generator->generateContent()
            ->willReturn(null);

        $this->logger->notice(
            'No supported license found in composer.json or license is unsupported. Skipping LICENSE generation.',
            [
                'input' => $this->input->reveal(),
                'target_path' => $targetPath,
            ],
        )->shouldBeCalledOnce();
        $this->logger->log(
            'notice',
            'LICENSE generation was skipped because no supported license metadata was available.',
            Argument::type('array'),
        )->shouldBeCalledOnce();

        self::assertSame(LicenseCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureInCheckModeWhenLicenseWouldChange(): void
    {
        $targetPath = getcwd() . '/LICENSE';

        $this->input->getOption('target')
            ->willReturn('LICENSE');
        $this->input->getOption('check')
            ->willReturn(true);
        $this->filesystem->getAbsolutePath('LICENSE')
            ->willReturn($targetPath);
        $this->filesystem->exists($targetPath)
            ->willReturn(true);
        $this->filesystem->readFile($targetPath)
            ->willReturn('Old license');
        $this->generator->generateContent()
            ->willReturn('New license');
        $this->fileDiffer->diffContents(
            'generated LICENSE content',
            $targetPath,
            'New license',
            'Old license',
            'Updating managed file ' . $targetPath . ' from generated LICENSE content.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file ' . $targetPath . ' from generated LICENSE content.',
        ))->shouldBeCalledOnce();
        $this->logger->error('LICENSE requires synchronization updates.', Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(LicenseCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessInDryRunModeWhenLicenseWouldChange(): void
    {
        $targetPath = getcwd() . '/LICENSE';

        $this->input->getOption('target')
            ->willReturn('LICENSE');
        $this->input->getOption('dry-run')
            ->willReturn(true);
        $this->filesystem->getAbsolutePath('LICENSE')
            ->willReturn($targetPath);
        $this->filesystem->exists($targetPath)
            ->willReturn(true);
        $this->filesystem->readFile($targetPath)
            ->willReturn('Old license');
        $this->generator->generateContent()
            ->willReturn('New license');
        $this->fileDiffer->diffContents(
            'generated LICENSE content',
            $targetPath,
            'New license',
            'Old license',
            'Updating managed file ' . $targetPath . ' from generated LICENSE content.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file ' . $targetPath . ' from generated LICENSE content.',
        ))->shouldBeCalledOnce();
        $this->logger->log('notice', 'LICENSE generation preview completed.', Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(LicenseCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipWritingWhenInteractiveConfirmationIsDeclined(): void
    {
        $targetPath = getcwd() . '/LICENSE';

        $this->input->getOption('target')
            ->willReturn('LICENSE');
        $this->input->getOption('interactive')
            ->willReturn(true);
        $this->input->isInteractive()
            ->willReturn(true);
        $this->filesystem->getAbsolutePath('LICENSE')
            ->willReturn($targetPath);
        $this->filesystem->exists($targetPath)
            ->willReturn(true);
        $this->filesystem->readFile($targetPath)
            ->willReturn('Old license');
        $this->generator->generateContent()
            ->willReturn('New license');
        $this->fileDiffer->diffContents(
            'generated LICENSE content',
            $targetPath,
            'New license',
            'Old license',
            'Updating managed file ' . $targetPath . ' from generated LICENSE content.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file ' . $targetPath . ' from generated LICENSE content.',
        ))->shouldBeCalledOnce();
        $this->io->askQuestion(Argument::type(ConfirmationQuestion::class))
            ->willReturn(false)
            ->shouldBeCalledOnce();
        $this->logger->notice(
            'Skipped updating {target_path}.',
            [
                'input' => $this->input->reveal(),
                'target_path' => $targetPath,
            ],
        )
            ->shouldBeCalledOnce();
        $this->logger->log('notice', 'LICENSE generation was skipped.', Argument::type('array'))
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(LicenseCommand::SUCCESS, $this->invokeExecute());
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
