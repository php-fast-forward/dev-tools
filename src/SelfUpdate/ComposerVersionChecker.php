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

namespace FastForward\DevTools\SelfUpdate;

use Composer\InstalledVersions;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use JsonException;
use Symfony\Component\Process\Process;

use function Safe\preg_match;
use function Safe\json_decode;

/**
 * Resolves DevTools freshness through Composer metadata without coupling callers to Composer commands.
 */
final readonly class ComposerVersionChecker implements VersionCheckerInterface
{
    private const string PACKAGE = 'fast-forward/dev-tools';

    private const int TIMEOUT_SECONDS = 5;

    /**
     * @param ProcessBuilderInterface $processBuilder the process builder used to query Composer metadata
     */
    public function __construct(
        private ProcessBuilderInterface $processBuilder,
    ) {}

    /**
     * Returns version information when it can be resolved without blocking command execution.
     */
    public function check(): ?VersionCheckResult
    {
        if (DevToolsPathResolver::isRepositoryCheckout()) {
            return null;
        }

        $currentVersion = InstalledVersions::getPrettyVersion(self::PACKAGE)
            ?? InstalledVersions::getVersion(self::PACKAGE);

        if (null === $currentVersion) {
            return null;
        }

        $latestVersion = $this->resolveLatestStableVersion();

        if (null === $latestVersion) {
            return null;
        }

        return new VersionCheckResult($currentVersion, $latestVersion);
    }

    /**
     * Resolves the latest stable DevTools version available to Composer.
     */
    private function resolveLatestStableVersion(): ?string
    {
        $process = $this->processBuilder
            ->withArgument(self::PACKAGE)
            ->withArgument('--available')
            ->withArgument('--format=json')
            ->withArgument('--no-interaction')
            ->build('composer show');

        $process->setTimeout(self::TIMEOUT_SECONDS);

        if (Process::SUCCESS !== $process->run()) {
            return null;
        }

        try {
            $payload = json_decode($process->getOutput(), true);
        } catch (JsonException) {
            return null;
        }

        if (! \is_array($payload)) {
            return null;
        }

        $versions = $payload['versions'] ?? null;

        if (! \is_array($versions)) {
            return null;
        }

        foreach ($versions as $version) {
            if (! \is_string($version)) {
                continue;
            }

            if (1 === preg_match('/^v?\d+\.\d+\.\d+$/', $version)) {
                return $version;
            }
        }

        return null;
    }
}
