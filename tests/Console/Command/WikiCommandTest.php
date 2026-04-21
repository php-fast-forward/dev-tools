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

use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Console\Command\WikiCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Git\GitClientInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(WikiCommand::class)]
final class WikiCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $composer;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $gitClient;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $process;

    private WikiCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->composer = $this->prophesize(ComposerJsonInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->gitClient = $this->prophesize(GitClientInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->process = $this->prophesize(Process::class);

        $this->composer->getDescription()
            ->willReturn('Fast Forward Dev Tools plugin');
        $this->composer->getAutoload('psr-4')
            ->willReturn([
                'FastForward\\DevTools\\' => 'src/',
            ]);
        $this->input->getOption('target')
            ->willReturn('.github/wiki');
        $this->input->getOption('cache-dir')
            ->willReturn('tmp/cache/phpdoc');
        $this->input->getOption('init')
            ->willReturn(false);
        $this->input->getOption('output-format')
            ->willReturn('text');
        $this->processBuilder->withArgument(Argument::any())->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument(Argument::any(), Argument::any())->willReturn(
            $this->processBuilder->reveal()
        );
        $this->processBuilder->build(Argument::any())->willReturn($this->process->reveal());

        $this->command = new WikiCommand(
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->composer->reveal(),
            $this->filesystem->reveal(),
            $this->gitClient->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenProcessQueueSucceeds(): void
    {
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(WikiCommand::SUCCESS)
            ->shouldBeCalled();
        $this->logger->info('Generating wiki documentation...')
            ->shouldBeCalled();
        $this->logger->info(
            'Wiki documentation generated successfully.',
            [
                'command' => 'wiki',
                'target' => '.github/wiki',
                'cache_dir' => 'tmp/cache/phpdoc',
                'process_output' => null,
            ],
        )->shouldBeCalled();

        self::assertSame(WikiCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithInitWillSkipExistingWikiSubmodule(): void
    {
        $this->input->getOption('init')
            ->willReturn(true);
        $this->filesystem->getAbsolutePath('.github/wiki')
            ->willReturn('/repo/.github/wiki');
        $this->filesystem->exists('/repo/.github/wiki')
            ->willReturn(true);
        $this->logger->info(
            'Wiki submodule already exists at {wiki_submodule_path}.',
            [
                'command' => 'wiki',
                'target' => '.github/wiki',
                'wiki_submodule_path' => '/repo/.github/wiki',
            ],
        )->shouldBeCalled();

        self::assertSame(WikiCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return int
     */
    private function executeCommand(): int
    {
        return (new ReflectionMethod($this->command, 'execute'))
            ->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
