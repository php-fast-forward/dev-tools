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
use FastForward\DevTools\Path\WorkingProjectPathResolver;
use FastForward\DevTools\Process\ColorPreservingProcessEnvironmentConfigurator;
use FastForward\DevTools\Process\CompositeProcessEnvironmentConfigurator;
use FastForward\DevTools\Process\ProcessBuilder;
use FastForward\DevTools\Process\ProcessQueue;
use FastForward\DevTools\Process\XdebugDisablingProcessEnvironmentConfigurator;
use FastForward\DevTools\Reflection\ClassReflection;
use FastForward\DevTools\SelfUpdate\ComposerSelfUpdateRunner;
use FastForward\DevTools\SelfUpdate\ComposerVersionChecker;
use FastForward\DevTools\SelfUpdate\SelfUpdateRunnerInterface;
use FastForward\DevTools\SelfUpdate\VersionCheckNotifier;
use FastForward\DevTools\SelfUpdate\VersionCheckNotifierInterface;
use FastForward\DevTools\SelfUpdate\WorkingDirectorySwitcher;
use FastForward\DevTools\SelfUpdate\WorkingDirectorySwitcherInterface;
use FastForward\DevTools\ServiceProvider\DevToolsServiceProvider;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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
use Symfony\Component\Console\Input\InputInterface;

#[CoversClass(DevTools::class)]
#[UsesClass(DevToolsPathResolver::class)]
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
     * @var ObjectProphecy<EnvironmentInterface>
     */
    private ObjectProphecy $environment;

    private DevTools $devTools;

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
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->devTools = $this->createDevTools();
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
        self::assertTrue($definition->hasOption('auto-update'));
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
     * @return DevTools
     */
    private function createDevTools(): DevTools
    {
        return new DevTools(
            $this->commandLoader->reveal(),
            $this->workingDirectorySwitcher->reveal(),
            $this->versionCheckNotifier->reveal(),
            $this->selfUpdateRunner->reveal(),
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
}
