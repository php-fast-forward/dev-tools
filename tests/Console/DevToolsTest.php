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

namespace FastForward\DevTools\Tests\Console;

use FastForward\DevTools\Console\CommandLoader\DevToolsCommandLoader;
use FastForward\DevTools\Console\Command\SelfUpdateCommand;
use FastForward\DevTools\Console\DevTools;
use FastForward\DevTools\Console\Formatter\LogLevelOutputFormatter;
use FastForward\DevTools\Console\Output\GithubActionOutput;
use FastForward\DevTools\Environment\EnvironmentInterface;
use FastForward\DevTools\Environment\RuntimeEnvironment;
use FastForward\DevTools\Filesystem\FinderFactory;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Path\ManagedWorkspace;
use FastForward\DevTools\Path\WorkingProjectPathResolver;
use FastForward\DevTools\Process\ColorPreservingProcessEnvironmentConfigurator;
use FastForward\DevTools\Process\CompositeProcessEnvironmentConfigurator;
use FastForward\DevTools\Process\ProcessBuilder;
use FastForward\DevTools\Process\ProcessQueue;
use FastForward\DevTools\Process\XdebugDisablingProcessEnvironmentConfigurator;
use FastForward\DevTools\Reflection\ClassReflection;
use FastForward\DevTools\SelfUpdate\ComposerSelfUpdateRunner;
use FastForward\DevTools\SelfUpdate\ComposerSelfUpdateScopeResolver;
use FastForward\DevTools\SelfUpdate\ComposerVersionChecker;
use FastForward\DevTools\SelfUpdate\SelfUpdateRunnerInterface;
use FastForward\DevTools\SelfUpdate\SelfUpdateScopeResolverInterface;
use FastForward\DevTools\SelfUpdate\VersionCheckNotifier;
use FastForward\DevTools\SelfUpdate\VersionCheckNotifierInterface;
use FastForward\DevTools\SelfUpdate\WorkingDirectorySwitcher;
use FastForward\DevTools\SelfUpdate\WorkingDirectorySwitcherInterface;
use FastForward\DevTools\ServiceProvider\DevToolsServiceProvider;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\CompleteCommand;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

use function Safe\putenv;

#[CoversClass(DevTools::class)]
#[UsesClass(DevToolsPathResolver::class)]
#[UsesClass(ManagedWorkspace::class)]
#[UsesClass(DevToolsCommandLoader::class)]
#[UsesClass(FinderFactory::class)]
#[UsesClass(DevToolsServiceProvider::class)]
#[UsesClass(WorkingProjectPathResolver::class)]
#[UsesClass(SelfUpdateCommand::class)]
#[UsesClass(ClassReflection::class)]
#[UsesClass(LogLevelOutputFormatter::class)]
#[UsesClass(GithubActionOutput::class)]
#[UsesClass(RuntimeEnvironment::class)]
#[UsesClass(ColorPreservingProcessEnvironmentConfigurator::class)]
#[UsesClass(CompositeProcessEnvironmentConfigurator::class)]
#[UsesClass(ProcessBuilder::class)]
#[UsesClass(ProcessQueue::class)]
#[UsesClass(XdebugDisablingProcessEnvironmentConfigurator::class)]
#[UsesClass(ComposerSelfUpdateRunner::class)]
#[UsesClass(ComposerSelfUpdateScopeResolver::class)]
#[UsesClass(ComposerVersionChecker::class)]
#[UsesClass(VersionCheckNotifier::class)]
#[UsesClass(WorkingDirectorySwitcher::class)]
final class DevToolsTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CommandLoaderInterface>
     */
    private ObjectProphecy $commandLoader;

    /**
     * @var ObjectProphecy<WorkingDirectorySwitcherInterface>
     */
    private ObjectProphecy $workingDirectorySwitcher;

    /**
     * @var ObjectProphecy<VersionCheckNotifierInterface>
     */
    private ObjectProphecy $versionCheckNotifier;

    /**
     * @var ObjectProphecy<SelfUpdateRunnerInterface>
     */
    private ObjectProphecy $selfUpdateRunner;

    /**
     * @var ObjectProphecy<SelfUpdateScopeResolverInterface>
     */
    private ObjectProphecy $selfUpdateScopeResolver;

    /**
     * @var ObjectProphecy<EnvironmentInterface>
     */
    private ObjectProphecy $environment;

    private DevTools $devTools;

    private string|false $originalWorkspaceDirectoryEnv;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        $this->commandLoader = $this->prophesize(CommandLoaderInterface::class);
        $this->commandLoader->getNames()
            ->willReturn([]);
        $this->commandLoader->has(Argument::type('string'))
            ->willReturn(false);
        $this->workingDirectorySwitcher = $this->prophesize(WorkingDirectorySwitcherInterface::class);
        $this->versionCheckNotifier = $this->prophesize(VersionCheckNotifierInterface::class);
        $this->selfUpdateRunner = $this->prophesize(SelfUpdateRunnerInterface::class);
        $this->selfUpdateScopeResolver = $this->prophesize(SelfUpdateScopeResolverInterface::class);
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->originalWorkspaceDirectoryEnv = getenv(ManagedWorkspace::ENV_WORKSPACE_DIR);
        $this->devTools = $this->createDevTools();
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        if (false === $this->originalWorkspaceDirectoryEnv) {
            putenv(ManagedWorkspace::ENV_WORKSPACE_DIR);

            return;
        }

        putenv(ManagedWorkspace::ENV_WORKSPACE_DIR . '=' . $this->originalWorkspaceDirectoryEnv);
    }

    /**
     * @return void
     */
    #[Test]
    public function getDefaultCommandsWillReturnFrameworkDefaultsOnly(): void
    {
        $commands = $this->invokeGetDefaultCommands($this->devTools);

        self::assertCount(4, $commands);
        self::assertInstanceOf(HelpCommand::class, $commands[0]);
        self::assertInstanceOf(ListCommand::class, $commands[1]);
        self::assertInstanceOf(CompleteCommand::class, $commands[2]);
        self::assertInstanceOf(DumpCompletionCommand::class, $commands[3]);
    }

    /**
     * @return void
     */
    #[Test]
    public function constructorWillSetApplicationNameAndExposeLoaderCommands(): void
    {
        $customCommand = new class extends Command {
            public function __construct()
            {
                parent::__construct('custom');
            }
        };

        $this->commandLoader->getNames()
            ->willReturn(['custom']);
        $this->commandLoader->has('custom')
            ->willReturn(true);
        $this->commandLoader->get('custom')
            ->willReturn($customCommand);

        self::assertSame('Fast Forward Dev Tools', $this->devTools->getName());
        self::assertTrue($this->devTools->has('custom'));
        self::assertSame($customCommand, $this->devTools->get('custom'));
    }

    /**
     * @return void
     */
    #[Test]
    public function constructorWillRegisterGlobalRuntimeOptions(): void
    {
        $definition = $this->devTools->getDefinition();

        self::assertTrue($definition->hasOption('working-dir'));
        self::assertSame('d', $definition->getOption('working-dir')->getShortcut());
        self::assertTrue($definition->hasOption('workspace-dir'));
        self::assertSame('w', $definition->getOption('workspace-dir')->getShortcut());
        self::assertTrue($definition->hasOption('auto-update'));
        self::assertTrue($definition->hasOption('no-logo'));
        self::assertFalse($definition->getOption('no-logo')->acceptValue());
    }

    /**
     * @return void
     */
    #[Test]
    public function doRunWillRenderLogoUnlessNoLogoOptionIsProvided(): void
    {
        $input = new ArrayInput([
            'command' => 'list',
        ]);

        $output = new BufferedOutput();

        $this->environment->get('FAST_FORWARD_AUTO_UPDATE', '')
            ->willReturn('');
        $this->workingDirectorySwitcher->switchTo(null)
            ->shouldBeCalledOnce();
        $this->versionCheckNotifier->notify($output)
            ->shouldBeCalledOnce();

        $result = $this->invokeDoRun($input, $output);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('_____', $output->fetch());
    }

    /**
     * @return void
     */
    #[Test]
    public function doRunWillNotRenderLogoWhenNoLogoOptionIsSet(): void
    {
        $input = new ArrayInput([
            '--no-logo' => true,
            'command' => 'list',
        ]);

        $output = new BufferedOutput();

        $this->environment->get('FAST_FORWARD_AUTO_UPDATE', '')
            ->willReturn('');
        $this->workingDirectorySwitcher->switchTo(null)
            ->shouldBeCalledOnce();
        $this->versionCheckNotifier->notify($output)
            ->shouldNotBeCalled();

        $this->invokeDoRun($input, $output);

        self::assertStringNotContainsString('_____', $output->fetch());
    }

    /**
     * @return void
     */
    #[Test]
    public function doRunWillNotRenderLogoWhenJsonOptionIsProvided(): void
    {
        $command = new class extends Command {
            public function __construct()
            {
                parent::__construct('standards');
            }

            protected function configure(): void
            {
                $this->addOption(name: 'json', mode: InputOption::VALUE_NONE, description: 'Emit structured JSON output.');
                $this->setCode(static fn(InputInterface $input, OutputInterface $output): int => Command::SUCCESS);
            }
        };

        $this->commandLoader->has('standards')
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->commandLoader->get('standards')
            ->willReturn($command)
            ->shouldBeCalledOnce();
        $input = new ArrayInput([
            'command' => 'standards',
            '--json' => true,
        ]);

        $output = new BufferedOutput();

        $this->environment->get('FAST_FORWARD_AUTO_UPDATE', '')
            ->willReturn('');
        $this->workingDirectorySwitcher->switchTo(null)
            ->shouldBeCalledOnce();
        $this->versionCheckNotifier->notify($output)
            ->shouldNotBeCalled();

        $result = $this->invokeDoRun($input, $output);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringNotContainsString('_____', $output->fetch());
    }

    /**
     * @return void
     */
    #[Test]
    public function doRunWillNotRenderLogoWhenPrettyJsonOptionIsProvided(): void
    {
        $command = new class extends Command {
            public function __construct()
            {
                parent::__construct('standards');
            }

            protected function configure(): void
            {
                $this->addOption(name: 'pretty-json', mode: InputOption::VALUE_NONE, description: 'Emit pretty JSON output.');
                $this->setCode(static fn(InputInterface $input, OutputInterface $output): int => Command::SUCCESS);
            }
        };

        $this->commandLoader->has('standards')
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->commandLoader->get('standards')
            ->willReturn($command)
            ->shouldBeCalledOnce();
        $input = new ArrayInput([
            'command' => 'standards',
            '--pretty-json' => true,
        ]);

        $output = new BufferedOutput();

        $this->environment->get('FAST_FORWARD_AUTO_UPDATE', '')
            ->willReturn('');
        $this->workingDirectorySwitcher->switchTo(null)
            ->shouldBeCalledOnce();
        $this->versionCheckNotifier->notify($output)
            ->shouldNotBeCalled();

        $result = $this->invokeDoRun($input, $output);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringNotContainsString('_____', $output->fetch());
    }

    /**
     * @return void
     */
    #[Test]
    #[TestWith(['changelog:show'])]
    #[TestWith(['changelog:next-version'])]
    public function doRunWillNotRenderLogoWhenRawOutputCommandIsRequested(string $commandName): void
    {
        $command = new class extends Command {
            public function __construct()
            {
                parent::__construct('placeholder');
                $this->setCode(static fn(InputInterface $input, OutputInterface $output): int => Command::SUCCESS);
            }
        };

        $command->setName($commandName);

        $this->commandLoader->has($commandName)
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->commandLoader->get($commandName)
            ->willReturn($command)
            ->shouldBeCalledOnce();

        $input = new ArrayInput([
            'command' => $commandName,
        ]);

        $output = new BufferedOutput();

        $this->environment->get('FAST_FORWARD_AUTO_UPDATE', '')
            ->willReturn('');
        $this->workingDirectorySwitcher->switchTo(null)
            ->shouldBeCalledOnce();
        $this->versionCheckNotifier->notify($output)
            ->shouldNotBeCalled();

        $result = $this->invokeDoRun($input, $output);

        self::assertSame(Command::SUCCESS, $result);
        self::assertStringNotContainsString('_____', $output->fetch());
    }

    /**
     * @return void
     */
    #[Test]
    public function allWillReturnLoaderCommandsWithPreservedKeys(): void
    {
        $commands = [
            'agents' => new class extends Command {
                public function __construct()
                {
                    parent::__construct('agents');
                }
            },
            'sync' => new class extends Command {
                public function __construct()
                {
                    parent::__construct('sync');
                }
            },
        ];

        $this->commandLoader->getNames()
            ->willReturn(array_keys($commands));
        $this->commandLoader->has('agents')
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->commandLoader->has('sync')
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $this->commandLoader->get('agents')
            ->willReturn($commands['agents']);
        $this->commandLoader->get('sync')
            ->willReturn($commands['sync']);

        $providedCommands = $this->devTools->all();

        self::assertArrayHasKey('agents', $providedCommands);
        self::assertArrayHasKey('sync', $providedCommands);
        self::assertSame($commands['agents'], $providedCommands['agents']);
        self::assertSame($commands['sync'], $providedCommands['sync']);
    }

    /**
     * @return void
     */
    #[Test]
    public function createWillReturnInstanceOfDevTools(): void
    {
        $reflectionProperty = new ReflectionProperty(DevTools::class, 'container');
        $reflectionProperty->setValue(null, null);

        $devTools = DevTools::create();

        self::assertInstanceOf(DevTools::class, $devTools);
        self::assertSame($devTools, DevTools::create());
    }

    /**
     * @return void
     */
    #[Test]
    public function isSelfUpdateCommandWillUseSelfUpdateCommandAttributeNamesAndAliases(): void
    {
        foreach (['dev-tools:self-update', 'self-update', 'selfupdate'] as $commandName) {
            $input = $this->prophesize(InputInterface::class);
            $input->getFirstArgument()
                ->willReturn($commandName);

            self::assertTrue($this->invokeIsSelfUpdateCommand($input->reveal()));
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function isSelfUpdateCommandWillRejectOtherCommands(): void
    {
        $input = $this->prophesize(InputInterface::class);
        $input->getFirstArgument()
            ->willReturn('standards');

        self::assertFalse($this->invokeIsSelfUpdateCommand($input->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function runAutoUpdateWhenRequestedWillUpdateGlobalInstallationWhenCurrentBinaryIsGlobal(): void
    {
        $input = $this->prophesize(InputInterface::class);
        $output = $this->prophesize(OutputInterface::class);
        $input->hasParameterOption('--auto-update', true)
            ->willReturn(true);
        $this->environment->get('FAST_FORWARD_AUTO_UPDATE', '')
            ->willReturn('');
        $this->selfUpdateScopeResolver->isGlobalInstallation()
            ->willReturn(true);
        $this->selfUpdateRunner->update(true, $output->reveal())
            ->willReturn(Command::SUCCESS)
            ->shouldBeCalledOnce();
        $output->writeln(Argument::type('string'))
            ->shouldNotBeCalled();

        $this->invokeRunAutoUpdateWhenRequested($input->reveal(), $output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWorkspaceDirectoryWillExposeWorkspaceDirectoryToManagedWorkspace(): void
    {
        $input = $this->prophesize(InputInterface::class);
        $input->getParameterOption('--workspace-dir', null, true)
            ->willReturn('.artifacts');

        $this->invokeConfigureWorkspaceDirectory($input->reveal());

        self::assertSame('.artifacts', ManagedWorkspace::getWorkspaceRoot());
    }

    /**
     * @return DevTools
     */
    private function createDevTools(): DevTools
    {
        return new DevTools(
            $this->commandLoader->reveal(),
            $this->workingDirectorySwitcher->reveal(),
            $this->versionCheckNotifier->reveal(),
            $this->selfUpdateRunner->reveal(),
            $this->selfUpdateScopeResolver->reveal(),
            $this->environment->reveal(),
        );
    }

    /**
     * @param DevTools $devTools
     *
     * @return array<int, Command>
     */
    private function invokeGetDefaultCommands(DevTools $devTools): array
    {
        $reflectionMethod = new ReflectionMethod($devTools, 'getDefaultCommands');

        /** @var array<int, Command> $commands */
        $commands = $reflectionMethod->invoke($devTools);

        return $commands;
    }

    /**
     * @param InputInterface $input
     *
     * @return bool
     */
    private function invokeIsSelfUpdateCommand(InputInterface $input): bool
    {
        $reflectionMethod = new ReflectionMethod($this->devTools, 'isSelfUpdateCommand');

        return (bool) $reflectionMethod->invoke($this->devTools, $input);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    private function invokeRunAutoUpdateWhenRequested(InputInterface $input, OutputInterface $output): void
    {
        $reflectionMethod = new ReflectionMethod($this->devTools, 'runAutoUpdateWhenRequested');
        $reflectionMethod->invoke($this->devTools, $input, $output);
    }

    /**
     * @param InputInterface $input
     *
     * @return void
     */
    private function invokeConfigureWorkspaceDirectory(InputInterface $input): void
    {
        $reflectionMethod = new ReflectionMethod($this->devTools, 'configureWorkspaceDirectory');
        $reflectionMethod->invoke($this->devTools, $input);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    private function invokeDoRun(InputInterface $input, OutputInterface $output): int
    {
        $reflectionMethod = new ReflectionMethod($this->devTools, 'doRun');

        return (int) $reflectionMethod->invoke($this->devTools, $input, $output);
    }
}
