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

use FastForward\DevTools\Console\Command\RefactorCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(RefactorCommand::class)]
final class RefactorCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<FileLocatorInterface>
     */
    private ObjectProphecy $fileLocator;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<ProcessBuilderInterface>
     */
    private ObjectProphecy $processBuilder;

    /**
     * @var ObjectProphecy<ProcessQueueInterface>
     */
    private ObjectProphecy $processQueue;

    /**
     * @var ObjectProphecy<InputInterface>
     */
    private ObjectProphecy $input;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    /**
     * @var ObjectProphecy<Process>
     */
    private ObjectProphecy $process;

    private RefactorCommand $command;

    private const string CONFIG_PATH = '/path/to/rector.php';

    private const string TYPE_PERFECT_CONFIG_PATH = '/app/tmp/cache/phpstan/type-perfect.neon';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->process = $this->prophesize(Process::class);

        $this->fileLocator->locate(RefactorCommand::CONFIG)
            ->willReturn(self::CONFIG_PATH);

        $this->input->getOption('fix')
            ->willReturn(false);
        $this->input->getOption('config')
            ->willReturn(RefactorCommand::CONFIG);
        $this->input->getOption('type-perfect')
            ->willReturn(false);
        $this->input->getOption('type-perfect-groups')
            ->willReturn('null_over_false,no_mixed,narrow_param');

        $this->processBuilder->withArgument(Argument::cetera())
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->build('vendor/bin/rector')
            ->willReturn($this->process->reveal());

        $this->processQueue->run($this->output->reveal())
            ->willReturn(RefactorCommand::SUCCESS);

        $this->command = new RefactorCommand(
            $this->fileLocator->reveal(),
            $this->filesystem->reveal(),
            $this->processBuilder->reveal(),
            $this->processQueue->reveal()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('refactor', $this->command->getName());
        self::assertSame('Runs Rector for code refactoring.', $this->command->getDescription());
        self::assertSame('This command runs Rector to refactor your code.', $this->command->getHelp());
        self::assertSame(['rector'], $this->command->getAliases());
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillHaveExpectedOptions(): void
    {
        $definition = $this->command->getDefinition();

        self::assertTrue($definition->hasOption('fix'));
        self::assertTrue($definition->hasOption('config'));
        self::assertTrue($definition->hasOption('type-perfect'));
        self::assertTrue($definition->hasOption('type-perfect-groups'));
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunRectorProcessWithDryRunWhenFixIsFalse(): void
    {
        $this->processBuilder->withArgument('process')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->withArgument('--config')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->withArgument(self::CONFIG_PATH)
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->withArgument('--dry-run')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());

        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();

        $result = $this->executeCommand();

        self::assertSame(RefactorCommand::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunRectorProcessWithoutDryRunWhenFixIsTrue(): void
    {
        $this->input->getOption('fix')
            ->willReturn(true);

        $this->processBuilder->withArgument('--dry-run')
            ->shouldNotBeCalled();

        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();

        $result = $this->executeCommand();

        self::assertSame(RefactorCommand::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRunTypePerfectAfterRectorWhenRequested(): void
    {
        $typePerfectProcess = $this->prophesize(Process::class);

        $this->input->getOption('type-perfect')
            ->willReturn(true);
        $this->output->writeln('<info>Running Rector for code refactoring...</info>')
            ->shouldBeCalledOnce();
        $this->filesystem->exists('vendor/rector/type-perfect')
            ->willReturn(true);
        $this->filesystem->exists('vendor/phpstan/extension-installer')
            ->willReturn(true);
        $this->filesystem->exists('phpstan.neon')
            ->willReturn(true);
        $this->filesystem->getAbsolutePath('phpstan.neon')
            ->willReturn('/app/phpstan.neon');
        $this->filesystem->getAbsolutePath('tmp/cache/phpstan/type-perfect.neon')
            ->willReturn(self::TYPE_PERFECT_CONFIG_PATH);
        $this->filesystem->dirname(self::TYPE_PERFECT_CONFIG_PATH)
            ->willReturn('/app/tmp/cache/phpstan');
        $this->filesystem->mkdir('/app/tmp/cache/phpstan')
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile(
            self::TYPE_PERFECT_CONFIG_PATH,
            Argument::that(static fn(string $contents): bool => str_contains($contents, "includes:\n    - '/app/phpstan.neon'")
                && str_contains($contents, 'null_over_false: true')
                && str_contains($contents, 'no_mixed: true')
                && str_contains($contents, 'narrow_param: true')),
        )->shouldBeCalledOnce();

        $this->output->writeln('<info>Running Type Perfect safety checks...</info>')
            ->shouldBeCalledOnce();

        $this->processBuilder->withArgument('analyse')
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--configuration', self::TYPE_PERFECT_CONFIG_PATH)
            ->shouldBeCalledOnce()
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->build('vendor/bin/phpstan')
            ->willReturn($typePerfectProcess->reveal())
            ->shouldBeCalledOnce();

        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($typePerfectProcess->reveal())
            ->shouldBeCalledOnce();

        self::assertSame(RefactorCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillFailWhenTypePerfectPackageIsMissing(): void
    {
        $this->input->getOption('type-perfect')
            ->willReturn(true);
        $this->output->writeln('<info>Running Rector for code refactoring...</info>')
            ->shouldBeCalledOnce();
        $this->filesystem->exists('vendor/rector/type-perfect')
            ->willReturn(false);

        $this->output->writeln(
            '<error>Type Perfect support requires rector/type-perfect. Install it with "composer require rector/type-perfect --dev" before using --type-perfect.</error>'
        )->shouldBeCalledOnce();

        $this->processQueue->add(Argument::type(Process::class))
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->shouldNotBeCalled();

        self::assertSame(RefactorCommand::FAILURE, $this->executeCommand());
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
