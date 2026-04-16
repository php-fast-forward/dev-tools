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

use DateTimeImmutable;
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Composer\Json\Schema\Author;
use FastForward\DevTools\Composer\Json\Schema\Support;
use FastForward\DevTools\Console\Command\PhpDocCommand;
use FastForward\DevTools\Console\Command\RefactorCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Process\ProcessBuilder;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Clock\ClockInterface;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Twig\Environment;

#[CoversClass(PhpDocCommand::class)]
#[UsesClass(Author::class)]
#[UsesClass(ProcessBuilder::class)]
#[UsesClass(Support::class)]
final class PhpDocCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ProcessQueueInterface>
     */
    private ObjectProphecy $processQueue;

    /**
     * @var ObjectProphecy<ComposerJsonInterface>
     */
    private ObjectProphecy $composer;

    /**
     * @var ObjectProphecy<FileLocatorInterface>
     */
    private ObjectProphecy $fileLocator;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<Environment>
     */
    private ObjectProphecy $renderer;

    /**
     * @var ObjectProphecy<ClockInterface>
     */
    private ObjectProphecy $clock;

    /**
     * @var ObjectProphecy<InputInterface>
     */
    private ObjectProphecy $input;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    private PhpDocCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->composer = $this->prophesize(ComposerJsonInterface::class);
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->renderer = $this->prophesize(Environment::class);
        $this->clock = $this->prophesize(ClockInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new PhpDocCommand(
            new ProcessBuilder(),
            $this->processQueue->reveal(),
            $this->composer->reveal(),
            $this->fileLocator->reveal(),
            $this->filesystem->reveal(),
            $this->renderer->reveal(),
            $this->clock->reveal(),
        );

        $this->input->getOption('fix')
            ->willReturn(false);
        $this->input->getOption('cache-dir')
            ->willReturn('tmp/cache/php-cs-fixer');
        $this->fileLocator->locate(PhpDocCommand::CONFIG)
            ->willReturn('/app/.php-cs-fixer.dist.php');
        $this->fileLocator->locate(RefactorCommand::CONFIG)
            ->willReturn('/app/rector.php');
        $this->filesystem->getAbsolutePath(PhpDocCommand::CACHE_FILE, 'tmp/cache/php-cs-fixer')
            ->willReturn('/app/tmp/cache/php-cs-fixer/.php-cs-fixer.cache');
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('phpdoc', $this->command->getName());
        self::assertSame('Checks and fixes PHPDocs.', $this->command->getDescription());
        self::assertSame('This command checks and fixes PHPDocs in your PHP files.', $this->command->getHelp());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCreateDocHeaderAndRunPhpDocProcesses(): void
    {
        $this->willRenderDocHeader();
        $this->filesystem->dumpFile(PhpDocCommand::FILENAME, 'Content')
            ->shouldBeCalledOnce();
        $this->processQueue->add(Argument::type(Process::class))
            ->shouldBeCalledTimes(2);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(PhpDocCommand::SUCCESS)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Checking and fixing PHPDocs...</info>')
            ->shouldBeCalled();
        $this->output->writeln('<info>Created .docheader from repository template.</info>')
            ->shouldBeCalled();

        self::assertSame(PhpDocCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillHandleDumpFileExceptionAndContinueRunningProcesses(): void
    {
        $this->willRenderDocHeader();
        $this->filesystem->dumpFile(PhpDocCommand::FILENAME, 'Content')
            ->willThrow(new RuntimeException('dump error'));
        $this->processQueue->add(Argument::type(Process::class))
            ->shouldBeCalledTimes(2);
        $this->processQueue->run($this->output->reveal())
            ->willReturn(PhpDocCommand::SUCCESS)
            ->shouldBeCalledOnce();

        $this->output->writeln('<info>Checking and fixing PHPDocs...</info>')
            ->shouldBeCalled();
        $this->output->writeln(
            '<comment>Skipping .docheader creation because the destination file could not be written.</comment>'
        )
            ->shouldBeCalled();

        self::assertSame(PhpDocCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    private function willRenderDocHeader(): void
    {
        $this->composer->getSupport()
            ->willReturn(new Support(
                issues: 'https://github.com/php-fast-forward/dev-tools/issues',
                wiki: 'https://github.com/php-fast-forward/dev-tools/wiki',
                source: 'https://github.com/php-fast-forward/dev-tools',
                docs: 'https://php-fast-forward.github.io/dev-tools/',
            ));
        $this->composer->getHomepage()
            ->willReturn('https://github.com/php-fast-forward/');
        $this->composer->getName()
            ->willReturn('fast-forward/dev-tools');
        $this->composer->getDescription()
            ->willReturn('Fast Forward Development Tools for PHP projects');
        $this->composer->getAuthors(true)
            ->willReturn(new Author('Felipe Sayão Lobato Abreu', 'github@mentordosnerds.com'));
        $this->composer->getLicense()
            ->willReturn('MIT');
        $this->clock->now()
            ->willReturn(new DateTimeImmutable('2026-01-01 00:00:00'));
        $this->renderer->render(
            'docblock/.docheader',
            Argument::that(static fn(array $variables): bool => 'fast-forward/dev-tools' === $variables['package']
                && '2026' === $variables['year']
                && isset($variables['links']['rfc2119'])),
        )
            ->willReturn('Content');
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
