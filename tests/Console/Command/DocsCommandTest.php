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

use FastForward\DevTools\Console\Command\DocsCommand;
use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Twig\Environment;

#[CoversClass(DocsCommand::class)]
final class DocsCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ProcessBuilderInterface>
     */
    private ObjectProphecy $processBuilder;

    /**
     * @var ObjectProphecy<ProcessQueueInterface>
     */
    private ObjectProphecy $processQueue;

    /**
     * @var ObjectProphecy<Environment>
     */
    private ObjectProphecy $renderer;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<ComposerJsonInterface>
     */
    private ObjectProphecy $composerJson;

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
        $this->composerJson = $this->prophesize(ComposerJsonInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->process = $this->prophesize(Process::class);

        $this->composerJson->getAutoload('psr-4')
            ->willReturn([
                'FastForward\\DevTools\\' => 'src/',
            ]);
        $this->composerJson->getName()
            ->willReturn('fast-forward/dev-tools');

        $this->input->getOption('source')
            ->willReturn('docs');
        $this->input->getOption('target')
            ->willReturn('public');
        $this->input->getOption('template')
            ->willReturn('default');
        $this->input->getOption('cache-dir')
            ->willReturn('tmp/cache/phpdoc');

        $this->command = new DocsCommand(
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->renderer->reveal(),
            $this->filesystem->reveal(),
            $this->composerJson->reveal()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('docs', $this->command->getName());
        self::assertSame('Generates API documentation.', $this->command->getDescription());
        self::assertSame('This command generates API documentation using phpDocumentor.', $this->command->getHelp());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillFailIfSourceDirectoryNotFound(): void
    {
        $this->output->writeln('<info>Generating API documentation...</info>')
            ->shouldBeCalled();

        $this->filesystem->getAbsolutePath('docs')
            ->willReturn('/app/docs');
        $this->filesystem->exists('/app/docs')
            ->willReturn(false);

        $this->output->writeln('<error>Source directory not found: /app/docs</error>')
            ->shouldBeCalled();

        $result = $this->executeCommand();

        self::assertSame(DocsCommand::FAILURE, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillGeneratePhpDocumentorConfigAndRunProcess(): void
    {
        $this->output->writeln('<info>Generating API documentation...</info>')
            ->shouldBeCalled();

        $this->filesystem->getAbsolutePath('docs')
            ->willReturn('/app/docs');
        $this->filesystem->exists('/app/docs')
            ->willReturn(true);
        $this->filesystem->getAbsolutePath('public')
            ->willReturn('/app/public');
        $this->filesystem->getAbsolutePath('tmp/cache/phpdoc')
            ->willReturn('/app/tmp/cache/phpdoc');

        $this->filesystem->makePathRelative('/app/docs')
            ->willReturn('docs/');

        $this->renderer->render('phpdocumentor.xml', Argument::type('array'))
            ->willReturn('RenderedXML');

        $this->filesystem->dumpFile('phpdocumentor.xml', 'RenderedXML', '/app/tmp/cache/phpdoc')
            ->shouldBeCalled();
        $this->filesystem->getAbsolutePath('phpdocumentor.xml', '/app/tmp/cache/phpdoc')
            ->willReturn('/app/tmp/cache/phpdoc/phpdocumentor.xml');

        $this->processBuilder->withArgument('--config', '/app/tmp/cache/phpdoc/phpdocumentor.xml')
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--ansi')
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--no-progress')
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--markers', 'TODO,FIXME,BUG,HACK')
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->build('vendor/bin/phpdoc')
            ->willReturn($this->process->reveal());

        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalled();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(DocsCommand::SUCCESS);

        $result = $this->executeCommand();

        self::assertSame(DocsCommand::SUCCESS, $result);
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
