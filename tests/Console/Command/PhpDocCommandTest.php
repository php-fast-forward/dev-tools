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

use RuntimeException;
use DateTimeImmutable;
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Composer\Json\Schema\Author;
use FastForward\DevTools\Composer\Json\Schema\Support;
use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use FastForward\DevTools\Console\Command\PhpDocCommand;
use FastForward\DevTools\Console\Command\RefactorCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use FastForward\DevTools\Path\ManagedWorkspace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Twig\Environment;

#[CoversClass(PhpDocCommand::class)]
#[UsesClass(Author::class)]
#[UsesClass(Support::class)]
#[UsesClass(ManagedWorkspace::class)]
#[UsesTrait(LogsCommandResults::class)]
final class PhpDocCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $composer;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $renderer;

    private ObjectProphecy $clock;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $process;

    private PhpDocCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->composer = $this->prophesize(ComposerJsonInterface::class);
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->renderer = $this->prophesize(Environment::class);
        $this->clock = $this->prophesize(ClockInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->process = $this->prophesize(Process::class);

        $this->input->getOption('fix')
            ->willReturn(false);
        $this->input->getOption('cache-dir')
            ->willReturn(ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHP_CS_FIXER));
        $this->input->getOption('progress')
            ->willReturn(false);
        $this->input->getOption('json')
            ->willReturn(false);
        $this->input->getOption('pretty-json')
            ->willReturn(false);
        $this->output->getVerbosity()
            ->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $this->output->isDecorated()
            ->willReturn(false);
        $this->output->getFormatter()
            ->willReturn(new OutputFormatter());
        $this->composer->getSupport()
            ->willReturn(new Support(
                issues: 'https://github.com/php-fast-forward/dev-tools/issues',
                source: 'https://github.com/php-fast-forward/dev-tools',
                docs: 'https://php-fast-forward.github.io/dev-tools/',
            ));
        $this->composer->getHomepage()
            ->willReturn('https://github.com/php-fast-forward/');
        $this->composer->getName()
            ->willReturn('fast-forward/dev-tools');
        $this->composer->getDescription()
            ->willReturn('Fast Forward Development Tools.');
        $this->composer->getAuthors(true)
            ->willReturn(new Author('Felipe Sayão Lobato Abreu'));
        $this->composer->getLicense()
            ->willReturn('MIT');
        $this->clock->now()
            ->willReturn(new DateTimeImmutable('2026-04-21'));
        $this->renderer->render('docblock/.docheader', Argument::type('array'))->willReturn('docheader');
        $this->fileLocator->locate(PhpDocCommand::CONFIG)->willReturn('/repo/.php-cs-fixer.dist.php');
        $this->fileLocator->locate(RefactorCommand::CONFIG)->willReturn('/repo/rector.php');
        $this->filesystem->getAbsolutePath(
            PhpDocCommand::CACHE_FILE,
            ManagedWorkspace::getCacheDirectory(ManagedWorkspace::PHP_CS_FIXER)
        )
            ->willReturn('/repo/.dev-tools/cache/php-cs-fixer/.php-cs-fixer.cache');
        $this->processBuilder->withArgument(Argument::any())->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument(Argument::any(), Argument::any())->willReturn(
            $this->processBuilder->reveal()
        );
        $this->processBuilder->build(Argument::any())->willReturn($this->process->reveal());
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledTimes(2);

        $this->command = new PhpDocCommand(
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->composer->reveal(),
            $this->fileLocator->reveal(),
            $this->filesystem->reveal(),
            $this->renderer->reveal(),
            $this->clock->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCreateDocHeaderAndRunPhpDocProcesses(): void
    {
        $this->filesystem->dumpFile(PhpDocCommand::FILENAME, 'docheader')->shouldBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(PhpDocCommand::SUCCESS)
            ->shouldBeCalled();
        $this->logger->info('Checking and fixing PHPDocs...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->info('Created .docheader from repository template.')
            ->shouldBeCalled();
        $this->logger->log(
            'info',
            'PHPDoc checks completed successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(PhpDocCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillHandleDumpFileExceptionAndContinueRunningProcesses(): void
    {
        $this->filesystem->dumpFile(PhpDocCommand::FILENAME, 'docheader')
            ->willThrow(new RuntimeException('write failed'));
        $this->processQueue->run($this->output->reveal())
            ->willReturn(PhpDocCommand::FAILURE)
            ->shouldBeCalled();
        $this->logger->info('Checking and fixing PHPDocs...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->warning(
            'Skipping .docheader creation because the destination file could not be written.'
        )->shouldBeCalled();
        $this->logger->error(
            'PHPDoc checks failed.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(PhpDocCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRequestStructuredOutputAndDisableProgressWhenJsonIsRequested(): void
    {
        $this->input->getOption('json')
            ->willReturn(true);
        $this->input->getOption('pretty-json')
            ->willReturn(false);
        $this->filesystem->dumpFile(PhpDocCommand::FILENAME, 'docheader')->shouldBeCalled();
        $this->processBuilder->withArgument('--format=json')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalled();
        $this->processBuilder->withArgument('--show-progress=none')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalled();
        $this->processBuilder->withArgument('--no-progress-bar')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalled();
        $this->processBuilder->withArgument('--output-format', 'json')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalled();
        $this->processQueue->run(Argument::type(OutputInterface::class))
            ->willReturn(PhpDocCommand::SUCCESS)
            ->shouldBeCalled();

        self::assertSame(PhpDocCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillEnableProgressWhenRequested(): void
    {
        $this->input->getOption('progress')
            ->willReturn(true);
        $this->filesystem->dumpFile(PhpDocCommand::FILENAME, 'docheader')->shouldBeCalled();
        $this->processBuilder->withArgument('--show-progress=none')
            ->shouldNotBeCalled();
        $this->processBuilder->withArgument('--no-progress-bar')
            ->shouldNotBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(PhpDocCommand::SUCCESS)
            ->shouldBeCalled();

        self::assertSame(PhpDocCommand::SUCCESS, $this->invokeExecute());
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
