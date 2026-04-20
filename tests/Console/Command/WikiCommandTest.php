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
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Path;

use function Safe\getcwd;

#[CoversClass(WikiCommand::class)]
final class WikiCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $processBuilder;

    private ObjectProphecy $processQueue;

    private ObjectProphecy $composer;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $gitClient;

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
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->process = $this->prophesize(Process::class);

        $this->composer->getDescription()
            ->willReturn('Fast Forward Dev Tools plugin');
        $this->composer->getAutoload('psr-4')
            ->willReturn([
                'FastForward\\DevTools\\' => 'src/',
            ]);

        $this->processBuilder->withArgument(Argument::any())
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument(Argument::any(), Argument::any())
            ->willReturn($this->processBuilder->reveal());

        $this->processBuilder->build(Argument::any())
            ->willReturn($this->process->reveal());

        $this->command = new WikiCommand(
            $this->processBuilder->reveal(),
            $this->processQueue->reveal(),
            $this->composer->reveal(),
            $this->filesystem->reveal(),
            $this->gitClient->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('wiki', $this->command->getName());
        self::assertSame('Generates API documentation in Markdown format.', $this->command->getDescription());
        self::assertSame(
            'This command generates API documentation in Markdown format using phpDocumentor. '
            . 'It accepts an optional `--target` option to specify the output directory and `--init` to initialize the wiki submodule.',
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

        self::assertTrue($definition->hasOption('target'));
        self::assertTrue($definition->hasOption('cache-dir'));
        self::assertTrue($definition->hasOption('init'));
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenProcessQueueSucceeds(): void
    {
        $this->input->getOption('target')
            ->willReturn('.github/wiki');
        $this->input->getOption('cache-dir')
            ->willReturn('tmp/cache/phpdoc');
        $this->input->getOption('init')
            ->willReturn(false);

        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalled();

        $this->processQueue->run()
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalled();

        $this->output->writeln('<info>Generating API documentation...</info>')
            ->shouldBeCalled();

        $result = $this->executeCommand();

        self::assertSame(WikiCommand::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureWhenProcessQueueFails(): void
    {
        $this->input->getOption('target')
            ->willReturn('.github/wiki');
        $this->input->getOption('cache-dir')
            ->willReturn('tmp/cache/phpdoc');
        $this->input->getOption('init')
            ->willReturn(false);

        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalled();

        $this->processQueue->run()
            ->willReturn(ProcessQueueInterface::FAILURE)
            ->shouldBeCalled();

        $this->output->writeln('<info>Generating API documentation...</info>')
            ->shouldBeCalled();

        $result = $this->executeCommand();

        self::assertSame(WikiCommand::FAILURE, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillBuildProcessWithCorrectArguments(): void
    {
        $this->input->getOption('target')
            ->willReturn('.github/wiki');
        $this->input->getOption('cache-dir')
            ->willReturn('tmp/cache/phpdoc');
        $this->input->getOption('init')
            ->willReturn(false);

        $this->processBuilder->withArgument('--visibility', 'public,protected')
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument('--template', Argument::any())
            ->willReturn($this->processBuilder->reveal());
        $this->processBuilder->withArgument(Argument::any(), Argument::any())
            ->willReturn($this->processBuilder->reveal());

        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalled();

        $this->processQueue->run()
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalled();

        $this->output->writeln(Argument::type('string'))
            ->shouldBeCalled();

        $result = $this->executeCommand();

        self::assertSame(WikiCommand::SUCCESS, $result);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithInitWillSkipExistingWikiSubmodule(): void
    {
        $this->input->getOption('target')
            ->willReturn('.github/wiki');
        $this->input->getOption('init')
            ->willReturn(true);

        $this->filesystem->getAbsolutePath('.github/wiki')
            ->willReturn('/app/.github/wiki');
        $this->filesystem->exists('/app/.github/wiki')
            ->willReturn(true);

        $this->processQueue->add(Argument::cetera())
            ->shouldNotBeCalled();

        $this->output->writeln(Argument::containingString('already exists'))
            ->shouldBeCalled();

        self::assertSame(WikiCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWithInitWillAddWikiSubmoduleWhenTargetDoesNotExist(): void
    {
        $wikiSubmodulePath = '/app/.github/wiki';
        $expectedRelativePath = Path::makeRelative($wikiSubmodulePath, getcwd());

        $this->input->getOption('target')
            ->willReturn('.github/wiki');
        $this->input->getOption('init')
            ->willReturn(true);

        $this->filesystem->getAbsolutePath('.github/wiki')
            ->willReturn($wikiSubmodulePath);
        $this->filesystem->exists($wikiSubmodulePath)
            ->willReturn(false);
        $this->gitClient->getConfig('remote.origin.url', getcwd())
            ->willReturn('git@github.com:php-fast-forward/dev-tools.git')
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('submodule')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('add')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument('git@github.com:php-fast-forward/dev-tools.wiki.git')
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->withArgument($expectedRelativePath)
            ->willReturn($this->processBuilder->reveal())
            ->shouldBeCalledOnce();
        $this->processBuilder->build('git')
            ->willReturn($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->run($this->output->reveal())
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalledOnce();

        self::assertSame(WikiCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipDefaultPackageNameWhenPsr4AutoloadIsEmpty(): void
    {
        $this->composer->getAutoload('psr-4')
            ->willReturn([]);
        $this->input->getOption('target')
            ->willReturn('.github/wiki');
        $this->input->getOption('cache-dir')
            ->willReturn('tmp/cache/phpdoc');
        $this->input->getOption('init')
            ->willReturn(false);

        $this->processBuilder->withArgument('--defaultpackagename', Argument::any())
            ->shouldNotBeCalled();
        $this->processQueue->add($this->process->reveal())
            ->shouldBeCalledOnce();
        $this->processQueue->run()
            ->willReturn(ProcessQueueInterface::SUCCESS)
            ->shouldBeCalledOnce();

        self::assertSame(WikiCommand::SUCCESS, $this->executeCommand());
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
