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

namespace FastForward\DevTools\Git;

use Symfony\Component\Process\Process;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Path;

use function rtrim;
use function str_starts_with;
use function trim;

/**
 * Executes semantic Git operations using the local Git binary.
 */
final readonly class GitClient implements GitClientInterface
{
    /**
     * @param ProcessBuilderInterface $processBuilder
     * @param ProcessQueueInterface $processQueue
     */
    public function __construct(
        private ProcessBuilderInterface $processBuilder,
        private ProcessQueueInterface $processQueue,
    ) {}

    /**
     * Returns a Git config value for the selected repository.
     *
     * @param string $key
     * @param ?string $workingDirectory
     */
    public function getConfig(string $key, ?string $workingDirectory = null): string
    {
        return $this->run(
            $this->processBuilder
                ->withArgument('config')
                ->withArgument('--get')
                ->withArgument($key)
                ->build('git'),
            $workingDirectory,
        );
    }

    /**
     * Returns the file contents shown from a specific Git reference.
     *
     * @param string $reference
     * @param string $path
     * @param ?string $workingDirectory
     */
    public function show(string $reference, string $path, ?string $workingDirectory = null): string
    {
        if (null !== $workingDirectory && Path::isAbsolute($path)) {
            $normalizedWorkingDirectory = rtrim(Path::canonicalize($workingDirectory), '/');
            $normalizedPath = Path::canonicalize($path);

            if (str_starts_with($normalizedPath, $normalizedWorkingDirectory . '/')) {
                $path = Path::makeRelative($normalizedPath, $normalizedWorkingDirectory);
            }
        }

        return $this->run(
            $this->processBuilder
                ->withArgument('show')
                ->withArgument($reference . ':' . $path)
                ->build('git'),
            $workingDirectory,
        );
    }

    /**
     * Executes a Git process and returns trimmed stdout.
     *
     * @param Process $process
     * @param ?string $workingDirectory
     */
    private function run(Process $process, ?string $workingDirectory = null): string
    {
        if (null !== $workingDirectory) {
            $process->setWorkingDirectory($workingDirectory);
        }

        $this->processQueue->add($process);

        if (ProcessQueueInterface::SUCCESS !== $this->processQueue->run()) {
            throw new RuntimeException(trim($process->getErrorOutput()));
        }

        return trim($process->getOutput());
    }
}
