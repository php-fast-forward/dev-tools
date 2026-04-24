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

use FastForward\DevTools\Console\Command\Traits\LogsCommandResults;
use Composer\Command\BaseCommand;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides functionality to execute automated code refactoring using Rector.
 * This class MUST NOT be extended and SHALL encapsulate the logic for Rector invocation.
 */
#[AsCommand(
    name: 'refactor',
    description: 'Runs Rector for code refactoring.',
    aliases: ['rector']
)]
final class RefactorCommand extends BaseCommand implements LoggerAwareCommandInterface
{
    use HasJsonOption;
    use LogsCommandResults;

    /**
     * @var string the default Rector configuration file
     */
    public const string CONFIG = 'rector.php';

    /**
     * Creates a new RefactorCommand instance.
     *
     * @param FileLocatorInterface $fileLocator the file locator
     * @param ProcessBuilderInterface $processBuilder the process builder
     * @param ProcessQueueInterface $processQueue the process queue
     * @param LoggerInterface $logger the output-aware logger
     */
    public function __construct(
        private readonly FileLocatorInterface $fileLocator,
        private readonly ProcessBuilderInterface $processBuilder,
        private readonly ProcessQueueInterface $processQueue,
        private readonly LoggerInterface $logger,
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
        $this->setHelp('This command runs Rector to refactor your code.');

        $this->addJsonOption()
            ->addOption(
                name: 'progress',
                mode: InputOption::VALUE_NONE,
                description: 'Whether to enable progress output from Rector.',
            )
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
        $jsonOutput = $this->isJsonOutput($input);
        $processOutput = $jsonOutput ? new BufferedOutput() : $output;
        $fix = (bool) $input->getOption('fix');
        $progress = ! $jsonOutput && (bool) $input->getOption('progress');

        $this->logger->info('Running Rector for code refactoring...', [
            'input' => $input,
        ]);

        $processBuilder = $this->processBuilder
            ->withArgument('process')
            ->withArgument('--config')
            ->withArgument($this->fileLocator->locate(self::CONFIG));

        if (! $progress) {
            $processBuilder = $processBuilder->withArgument('--no-progress-bar');
        }

        if ($jsonOutput) {
            $processBuilder = $processBuilder
                ->withArgument('--output-format', 'json');
        }

        if (! $fix) {
            $processBuilder = $processBuilder->withArgument('--dry-run');
        }

        $this->processQueue->add($processBuilder->build('vendor/bin/rector'));

        $result = $this->processQueue->run($processOutput);

        if (self::SUCCESS === $result) {
            return $this->success('Code refactoring checks completed successfully.', $input, [
                'output' => $processOutput,
            ]);
        }

        return $this->failure('Code refactoring checks failed.', $input, [
            'output' => $processOutput,
        ]);
    }
}
