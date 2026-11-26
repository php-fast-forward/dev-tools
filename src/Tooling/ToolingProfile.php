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

namespace FastForward\DevTools\Tooling;

use FastForward\DevTools\Filesystem\Filesystem;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Path\WorkingProjectPathResolver;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use stdClass;
use Symfony\Component\Process\ExecutableFinder;

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * Owns the versioned project profile and reports existing tool availability.
 *
 * Reading and configuring profiles MUST NOT install dependencies.
 *
 * @copyright 2026 Fast Forward
 * @license https://opensource.org/licenses/MIT MIT License
 */
final readonly class ToolingProfile implements ToolingProfileInterface
{
    public const string FILE = 'dev-tools.json';

    public const array CAPABILITIES = ['tests', 'quality', 'docs', 'metrics', 'dependencies'];

    private const array BINARIES = [
        'tests' => ['phpunit'],
        'quality' => ['rector', 'php-cs-fixer', 'ecs'],
        'docs' => ['phpdoc'],
        'metrics' => ['phpmetrics'],
        'dependencies' => ['swiss-knife', 'composer-dependency-analyser'],
    ];

    /**
     * @param FilesystemInterface $filesystem project file operations
     * @param ExecutableFinder $executables resolves Composer on PATH without invoking it
     */
    public function __construct(
        private FilesystemInterface $filesystem = new Filesystem(),
        private ExecutableFinder $executables = new ExecutableFinder(),
    ) {}

    /**
     * @return list<string>|null the explicit selection, or null for legacy behavior
     */
    public function read(): ?array
    {
        $path = WorkingProjectPathResolver::getProjectPath(self::FILE);
        if (! $this->filesystem->exists($path)) {
            return null;
        }

        try {
            $profile = json_decode($this->filesystem->readFile($path), false, 512, \JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                'Invalid dev-tools.json: ' . $exception->getMessage(),
                previous: $exception
            );
        }

        if (! $profile instanceof stdClass
            || [] !== array_diff(array_keys(get_object_vars($profile)), ['version', 'capabilities'])
            || ($profile->version ?? null) !== 1
            || ! isset($profile->capabilities)
            || ! \is_array($profile->capabilities)
        ) {
            throw new InvalidArgumentException('dev-tools.json must contain only version: 1 and a capabilities array.');
        }

        return self::normalize($profile->capabilities);
    }

    /**
     * Validates and canonically orders the selected capabilities.
     *
     * @param array $capabilities candidate capability names
     *
     * @return list<string>
     */
    public static function normalize(array $capabilities): array
    {
        if (! array_is_list($capabilities)) {
            throw new InvalidArgumentException('Capabilities must be a list.');
        }
        $selected = [];
        foreach ($capabilities as $capability) {
            if (! \is_string($capability) || ! \in_array($capability, self::CAPABILITIES, true)) {
                throw new InvalidArgumentException('Unknown capability. Choose: ' . implode(
                    ', ',
                    self::CAPABILITIES
                ) . '.');
            }
            if (\in_array($capability, $selected, true)) {
                throw new InvalidArgumentException('Duplicate capability: ' . $capability . '.');
            }
            $selected[] = $capability;
        }

        return array_values(array_intersect(self::CAPABILITIES, $selected));
    }

    /**
     * Writes a validated selection only when it changes; malformed files are preserved.
     *
     * @param list<string> $capabilities selected capabilities
     * @param bool $dryRun whether to preview without writing
     */
    public function configure(array $capabilities, bool $dryRun = false): bool
    {
        $current = $this->read();
        $selected = self::normalize($capabilities);
        if ($current === $selected) {
            return false;
        }
        if (! $dryRun) {
            $this->filesystem->dumpFile(
                WorkingProjectPathResolver::getProjectPath(self::FILE),
                json_encode([
                    'version' => 1,
                    'capabilities' => $selected,
                ], \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR) . "\n",
            );
        }

        return true;
    }

    /**
     * @return array<string, array{selected: bool, available: bool, missing: list<string>, binaries: array<string, string>}>
     */
    public function status(): array
    {
        $selected = $this->read() ?? self::CAPABILITIES;
        $status = [];
        foreach (self::BINARIES as $capability => $names) {
            $binaries = [];
            $missing = [];
            foreach ($names as $name) {
                $path = DevToolsPathResolver::getPreferredToolBinaryPath($name);
                $binaries[$name] = $path;
                if (! is_file($path) || ! is_executable($path)) {
                    $missing[] = $name;
                }
            }
            if (\in_array($capability, ['quality', 'dependencies'], true)) {
                $composer = $this->executables->find('composer');
                $binaries['composer'] = $composer ?? '';
                if (null === $composer) {
                    $missing[] = 'composer';
                }
            }
            if ('quality' === $capability && ! $this->filesystem->exists(
                DevToolsPathResolver::getRuntimeVendorPath('ergebnis/composer-normalize/composer.json')
            )) {
                $missing[] = 'ergebnis/composer-normalize';
            }
            $status[$capability] = [
                'selected' => \in_array($capability, $selected, true),
                'available' => [] === $missing,
                'missing' => $missing,
                'binaries' => $binaries,
            ];
        }

        return $status;
    }

    /**
     * @param list<string> $capabilities capabilities required by the execution plan
     */
    public function assertAvailable(array $capabilities): void
    {
        $status = $this->status();
        foreach ($capabilities as $capability) {
            if (! $status[$capability]['available']) {
                throw new RuntimeException('Selected capability ' . $capability . ' is missing tools: ' . implode(
                    ', ',
                    $status[$capability]['missing']
                ) . '.');
            }
        }
    }
}
