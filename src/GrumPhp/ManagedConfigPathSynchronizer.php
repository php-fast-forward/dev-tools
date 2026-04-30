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

namespace FastForward\DevTools\GrumPhp;

use Symfony\Component\Filesystem\Path;

/**
 * Synchronizes deprecated DevTools-managed GrumPHP composer metadata without leaking package paths into consumers.
 */
final class ManagedConfigPathSynchronizer
{
    /**
     * @var string relative packaged config suffix managed by DevTools installs
     */
    private const string MANAGED_CONFIG_SUFFIX = 'vendor/fast-forward/dev-tools/grumphp.yml';

    /**
     * Removes deprecated DevTools-managed GrumPHP config-default-path entries while preserving consumer-owned values.
     *
     * @param array<string, mixed> $extra the composer.json extra payload
     * @param string $workingDirectory the consumer project directory
     * @param string $managedConfigPath the active packaged GrumPHP config path
     *
     * @return array<string, mixed> the synchronized composer extra payload
     */
    public function synchronize(array $extra, string $workingDirectory, string $managedConfigPath): array
    {
        if (isset($extra['grumphp']) && ! \is_array($extra['grumphp'])) {
            return $extra;
        }

        $grumphpExtra = $extra['grumphp'] ?? [];
        $configDefaultPath = $grumphpExtra['config-default-path'] ?? null;

        if (\is_string($configDefaultPath) && $this->isManagedConfigPath(
            $configDefaultPath,
            $workingDirectory,
            $managedConfigPath
        )) {
            unset($grumphpExtra['config-default-path']);
        }

        if ([] === $grumphpExtra) {
            unset($extra['grumphp']);

            return $extra;
        }

        $extra['grumphp'] = $grumphpExtra;

        return $extra;
    }

    /**
     * Reports whether a config-default-path value is managed by DevTools.
     *
     * @param string $configDefaultPath the stored composer extra path value
     * @param string $workingDirectory the consumer project directory
     * @param string $managedConfigPath the active packaged GrumPHP config path
     */
    public function isManagedConfigPath(
        string $configDefaultPath,
        string $workingDirectory,
        string $managedConfigPath,
    ): bool {
        $normalizedConfigPath = $this->normalize($configDefaultPath);
        $normalizedManagedRelativePath = $this->normalize(Path::makeRelative($managedConfigPath, $workingDirectory));

        return $normalizedConfigPath === $normalizedManagedRelativePath
            || str_ends_with($normalizedConfigPath, self::MANAGED_CONFIG_SUFFIX);
    }

    /**
     * Normalizes a path for stable comparisons across platforms.
     *
     * @param string $path the path to normalize
     */
    private function normalize(string $path): string
    {
        return trim(str_replace('\\', '/', Path::canonicalize($path)), '/');
    }
}
