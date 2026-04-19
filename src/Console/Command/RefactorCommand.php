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

namespace FastForward\DevTools\Console\Command;

use Composer\Command\BaseCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides functionality to execute automated code refactoring using Rector.
 * This class MUST NOT be extended and SHALL encapsulate the logic for Rector invocation.
 */
#[AsCommand(
    name: 'refactor',
    description: 'Runs Rector for code refactoring.',
    aliases: ['rector'],
    help: 'This command runs Rector to refactor your code.'
)]
final class RefactorCommand extends BaseCommand
{
    /**
     * @var string the default Rector configuration file
     */
    public const string CONFIG = 'rector.php';

    /**
     * @var string the generated PHPStan config used to run Type Perfect checks
     */
    private const string TYPE_PERFECT_CONFIG = 'tmp/cache/phpstan/type-perfect.neon';

    /**
     * @var list<string> the supported Type Perfect rule groups
     */
    private const array TYPE_PERFECT_GROUPS = [
        'null_over_false',
        'no_mixed',
        'narrow_param',
    ];

    /**
     * Creates a new RefactorCommand instance.
     *
     * @param FileLocatorInterface $fileLocator the file locator
     * @param FilesystemInterface $filesystem the filesystem used for Type Perfect configuration generation
     * @param ProcessBuilderInterface $processBuilder the process builder
     * @param ProcessQueueInterface $processQueue the process queue
     */
    public function __construct(
        private readonly FileLocatorInterface $fileLocator,
        private readonly FilesystemInterface $filesystem,
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
    ) {
        parent::__construct();
    }

    /**
     * Configures the refactor command options and description.
     *
     * This method MUST define the expected `--fix` option. It SHALL configure the command name
     * and descriptions accurately.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'fix',
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Automatically fix code refactoring issues.'
            )
            ->addOption(
                name: 'config',
                shortcut: 'c',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The path to the Rector configuration file.',
                default: self::CONFIG
            )
            ->addOption(
                name: 'type-perfect',
                mode: InputOption::VALUE_NONE,
                description: 'Run PHPStan Type Perfect checks after Rector using the supported Fast Forward preset.'
            )
            ->addOption(
                name: 'type-perfect-groups',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Comma-separated Type Perfect groups to enable.',
                default: implode(',', self::TYPE_PERFECT_GROUPS)
            );
    }

    /**
     * Executes the refactoring process securely.
     *
     * The method MUST execute Rector securely via `Process`. It SHALL use dry-run mode
     * unless the `--fix` option is specified. It MUST return `self::SUCCESS` or `self::FAILURE`.
     *
     * @param InputInterface $input the input interface to retrieve arguments properly
     * @param OutputInterface $output the output interface to log outputs
     *
     * @return int the status code denoting success or failure
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running Rector for code refactoring...</info>');

        $config = (string) $input->getOption('config');
        $processBuilder = $this->processBuilder
            ->withArgument('process')
            ->withArgument('--config')
            ->withArgument($this->fileLocator->locate($config));

        if (! $input->getOption('fix')) {
            $processBuilder = $processBuilder->withArgument('--dry-run');
        }

        $this->processQueue->add($processBuilder->build('vendor/bin/rector'));

        if ($input->getOption('type-perfect')) {
            $typePerfectGroups = $this->resolveTypePerfectGroups((string) $input->getOption('type-perfect-groups'));

            if ([] === $typePerfectGroups) {
                $output->writeln(
                    '<error>No valid Type Perfect groups were provided. Supported groups: '
                    . implode(', ', self::TYPE_PERFECT_GROUPS)
                    . '.</error>'
                );

                return self::FAILURE;
            }

            if (! $this->filesystem->exists('vendor/rector/type-perfect')) {
                $output->writeln(
                    '<error>Type Perfect support requires rector/type-perfect. Install it with '
                    . '"composer require rector/type-perfect --dev" before using --type-perfect.</error>'
                );

                return self::FAILURE;
            }

            if (! $this->filesystem->exists('vendor/phpstan/extension-installer')) {
                $output->writeln(
                    '<error>Type Perfect support requires phpstan/extension-installer for the Fast Forward integration path. '
                    . 'Install it with "composer require phpstan/extension-installer --dev" before using --type-perfect.</error>'
                );

                return self::FAILURE;
            }

            $output->writeln('<info>Running Type Perfect safety checks...</info>');

            $typePerfectConfig = $this->writeTypePerfectConfig($typePerfectGroups);
            $typePerfect = $this->processBuilder
                ->withArgument('analyse')
                ->withArgument('--configuration', $typePerfectConfig)
                ->build('vendor/bin/phpstan');

            $this->processQueue->add($typePerfect);
        }

        return $this->processQueue->run($output);
    }

    /**
     * Filters the requested Type Perfect groups down to the supported subset.
     *
     * @param string $groups the raw comma-separated option value
     *
     * @return list<string> the valid requested groups in declaration order
     */
    private function resolveTypePerfectGroups(string $groups): array
    {
        $requestedGroups = array_map('trim', explode(',', $groups));
        $requestedGroups = array_filter($requestedGroups, static fn(string $group): bool => '' !== $group);

        return array_values(array_intersect(self::TYPE_PERFECT_GROUPS, $requestedGroups));
    }

    /**
     * Writes the temporary PHPStan config used to run Type Perfect.
     *
     * @param list<string> $groups the enabled Type Perfect groups
     *
     * @return string the generated config path
     */
    private function writeTypePerfectConfig(array $groups): string
    {
        $configPath = (string) $this->filesystem->getAbsolutePath(self::TYPE_PERFECT_CONFIG);
        $this->filesystem->mkdir($this->filesystem->dirname($configPath));

        $lines = [];
        $projectPhpStanConfig = $this->resolveProjectPhpStanConfig();

        if (null !== $projectPhpStanConfig) {
            $lines[] = 'includes:';
            $lines[] = sprintf("    - '%s'", str_replace("'", "''", $projectPhpStanConfig));
            $lines[] = '';
        }

        $lines[] = 'parameters:';
        $lines[] = '    type_perfect:';

        foreach ($groups as $group) {
            $lines[] = sprintf('        %s: true', $group);
        }

        $this->filesystem->dumpFile($configPath, implode("\n", $lines) . "\n");

        return $configPath;
    }

    /**
     * Resolves the consumer PHPStan config to include in the generated Type Perfect file.
     *
     * @return string|null the absolute PHPStan config path, or null when the consumer has no PHPStan config yet
     */
    private function resolveProjectPhpStanConfig(): ?string
    {
        foreach (['phpstan.neon', 'phpstan.neon.dist'] as $candidate) {
            if ($this->filesystem->exists($candidate)) {
                return (string) $this->filesystem->getAbsolutePath($candidate);
            }
        }

        return null;
    }
}
