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
use FastForward\DevTools\Console\Command\DocsCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
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
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Twig\Environment;

#[CoversClass(DocsCommand::class)]
final class DocsCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $renderer;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $composer;

    private ObjectProphecy $logger;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $process;

    private DocsCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->processBuilder = $this->prophesize(ProcessBuilderInterface::class);
        $this->processQueue = $this->prophesize(ProcessQueueInterface::class);
        $this->renderer = $this->prophesize(Environment::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->composer = $this->prophesize(ComposerJsonInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->process = $this->prophesize(Process::class);

        $this->input->getOption('source')
            ->willReturn('docs');
        $this->input->getOption('target')
            ->willReturn('.dev-tools');
        $this->input->getOption('cache-dir')
            ->willReturn('tmp/cache/phpdoc');
        $this->input->getOption('template')
            ->willReturn('vendor/fast-forward/phpdoc-bootstrap-template');
        $this->input->getOption('json')
            ->willReturn(false);
        $this->output->getVerbosity()
            ->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $this->output->isDecorated()
            ->willReturn(false);
        $this->output->getFormatter()
            ->willReturn(new OutputFormatter());
        $this->filesystem->getAbsolutePath('docs')
            ->willReturn('/repo/docs');
        $this->filesystem->getAbsolutePath('.dev-tools')
            ->willReturn('/repo/.dev-tools');
        $this->filesystem->getAbsolutePath('tmp/cache/phpdoc')
            ->willReturn('/repo/tmp/cache/phpdoc');
        $this->filesystem->getAbsolutePath('phpdocumentor.xml', '/repo/tmp/cache/phpdoc')
            ->willReturn('/repo/tmp/cache/phpdoc/phpdocumentor.xml');
        $this->filesystem->makePathRelative('/repo/docs')
            ->willReturn('docs');
        $this->filesystem->exists('/repo/docs')
            ->willReturn(true);
        $this->composer->getAutoload('psr-4')
            ->willReturn([
                'FastForward\\DevTools\\' => 'src/',
            ]);
        $this->composer->getName()
            ->willReturn('fast-forward/dev-tools');
        $this->renderer->render('phpdocumentor.xml', Argument::type('array'))->willReturn('<phpdocumentor />');
        $this->processBuilder->withArgument(Argument::any())->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument(Argument::any(), Argument::any())->willReturn(
            $this->processBuilder->reveal()
        );
        $this->processBuilder->build('vendor/bin/phpdoc')
            ->willReturn($this->process->reveal());

        $this->command = new DocsCommand(
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->renderer->reveal(),
            $this->filesystem->reveal(),
            $this->composer->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillFailWhenSourceDirectoryIsMissing(): void
    {
        $this->filesystem->exists('/repo/docs')
            ->willReturn(false);
        $this->logger->info('Generating API documentation...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->error(
            'Source directory not found: {source}',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface),
        )->shouldBeCalled();

        self::assertSame(DocsCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenProcessQueueSucceeds(): void
    {
        $this->filesystem->dumpFile('phpdocumentor.xml', '<phpdocumentor />', '/repo/tmp/cache/phpdoc')
            ->shouldBeCalled();
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(DocsCommand::SUCCESS)
            ->shouldBeCalled();
        $this->logger->info('Generating API documentation...', Argument::that(
            static fn(array $context): bool => $context['input'] instanceof InputInterface
        ))
            ->shouldBeCalled();
        $this->logger->info(
            'API documentation generated successfully.',
            Argument::that(static fn(array $context): bool => $context['input'] instanceof InputInterface
                && $context['output'] instanceof OutputInterface),
        )->shouldBeCalled();

        self::assertSame(DocsCommand::SUCCESS, $this->executeCommand());
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
