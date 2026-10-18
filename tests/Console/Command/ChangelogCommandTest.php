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

use FastForward\DevTools\Console\Command\ChangelogCommand;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessEnvironmentConfiguratorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

use function Safe\getcwd;

#[CoversClass(ChangelogCommand::class)]
final class ChangelogCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function runWillForwardUnparsedArgumentsAndPreserveRawOutputAndExitStatus(): void
    {
        $builder = $this->prophesize(ProcessBuilderInterface::class);
        $environment = $this->prophesize(ProcessEnvironmentConfiguratorInterface::class);
        $process = new Process([\PHP_BINARY, '-r', 'echo "<info>literal markdown</info>\\n"; exit(7);']);
        $arguments = ['notes', '1.2.3', '--file=path with spaces.md', '--json', '$(touch forbidden)'];
        $builder->build(['cpx', '--skip-local', 'fast-forward/changelog:0.2.0', '--no-ansi', ...$arguments])
            ->willReturn($process)
            ->shouldBeCalledOnce();
        $output = new BufferedOutput();
        $environment->configure($process, $output)
            ->shouldBeCalledOnce();
        $command = new ChangelogCommand($builder->reveal(), $environment->reveal(), 80500);

        self::assertSame(7, $command->run(new ArgvInput(['dev-tools', 'changelog', ...$arguments]), $output));
        self::assertSame("<info>literal markdown</info>\n", $output->fetch());
        self::assertSame(getcwd(), $process->getWorkingDirectory());
    }

    /**
     * @return void
     */
    #[Test]
    public function runWillRemoveOnlyTheWrapperSeparatorAndPreserveLibraryAnsiAndOptions(): void
    {
        $builder = $this->prophesize(ProcessBuilderInterface::class);
        $environment = $this->prophesize(ProcessEnvironmentConfiguratorInterface::class);
        $process = new Process([\PHP_BINARY, '-r', 'exit(0);']);
        $builder->build(
            ['cpx', '--skip-local', 'fast-forward/changelog:0.2.0', 'add', '--no-ansi', '--help', '--', '-literal']
        )
            ->willReturn($process)
            ->shouldBeCalledOnce();
        $environment->configure($process, Argument::type(OutputInterface::class))->shouldBeCalledOnce();
        $command = new ChangelogCommand($builder->reveal(), $environment->reveal(), 80500);
        $output = new BufferedOutput();

        self::assertSame(
            0,
            $command->run(new ArgvInput([
                'dev-tools',
                'changelog',
                '--',
                'add',
                '--no-ansi',
                '--help',
                '--',
                '-literal',
            ]), $output)
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function runWillKeepStandardErrorSeparateWhenTheConsoleSupportsIt(): void
    {
        $builder = $this->prophesize(ProcessBuilderInterface::class);
        $environment = $this->prophesize(ProcessEnvironmentConfiguratorInterface::class);
        $process = new Process([\PHP_BINARY, '-r', 'fwrite(STDOUT, "notes"); fwrite(STDERR, "diagnostic");']);
        $builder->build(['cpx', '--skip-local', 'fast-forward/changelog:0.2.0', '--ansi', 'status'])->willReturn(
            $process
        );
        $errorOutput = new BufferedOutput();
        $output = $this->prophesize(ConsoleOutputInterface::class);
        $output->isDecorated()
            ->willReturn(true);
        $output->getErrorOutput()
            ->willReturn($errorOutput);
        $output->write('notes', false, OutputInterface::OUTPUT_RAW)->shouldBeCalledOnce();
        $environment->configure($process, $output->reveal())
            ->shouldBeCalledOnce();
        $command = new ChangelogCommand($builder->reveal(), $environment->reveal(), 80500);

        self::assertSame(0, $command->run(new ArgvInput(['dev-tools', 'changelog', 'status']), $output->reveal()));
        self::assertSame('diagnostic', $errorOutput->fetch());
    }

    /**
     * @return void
     */
    #[Test]
    public function runWillRejectAnOlderRuntimeBeforeStartingCpx(): void
    {
        $builder = $this->prophesize(ProcessBuilderInterface::class);
        $environment = $this->prophesize(ProcessEnvironmentConfiguratorInterface::class);
        $builder->build(Argument::any())->shouldNotBeCalled();
        $environment->configure(Argument::cetera())->shouldNotBeCalled();
        $tester = new CommandTester(new ChangelogCommand($builder->reveal(), $environment->reveal(), 80300));

        self::assertSame(1, $tester->execute([
            'arguments' => ['status'],
        ]));
        self::assertStringContainsString('requires PHP 8.5', $tester->getDisplay());
    }

    /**
     * @return void
     */
    #[Test]
    public function runWillConsumeWrapperOptionsAndForwardNonInteractiveMode(): void
    {
        $builder = $this->prophesize(ProcessBuilderInterface::class);
        $environment = $this->prophesize(ProcessEnvironmentConfiguratorInterface::class);
        $process = new Process([\PHP_BINARY, '-r', 'exit(0);']);
        $builder->build(
            [
                'cpx',
                '--skip-local',
                'fast-forward/changelog:0.2.0',
                '--no-ansi',
                '--no-interaction',
                'status',
                '--',
                '--no-logo',
            ]
        )
            ->willReturn($process)
            ->shouldBeCalledOnce();
        $environment->configure($process, Argument::type(OutputInterface::class))->shouldBeCalledOnce();
        $command = new ChangelogCommand($builder->reveal(), $environment->reveal(), 80500);
        $input = new ArgvInput([
            'dev-tools',
            'changelog',
            'status',
            '--working-dir',
            '/somewhere',
            '-w',
            '/artifacts',
            '--workspace-dir=/artifacts',
            '--working-dir=/somewhere',
            '--no-logo',
            '--auto-update',
            '--',
            '--no-logo',
        ]);
        $input->setInteractive(false);

        self::assertSame(0, $command->run($input, new BufferedOutput()));
    }
}
