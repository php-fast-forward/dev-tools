<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Command;

use Closure;
use DOMDocument;
use JsonException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Safe\json_decode;
use function array_values;
use function trim;

/**
 * Orchestrates dependency analysis across the supported Composer analyzers.
 * This command MUST report missing and unused dependencies using a single,
 * deterministic report that is friendly for local development and CI runs.
 */
final class DependenciesCommand extends AbstractCommand
{
    /**
     * @var string the root composer manifest expected by the dependency analysers
     */
    public const string COMPOSER_JSON = 'composer.json';

    /**
     * @var string the packaged path to shipmonk/composer-dependency-analyser
     */
    public const string DEPENDENCY_ANALYSER = 'vendor/bin/composer-dependency-analyser';

    /**
     * @var string the packaged path to icanhazstring/composer-unused
     */
    public const string COMPOSER_UNUSED = 'vendor/bin/composer-unused';

    /**
     * @var Closure(list<string>): array{exitCode:int, output:string}|null custom process runner used for testing
     */
    private readonly ?Closure $processRunner;

    /**
     * Constructs the dependencies command.
     *
     * The command MAY receive a custom runner for deterministic tests while the
     * default runtime MUST execute the real analyzers through Symfony Process.
     *
     * @param Filesystem|null $filesystem the filesystem utility used by the command
     * @param callable(list<string>): array{exitCode:int, output:string}|null $processRunner custom analyzer executor
     */
    public function __construct(?Filesystem $filesystem = null, ?callable $processRunner = null)
    {
        $this->processRunner = null === $processRunner ? null : Closure::fromCallable($processRunner);

        parent::__construct($filesystem);
    }

    /**
     * Configures the dependency analysis command metadata.
     *
     * The command MUST expose the `dependencies` name so it can run via both
     * Composer and the local `dev-tools` binary.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('dependencies')
            ->setDescription('Analyzes missing and unused Composer dependencies.')
            ->setHelp(
                'This command runs composer-dependency-analyser and composer-unused to report '
                . 'missing and unused Composer dependencies.'
            );
    }

    /**
     * Executes the dependency analysis workflow.
     *
     * The command MUST verify the required binaries before executing the tools,
     * SHOULD normalize their machine-readable output into a unified report, and
     * SHALL return a non-zero exit code when findings or execution failures exist.
     *
     * @param InputInterface $input the runtime command input
     * @param OutputInterface $output the console output stream
     *
     * @return int the command execution status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running dependency analysis...</info>');

        $composerJson = $this->getAbsolutePath(self::COMPOSER_JSON);
        $dependencyAnalyser = $this->getAbsolutePath(self::DEPENDENCY_ANALYSER);
        $composerUnused = $this->getAbsolutePath(self::COMPOSER_UNUSED);

        $missingRequirements = $this->resolveMissingRequirements(
            composerJson: $composerJson,
            dependencyAnalyser: $dependencyAnalyser,
            composerUnused: $composerUnused,
        );

        if ([] !== $missingRequirements) {
            $output->writeln('<error>Dependency analysis requires the following files:</error>');

            foreach ($missingRequirements as $requirement) {
                $output->writeln($requirement);
            }

            return self::FAILURE;
        }

        $missingDependencies = $this->analyzeMissingDependencies($composerJson, $dependencyAnalyser);
        $unusedDependencies = $this->analyzeUnusedDependencies($composerJson, $composerUnused);

        $output->writeln('');
        $output->writeln('Dependency Analysis Report');
        $output->writeln('');

        $hasExecutionFailure = $this->renderMissingDependenciesSection($output, $missingDependencies);

        $output->writeln('');

        if ($this->renderUnusedDependenciesSection($output, $unusedDependencies)) {
            $hasExecutionFailure = true;
        }

        $output->writeln('');
        $output->writeln('Summary:');

        if ($hasExecutionFailure) {
            $output->writeln('- dependency analysis could not be completed.');

            return self::FAILURE;
        }

        $output->writeln(\sprintf('- %d missing', \count($missingDependencies['dependencies'])));
        $output->writeln(\sprintf('- %d unused', \count($unusedDependencies['dependencies'])));

        if ([] !== $missingDependencies['dependencies'] || [] !== $unusedDependencies['dependencies']) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Resolves missing runtime requirements for the command.
     *
     * @param string $composerJson absolute path to composer.json
     * @param string $dependencyAnalyser absolute path to composer-dependency-analyser
     * @param string $composerUnused absolute path to composer-unused
     *
     * @return list<string> the user-facing requirement errors
     */
    private function resolveMissingRequirements(
        string $composerJson,
        string $dependencyAnalyser,
        string $composerUnused,
    ): array {
        $missing = [];

        if (! $this->filesystem->exists($composerJson)) {
            $missing[] = '- composer.json not found in the current working directory.';
        }

        if (! $this->filesystem->exists($dependencyAnalyser)) {
            $missing[] = '- vendor/bin/composer-dependency-analyser not found. Reinstall fast-forward/dev-tools dependencies.';
        }

        if (! $this->filesystem->exists($composerUnused)) {
            $missing[] = '- vendor/bin/composer-unused not found. Reinstall fast-forward/dev-tools dependencies.';
        }

        return $missing;
    }

    /**
     * Executes the missing dependency analyzer and normalizes its result.
     *
     * @param string $composerJson absolute path to composer.json
     * @param string $dependencyAnalyser absolute path to composer-dependency-analyser
     *
     * @return array{dependencies:list<string>, examples:array<string, string>, rawOutput:string, executionFailed:bool}
     */
    private function analyzeMissingDependencies(string $composerJson, string $dependencyAnalyser): array
    {
        $result = $this->runDependencyProcess([
            $dependencyAnalyser,
            '--composer-json=' . $composerJson,
            '--format=junit',
            '--ignore-unused-deps',
            '--ignore-dev-in-prod-deps',
            '--ignore-prod-only-in-dev-deps',
            '--ignore-unknown-classes',
            '--ignore-unknown-functions',
        ]);

        $normalized = $this->parseMissingDependencies($result['output']);

        if (null === $normalized) {
            return [
                'dependencies' => [],
                'examples' => [],
                'rawOutput' => $result['output'],
                'executionFailed' => true,
            ];
        }

        if (self::SUCCESS !== $result['exitCode'] && [] === $normalized['dependencies']) {
            return [
                'dependencies' => [],
                'examples' => [],
                'rawOutput' => $result['output'],
                'executionFailed' => true,
            ];
        }

        return [
            'dependencies' => $normalized['dependencies'],
            'examples' => $normalized['examples'],
            'rawOutput' => $result['output'],
            'executionFailed' => false,
        ];
    }

    /**
     * Executes composer-unused and normalizes its result.
     *
     * @param string $composerJson absolute path to composer.json
     * @param string $composerUnused absolute path to composer-unused
     *
     * @return array{dependencies:list<string>, rawOutput:string, executionFailed:bool}
     */
    private function analyzeUnusedDependencies(string $composerJson, string $composerUnused): array
    {
        $result = $this->runDependencyProcess([
            $composerUnused,
            $composerJson,
            '--output-format=json',
            '--no-progress',
        ]);

        $dependencies = $this->parseUnusedDependencies($result['output']);

        if (null === $dependencies) {
            return [
                'dependencies' => [],
                'rawOutput' => $result['output'],
                'executionFailed' => true,
            ];
        }

        if (self::SUCCESS !== $result['exitCode'] && [] === $dependencies) {
            return [
                'dependencies' => [],
                'rawOutput' => $result['output'],
                'executionFailed' => true,
            ];
        }

        return [
            'dependencies' => $dependencies,
            'rawOutput' => $result['output'],
            'executionFailed' => false,
        ];
    }

    /**
     * Renders the normalized missing dependency section.
     *
     * @param OutputInterface $output the console output stream
     * @param array{dependencies:list<string>, examples:array<string, string>, rawOutput:string, executionFailed:bool} $analysis
     *
     * @return bool true when the analyzer failed operationally
     */
    private function renderMissingDependenciesSection(OutputInterface $output, array $analysis): bool
    {
        $output->writeln('[Missing Dependencies]');

        if ($analysis['executionFailed']) {
            $output->writeln('<error>composer-dependency-analyser did not return a readable report.</error>');
            $this->renderRawOutput($output, $analysis['rawOutput']);

            return true;
        }

        if ([] === $analysis['dependencies']) {
            $output->writeln('None detected.');

            return false;
        }

        foreach ($analysis['dependencies'] as $dependency) {
            $line = '- ' . $dependency;

            if (\array_key_exists($dependency, $analysis['examples'])) {
                $line .= ' <comment>(' . $analysis['examples'][$dependency] . ')</comment>';
            }

            $output->writeln($line);
        }

        return false;
    }

    /**
     * Renders the normalized unused dependency section.
     *
     * @param OutputInterface $output the console output stream
     * @param array{dependencies:list<string>, rawOutput:string, executionFailed:bool} $analysis
     *
     * @return bool true when the analyzer failed operationally
     */
    private function renderUnusedDependenciesSection(OutputInterface $output, array $analysis): bool
    {
        $output->writeln('[Unused Dependencies]');

        if ($analysis['executionFailed']) {
            $output->writeln('<error>composer-unused did not return a readable report.</error>');
            $this->renderRawOutput($output, $analysis['rawOutput']);

            return true;
        }

        if ([] === $analysis['dependencies']) {
            $output->writeln('None detected.');

            return false;
        }

        foreach ($analysis['dependencies'] as $dependency) {
            $output->writeln('- ' . $dependency);
        }

        return false;
    }

    /**
     * Prints the raw analyzer output when normalization is not possible.
     *
     * @param OutputInterface $output the console output stream
     * @param string $rawOutput the raw analyzer output
     *
     * @return void
     */
    private function renderRawOutput(OutputInterface $output, string $rawOutput): void
    {
        $trimmedOutput = trim($rawOutput);

        if ('' === $trimmedOutput) {
            $output->writeln('<comment>No analyzer output was captured.</comment>');

            return;
        }

        $output->writeln($trimmedOutput);
    }

    /**
     * Parses the shadow dependency suite from the analyzer JUnit output.
     *
     * @param string $xml the raw JUnit XML payload
     *
     * @return array{dependencies:list<string>, examples:array<string, string>}|null
     */
    private function parseMissingDependencies(string $xml): ?array
    {
        $trimmedXml = trim($xml);

        if ('' === $trimmedXml || ! \extension_loaded('dom') || ! \extension_loaded('libxml')) {
            return null;
        }

        $internalErrors = libxml_use_internal_errors(true);

        $document = new DOMDocument();
        $loaded = $document->loadXML($trimmedXml);

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if (! $loaded) {
            return null;
        }

        $dependencies = [];
        $examples = [];

        foreach ($document->getElementsByTagName('testsuite') as $suite) {
            if ('shadow dependencies' !== $suite->getAttribute('name')) {
                continue;
            }

            foreach ($suite->getElementsByTagName('testcase') as $testCase) {
                $dependency = trim($testCase->getAttribute('name'));

                if ('' === $dependency) {
                    continue;
                }

                $dependencies[] = $dependency;

                foreach ($testCase->getElementsByTagName('failure') as $failure) {
                    $example = trim((string) $failure->nodeValue);

                    if ('' !== $example) {
                        $examples[$dependency] = $example;

                        break;
                    }
                }
            }
        }

        return [
            'dependencies' => array_values($dependencies),
            'examples' => $examples,
        ];
    }

    /**
     * Parses the composer-unused JSON output.
     *
     * @param string $json the raw JSON payload
     *
     * @return list<string>|null
     */
    private function parseUnusedDependencies(string $json): ?array
    {
        $trimmedJson = trim($json);

        if ('' === $trimmedJson) {
            return null;
        }

        try {
            $decoded = json_decode($trimmedJson, true);
        } catch (JsonException) {
            return null;
        }

        if (! \is_array($decoded) || ! \array_key_exists('unused-packages', $decoded) || ! \is_array(
            $decoded['unused-packages']
        )) {
            return null;
        }

        $dependencies = [];

        foreach ($decoded['unused-packages'] as $dependency) {
            if (\is_string($dependency) && '' !== $dependency) {
                $dependencies[] = $dependency;
            }
        }

        return array_values($dependencies);
    }

    /**
     * Executes a dependency analyzer and captures its output for normalization.
     *
     * @param list<string> $command the analyzer command to execute
     *
     * @return array{exitCode:int, output:string}
     */
    private function runDependencyProcess(array $command): array
    {
        if ($this->processRunner instanceof Closure) {
            return ($this->processRunner)($command);
        }

        $process = new Process($command, $this->getCurrentWorkingDirectory(), [
            'NO_COLOR' => '1',
        ]);
        $process->setTimeout(null);

        $buffer = '';

        $process->run(static function (string $type, string $output) use (&$buffer): void {
            $buffer .= $output;
        });

        return [
            'exitCode' => $process->getExitCode() ?? self::FAILURE,
            'output' => trim($buffer),
        ];
    }
}
