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

use FastForward\DevTools\Environment\EnvironmentInterface;
use FastForward\DevTools\Path\DevToolsPathResolver;
use Symfony\Component\Filesystem\Path;

/**
 * Detects Composer global DevTools installations from known Composer home paths.
 */
final readonly class ComposerSelfUpdateScopeResolver implements SelfUpdateScopeResolverInterface
{
    private const string PACKAGE_PATH = 'vendor/fast-forward/dev-tools';

    /**
     * @param EnvironmentInterface $environment reads Composer home environment values
     * @param string|null $packagePath the DevTools package path; defaults to the active package root
     */
    public function __construct(
        private EnvironmentInterface $environment,
        private ?string $packagePath = null,
    ) {}

    /**
     * Returns whether DevTools is running from Composer's global installation.
     */
    public function isGlobalInstallation(): bool
    {
        $packagePath = Path::canonicalize($this->packagePath ?? DevToolsPathResolver::getPackagePath());

        foreach ($this->getComposerHomeCandidates() as $composerHome) {
            $globalPackagePath = Path::canonicalize(Path::join($composerHome, self::PACKAGE_PATH));

            if ($packagePath === $globalPackagePath || str_starts_with(
                $packagePath,
                $globalPackagePath . \DIRECTORY_SEPARATOR
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns candidate Composer home directories for supported platforms.
     *
     * @return list<string>
     */
    private function getComposerHomeCandidates(): array
    {
        $candidates = [];
        $composerHome = $this->environment->get('COMPOSER_HOME');

        if (null !== $composerHome && '' !== $composerHome) {
            $candidates[] = $composerHome;
        }

        $home = $this->environment->get('HOME');

        if (null !== $home && '' !== $home) {
            $candidates[] = Path::join($home, '.composer');
            $candidates[] = Path::join($home, '.config/composer');
            $candidates[] = Path::join($home, 'Library/Application Support/Composer');
        }

        $appData = $this->environment->get('APPDATA');

        if (null !== $appData && '' !== $appData) {
            $candidates[] = Path::join($appData, 'Composer');
        }

        return array_values(array_unique($candidates));
    }
}
