<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace FastForward\DevTools\Console\Command;

use Composer\InstalledVersions;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Filesystem\FinderFactoryInterface;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function Safe\getcwd;
use function Safe\json_decode;
use function Safe\preg_match;

/**
 * Installs optional LLM Skills integration through its official Composer commands.
 *
 * Existing configuration MUST remain owned by LLM Skills and the consumer.
 */
#[AsCommand(name: 'setup:skills', description: 'Opts into LLM Skills using its official CLI.', aliases: [
    'skills:setup',
])]
final class SkillsSetupCommand extends Command
{
    /**
     * @param ProcessBuilderInterface $processBuilder builds Composer commands
     * @param ProcessQueueInterface $processQueue applies centralized subprocess policy
     * @param FilesystemInterface $filesystem reads consumer configuration
     * @param FinderFactoryInterface $finderFactory discovers local skill payloads
     */
    public function __construct(
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly FilesystemInterface $filesystem,
        private readonly FinderFactoryInterface $finderFactory,
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Preview commands without installing or writing files.'
            )
            ->addOption('no-sync', null, InputOption::VALUE_NONE, 'Configure sources without the final skills:update.')
            ->addOption(
                'dev-tools-ref',
                null,
                InputOption::VALUE_REQUIRED,
                'Explicit GitHub ref for external DevTools skills.'
            );
    }

    /**
     * @param InputInterface $input the setup options
     * @param OutputInterface $output the output destination
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $commands = $this->plan($input);
        } catch (Throwable $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return self::FAILURE;
        }

        foreach ($commands as $arguments) {
            $process = $this->processBuilder->build($arguments);
            $process->setWorkingDirectory(getcwd());
            $output->writeln($process->getCommandLine(), OutputInterface::OUTPUT_RAW);

            if ($input->getOption('dry-run')) {
                continue;
            }

            $this->processQueue->add($process, label: 'Configuring optional LLM Skills');
            if (self::SUCCESS !== $this->processQueue->run($output)) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Plans every operation before permitting consumer writes.
     *
     * @param InputInterface $input the setup options
     *
     * @return list<list<string>>
     */
    private function plan(InputInterface $input): array
    {
        $contents = $this->filesystem->readFile('composer.json');
        $manifest = json_decode($contents, true);
        if (! \is_array($manifest) || ! str_starts_with(ltrim($contents), '{')) {
            throw new RuntimeException('The consumer composer.json MUST contain an object.');
        }

        $self = 'fast-forward/dev-tools' === ($manifest['name'] ?? null);
        $ref = $self ? null : $this->resolveDevToolsRef($input);
        $commands = [];
        $approval = $manifest['config']['allow-plugins'] ?? [];
        $allowed = null;

        if (\is_bool($approval)) {
            $allowed = $approval;
        } elseif (\is_array($approval)) {
            foreach ($approval as $pattern => $enabled) {
                if (fnmatch((string) $pattern, 'llm/skills')) {
                    $allowed = $enabled;
                    break;
                }
            }
        }

        if (false === $allowed) {
            throw new RuntimeException(
                'Existing Composer policy denies llm/skills; update that policy explicitly before setup.'
            );
        }

        if (true !== $allowed) {
            $commands[] = ['composer', '--no-plugins', '--no-scripts', 'config', 'allow-plugins.llm/skills', 'true'];
        }

        $vendorDirectory = $manifest['config']['vendor-dir'] ?? 'vendor';
        if (! \is_string($vendorDirectory) || '' === trim($vendorDirectory)) {
            throw new RuntimeException('The Composer vendor-dir MUST be a non-empty string.');
        }

        if (! isset($manifest['require-dev']['llm/skills']) && ! isset($manifest['require']['llm/skills'])) {
            $commands[] = [
                'composer',
                'require',
                '--dev',
                'llm/skills:^1.15',
                '--no-plugins',
                '--no-scripts',
                '--no-interaction',
            ];
        } elseif (! $this->filesystem->exists(rtrim($vendorDirectory, '/\\') . '/llm/skills/composer.json')) {
            $commands[] = ['composer', 'install', '--no-plugins', '--no-scripts', '--no-interaction'];
        }

        if (! $this->filesystem->exists('skills.json')) {
            $commands[] = [
                'composer',
                'skills:init',
                '--quick',
                '--no-sync',
                '--target=.agents/skills',
                '--trust=fast-forward/*',
                '--auto-sync',
                '--no-discovery',
                '--no-interaction',
            ];
        }

        if ($this->hasLocalSkills()) {
            $commands[] = [
                'composer',
                'skills:add',
                './resources/skills',
                '--from=dir',
                '--no-sync',
                '--no-interaction',
            ];
        }

        if (! $self) {
            $commands[] = [
                'composer',
                'skills:add',
                'php-fast-forward/dev-tools',
                '--ref=' . $ref,
                '--no-sync',
                '--no-interaction',
            ];
        }

        $commands[] = [
            'composer',
            'skills:add',
            'php-fast-forward/changelog',
            '--ref=^0.4.0',
            '--no-sync',
            '--no-interaction',
        ];
        if (! $input->getOption('no-sync')) {
            $commands[] = ['composer', 'skills:update', '--no-interaction'];
        }

        return $commands;
    }

    /**
     * Resolves an explicitly chosen ref or the executing installation's source commit.
     *
     * @param InputInterface $input the setup options
     *
     * @return string
     */
    private function resolveDevToolsRef(InputInterface $input): string
    {
        $ref = $input->getOption('dev-tools-ref');
        if (\is_string($ref) && '' !== trim($ref)) {
            return trim($ref);
        }

        if (null !== $ref) {
            throw new RuntimeException('--dev-tools-ref MUST be non-empty.');
        }

        if (InstalledVersions::isInstalled('fast-forward/dev-tools')) {
            $reference = InstalledVersions::getReference('fast-forward/dev-tools');
            if (\is_string($reference) && 1 === preg_match('/\A[0-9a-f]{40}\z/i', $reference)) {
                return $reference;
            }
        }

        throw new RuntimeException(
            'Cannot verify the executing DevTools source ref; provide --dev-tools-ref explicitly.'
        );
    }

    /**
     * @return bool
     */
    private function hasLocalSkills(): bool
    {
        if (! $this->filesystem->exists('resources/skills')) {
            return false;
        }

        return $this->finderFactory->create()
            ->files()
            ->ignoreDotFiles(false)
            ->name('SKILL.md')
            ->in('resources/skills')
            ->hasResults();
    }
}
