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

namespace FastForward\DevTools\Console;

use FastForward\DevTools\Console\Command\SelfUpdateCommand;
use Override;
use FastForward\DevTools\Environment\EnvironmentInterface;
use FastForward\DevTools\SelfUpdate\SelfUpdateRunnerInterface;
use FastForward\DevTools\SelfUpdate\VersionCheckNotifierInterface;
use FastForward\DevTools\SelfUpdate\WorkingDirectorySwitcherInterface;
use FastForward\DevTools\ServiceProvider\DevToolsServiceProvider;
use DI\Container;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Wraps the fast-forward console tooling suite conceptually as an isolated application instance.
 * Extending the base application, it MUST provide default command injections safely.
 */
final class DevTools extends Application
{
    private const string LOGO = <<<'LOGO'
         ____             _____           _
        |  _ \  _____   _|_   _|__   ___ | |___
        | | | |/ _ \ \ / / | |/ _ \ / _ \| / __|
        | |_| |  __/\ V /  | | (_) | (_) | \__ \
        |____/ \___| \_/   |_|\___/ \___/|_|___/
        LOGO;

    /**
     * @var ContainerInterface holds the static container instance for global access within the DevTools context
     */
    private static ?ContainerInterface $container = null;

    /**
     * Initializes the DevTools global context and dependency graph.
     *
     * The method MUST define default configurations and MAY accept an explicit command provider.
     * It SHALL instruct the runner to treat the `standards` command generically as its default endpoint.
     *
     * @param CommandLoaderInterface $commandLoader the command loader responsible for providing command instances
     * @param WorkingDirectorySwitcherInterface $workingDirectorySwitcher switches the process working directory
     * @param VersionCheckNotifierInterface $versionCheckNotifier emits non-blocking version freshness warnings
     * @param SelfUpdateRunnerInterface $selfUpdateRunner runs explicit or automatic self-update flows
     * @param EnvironmentInterface $environment reads environment flags for optional auto-update behavior
     */
    public function __construct(
        CommandLoaderInterface $commandLoader,
        private readonly WorkingDirectorySwitcherInterface $workingDirectorySwitcher,
        private readonly VersionCheckNotifierInterface $versionCheckNotifier,
        private readonly SelfUpdateRunnerInterface $selfUpdateRunner,
        private readonly EnvironmentInterface $environment,
    ) {
        parent::__construct('Fast Forward Dev Tools');

        $this->setDefaultCommand('dev-tools:standards');
        $this->setCommandLoader($commandLoader);
    }

    /**
     * Gets the help message for the DevTools application, including the ASCII logo.
     *
     * @return string
     */
    #[Override]
    public function getHelp(): string
    {
        return self::LOGO . "\n\n" . parent::getHelp();
    }

    /**
     * Returns the application-level input definition with DevTools runtime options.
     *
     * @return InputDefinition the global application input definition
     */
    #[Override]
    protected function getDefaultInputDefinition(): InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();

        $definition->addOption(new InputOption(
            name: 'working-dir',
            shortcut: 'd',
            mode: InputOption::VALUE_REQUIRED,
            description: 'Run DevTools as if it was started in the given directory.',
        ));

        $definition->addOption(new InputOption(
            name: 'auto-update',
            mode: InputOption::VALUE_NONE,
            description: 'Update fast-forward/dev-tools before running the requested command.',
        ));

        return $definition;
    }

    /**
     * Runs the application after applying global runtime options.
     *
     * @param InputInterface $input the application input
     * @param OutputInterface $output the application output
     *
     * @return int the application status code
     */
    #[Override]
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->workingDirectorySwitcher->switchTo($this->getWorkingDirectoryOption($input));
        } catch (Throwable $throwable) {
            $output->writeln(\sprintf('<error>%s</error>', $throwable->getMessage()));

            return Command::FAILURE;
        }

        if (! $this->isSelfUpdateCommand($input)) {
            $this->runAutoUpdateWhenRequested($input, $output);
            $this->versionCheckNotifier->notify($output);
        }

        return parent::doRun($input, $output);
    }

    /**
     * Create DevTools instance from container.
     *
     * @return DevTools
     */
    public static function create(): self
    {
        return self::getContainer()->get(self::class);
    }

    /**
     * Retrieves the shared DevTools service container.
     */
    public static function getContainer(): ContainerInterface
    {
        if (! self::$container instanceof ContainerInterface) {
            $serviceProvider = new DevToolsServiceProvider();
            self::$container = new Container($serviceProvider->getFactories());
        }

        return self::$container;
    }

    /**
     * Resolves the raw working-directory option before command parsing.
     *
     * @param InputInterface $input the application input
     */
    private function getWorkingDirectoryOption(InputInterface $input): ?string
    {
        $workingDirectory = $input->getParameterOption(['--working-dir', '-d'], null, true);

        return \is_string($workingDirectory) ? $workingDirectory : null;
    }

    /**
     * Runs an explicit automatic update without letting failures block the requested command.
     *
     * @param InputInterface $input the application input
     * @param OutputInterface $output the application output
     */
    private function runAutoUpdateWhenRequested(InputInterface $input, OutputInterface $output): void
    {
        $autoUpdateMode = $this->environment->get('FAST_FORWARD_AUTO_UPDATE', '');

        if (! $input->hasParameterOption('--auto-update', true) && ! $this->isTruthyAutoUpdateMode($autoUpdateMode)) {
            return;
        }

        try {
            $statusCode = $this->selfUpdateRunner->update('global' === $autoUpdateMode, $output);
        } catch (Throwable) {
            $output->writeln('<comment>DevTools auto-update failed; continuing with the requested command.</comment>');

            return;
        }

        if (Command::SUCCESS !== $statusCode) {
            $output->writeln('<comment>DevTools auto-update failed; continuing with the requested command.</comment>');
        }
    }

    /**
     * Detects whether the current invocation targets the self-update command.
     *
     * @param InputInterface $input the application input
     */
    private function isSelfUpdateCommand(InputInterface $input): bool
    {
        return \in_array($input->getFirstArgument(), SelfUpdateCommand::getCommandNames(), true);
    }

    /**
     * Interprets environment values that enable auto-update.
     *
     * @param string|null $mode the FAST_FORWARD_AUTO_UPDATE value
     */
    private function isTruthyAutoUpdateMode(?string $mode): bool
    {
        return null !== $mode && \in_array(strtolower($mode), ['1', 'true', 'yes', 'on', 'global'], true);
    }
}
