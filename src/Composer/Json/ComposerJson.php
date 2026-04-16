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

namespace FastForward\DevTools\Composer\Json;

use RuntimeException;
use UnexpectedValueException;
use Composer\Factory;
use Composer\InstalledVersions;
use Composer\Json\JsonFile;
use DateTimeImmutable;
use FastForward\DevTools\Composer\Json\Schema\Author;
use FastForward\DevTools\Composer\Json\Schema\AuthorInterface;
use FastForward\DevTools\Composer\Json\Schema\Funding;
use FastForward\DevTools\Composer\Json\Schema\Support;
use FastForward\DevTools\Composer\Json\Schema\SupportInterface;
use UnderflowException;

use function Safe\realpath;

/**
 * Represents a specialized reader for a Composer JSON file.
 *
 * This class SHALL provide convenient accessors for commonly used
 * `composer.json` metadata after reading and caching the file contents.
 * Consumers SHOULD use this class when they need normalized access to
 * package-level metadata. The internal data cache MUST reflect the
 * contents returned by the underlying JSON file reader at construction
 * time.
 */
final class ComposerJson implements ComposerJsonInterface
{
    /**
     * Stores the decoded Composer JSON document contents.
     *
     * This property MUST contain the data read from the target Composer
     * file during construction. Consumers SHOULD treat the structure as
     * internal implementation detail and SHALL rely on accessor methods
     * instead of direct access.
     *
     * @var array<string, mixed>
     */
    private array $data;

    private array $installed;

    /**
     * Initializes the Composer JSON reader.
     *
     * When no path is provided, the default Composer file location
     * returned by Composer's factory SHALL be used. The constructor MUST
     * immediately read and cache the JSON document contents so that
     * subsequent accessor methods can operate on the in-memory data.
     *
     * @param string|null $path The absolute or relative path to a
     *                          Composer JSON file. When omitted, the
     *                          default Composer file path SHALL be used.
     *
     * @throws RuntimeException when $path is'nt provided and COMPOSER environment variable is set to a directory
     * @throws UnexpectedValueException when composer.json can't be parsed
     */
    public function __construct(?string $path = null)
    {
        $pathLocal = realpath(Factory::getComposerFile());

        $path ??= $pathLocal;
        $installedJsonPath = \dirname($pathLocal) . '/vendor/composer/installed.json';

        $this->data = (new JsonFile($path))->read();
        $this->installed = (new JsonFile($installedJsonPath))->read();
    }

    /**
     * Returns the package name declared in the Composer file.
     *
     * This method SHALL return the value of the `name` key when present.
     * If the package name is not defined, the method MUST return an
     * empty string.
     *
     * @return string the package name, or an empty string when undefined
     */
    public function getName(): string
    {
        return $this->data['name'];
    }

    /**
     * Returns the package description declared in the Composer file.
     *
     * This method SHALL return the value of the `description` key when
     * present. If the description is not defined, the method MUST return
     * an empty string.
     *
     * @return string the package description, or an empty string when undefined
     */
    public function getDescription(): string
    {
        return $this->data['description'];
    }

    /**
     * Returns the package version.
     *
     * This method SHOULD return the installed package version when it can be
     * resolved through Composer's installed versions metadata. When that value
     * cannot be resolved, the method SHALL fall back to the `version` value
     * declared in the Composer file. If neither source provides a usable value,
     * the method MUST return an empty string.
     *
     * @return string the package version, or an empty string when undefined
     */
    public function getVersion(): string
    {
        return $this->data['version'] ?? InstalledVersions::getVersion($this->getName());
    }

    /**
     * Returns the package type declared in the Composer file.
     *
     * This method SHALL return the value of the `type` key when present.
     * If the package type is not defined, the method MUST return an empty
     * string.
     *
     * @return string the package type, or an empty string when undefined
     */
    public function getType(): string
    {
        return $this->data['type'] ?? 'library';
    }

    /**
     * Returns the package keywords declared in the Composer file.
     *
     * This method SHALL return the `keywords` values in declaration order
     * whenever available. Non-string values MUST be ignored. If the section
     * is absent, the method MUST return an empty array.
     *
     * @return array<int, string> the package keywords, or an empty array when undefined
     */
    public function getKeywords(): array
    {
        return $this->data['keywords'] ?? [];
    }

    /**
     * Returns the package homepage URL declared in the Composer file.
     *
     * This method SHALL return the value of the `homepage` key when present.
     * If the homepage is not defined, the method MUST return an empty string.
     *
     * @return string the homepage URL, or an empty string when undefined
     */
    public function getHomepage(): string
    {
        return $this->data['homepage'] ?? '';
    }

    /**
     * Returns the readme path or reference declared in the Composer file.
     *
     * This method SHALL return the value of the `readme` key when present.
     * If the readme value is not defined, the method MUST return an empty
     * string.
     *
     * @return string the readme value, or an empty string when undefined
     */
    public function getReadme(): string
    {
        return $this->data['readme'] ?? '';
    }

    /**
     * Returns the package time metadata as an immutable date-time instance.
     *
     * This method SHALL attempt to create a DateTimeImmutable instance from the
     * `time` field. When the field is not present or is not a valid date-time
     * string, the current immutable date-time SHALL be returned.
     *
     * @return DateTimeImmutable|null the package time metadata as an immutable date-time value
     */
    public function getTime(): ?DateTimeImmutable
    {
        $packages = $this->installed['packages'];

        if (isset($packages[$this->getName()])) {
            return new DateTimeImmutable($packages[$this->getName()]['time']);
        }

        if (isset($this->data['time'])) {
            return new DateTimeImmutable($this->data['time']);
        }

        return null;
    }

    /**
     * Returns the package license when it can be resolved to a single value.
     *
     * This method SHALL return the `license` value directly when it is a
     * string. When the license is an array containing exactly one item,
     * that single item SHALL be returned. When the license field is not
     * present, is empty, or cannot be resolved to exactly one string
     * value, the method MUST return null.
     *
     * @return string|null the resolved license identifier, or null when no
     *                     single license value can be determined
     */
    public function getLicense(): ?string
    {
        $license = $this->data['license'] ?? [];

        if (\is_string($license)) {
            return $license;
        }

        if (\is_array($license) && 1 === \count($license) && \is_string($license[0] ?? null)) {
            return $license[0];
        }

        return null;
    }

    /**
     * Returns the package authors declared in the Composer file.
     *
     * This method SHALL normalize each author entry to an AuthorInterface
     * implementation. When `$onlyFirstAuthor` is `true`, the first normalized
     * author MUST be returned. If no author is declared, an UnderflowException
     * SHALL be thrown. When `$onlyFirstAuthor` is `false`, all normalized
     * authors MUST be returned as an iterable.
     *
     * @param bool $onlyFirstAuthor determines whether only the first declared
     *                              author SHALL be returned instead of the full
     *                              author list
     *
     * @return AuthorInterface|iterable<int, AuthorInterface> the first declared
     *                                                        author when
     *                                                        `$onlyFirstAuthor`
     *                                                        is `true`, or the full
     *                                                        authors list when
     *                                                        `$onlyFirstAuthor`
     *                                                        is `false`
     */
    public function getAuthors(bool $onlyFirstAuthor = false): AuthorInterface|iterable
    {
        $authors = array_map(static fn(array $author): Author => new Author(
            $author['name'] ?? '',
            $author['email'] ?? '',
            $author['homepage'] ?? '',
            $author['role'] ?? '',
        ), $this->data['authors'] ?? []);

        if ($onlyFirstAuthor) {
            if ([] === $authors) {
                throw new UnderflowException('No author entries were declared in the Composer file.');
            }

            return $authors[0];
        }

        return $authors;
    }

    /**
     * Returns the support metadata declared in the Composer file.
     *
     * This method SHALL return a SupportInterface implementation built from
     * the `support` section. When the section is absent, an empty support
     * object MUST be returned.
     *
     * @return SupportInterface the support metadata object
     */
    public function getSupport(): SupportInterface
    {
        $support = $this->data['support'] ?? [];

        if (! \is_array($support)) {
            $support = [];
        }

        return new Support(
            $support['email'] ?? '',
            $support['issues'] ?? '',
            $support['forum'] ?? '',
            $support['wiki'] ?? '',
            $support['irc'] ?? '',
            $support['source'] ?? '',
            $support['docs'] ?? '',
            $support['rss'] ?? '',
            $support['chat'] ?? '',
            $support['security'] ?? '',
        );
    }

    /**
     * Returns the funding entries declared in the Composer file.
     *
     * This method SHALL normalize each funding entry into a Funding value
     * object. Invalid or non-array entries MUST be ignored. If the section
     * is absent, the method MUST return an empty array.
     *
     * @return array<int, Funding> the funding entries, or an empty array when undefined
     */
    public function getFunding(): array
    {
        $funding = $this->data['funding'] ?? [];

        if (! \is_array($funding)) {
            return [];
        }

        $entries = [];

        foreach ($funding as $entry) {
            if (! \is_array($entry)) {
                continue;
            }

            $entries[] = new Funding($entry['type'] ?? '', $entry['url'] ?? '');
        }

        return $entries;
    }

    /**
     * Returns the autoload configuration for the requested autoload type.
     *
     * This method SHALL inspect the `autoload` section and return the
     * nested configuration for the requested type, such as `psr-4`.
     * When the `autoload` section or the requested type is not defined,
     * the method MUST return an empty array.
     *
     * @param string|null $type The autoload mapping type to retrieve. This
     *                          defaults to the complete section when null.
     *
     * @return array<string, mixed> the autoload configuration for the requested
     *                              type, or an empty array when unavailable
     */
    public function getAutoload(?string $type = null): array
    {
        $autoload = $this->data['autoload'] ?? [];

        if (! \is_array($autoload)) {
            return [];
        }

        if (null === $type) {
            return $autoload;
        }

        $mapping = $autoload[$type] ?? [];

        return \is_array($mapping) ? $mapping : [];
    }

    /**
     * Returns the development autoload configuration for the requested type.
     *
     * This method SHALL inspect the `autoload-dev` section and return the
     * nested configuration for the requested type. When the section or the
     * requested type is not defined, the method MUST return an empty array.
     *
     * @param string|null $type The development autoload mapping type to
     *                          retrieve. This defaults to the complete section
     *                          when null.
     *
     * @return array<string, mixed> the autoload-dev configuration for the
     *                              requested type, or an empty array when unavailable
     */
    public function getAutoloadDev(?string $type = null): array
    {
        $autoloadDev = $this->data['autoload-dev'] ?? [];

        if (! \is_array($autoloadDev)) {
            return [];
        }

        if (null === $type) {
            return $autoloadDev;
        }

        $mapping = $autoloadDev[$type] ?? [];

        return \is_array($mapping) ? $mapping : [];
    }

    /**
     * Returns the minimum stability declared in the Composer file.
     *
     * This method SHALL return the value of the `minimum-stability` key when
     * present. If the key is absent, the method MUST return an empty string.
     *
     * @return string the minimum stability value
     */
    public function getMinimumStability(): string
    {
        return $this->data['minimum-stability'] ?? 'stable';
    }

    /**
     * Returns configuration data from the Composer `config` section.
     *
     * This method SHALL return the complete `config` section when `$config`
     * is null. When a specific key is requested, the method SHALL return the
     * matching value if it is an array or a string. Any non-array scalar
     * value MUST be cast to string. If the section or key is absent, an
     * empty array SHALL be returned when `$config` is null, otherwise an
     * empty string SHALL be returned.
     *
     * @param string|null $config the configuration key to retrieve, or null
     *                            to retrieve the complete config section
     *
     * @return array<string, mixed>|string the requested config value or the full
     *                                     config structure, depending on the
     *                                     requested key
     */
    public function getConfig(?string $config): array|string
    {
        $configuration = $this->data['config'] ?? [];

        if (! \is_array($configuration)) {
            return null === $config ? [] : '';
        }

        if (null === $config) {
            return $configuration;
        }

        $value = $configuration[$config] ?? '';

        if (\is_array($value)) {
            return $value;
        }

        return \is_string($value) ? $value : (string) $value;
    }

    /**
     * Returns the scripts declared in the Composer file.
     *
     * This method SHALL return the `scripts` section when present. If the
     * section is absent or invalid, the method MUST return an empty array.
     *
     * @return array<string, mixed> the Composer scripts configuration
     */
    public function getScripts(): array
    {
        $scripts = $this->data['scripts'] ?? [];

        return \is_array($scripts) ? $scripts : [];
    }

    /**
     * Returns the extra configuration section declared in the Composer file.
     *
     * This method SHALL return the complete `extra` section when `$extra` is
     * null. When a specific extra key is requested, the method SHALL return
     * the matching value only when that value is an array. If the section or
     * requested key is absent, the method MUST return an empty array.
     *
     * @param string|null $extra the extra configuration key to retrieve, or
     *                           null to retrieve the complete extra section
     *
     * @return array<string, mixed> the extra configuration data, or an empty
     *                              array when undefined
     */
    public function getExtra(?string $extra = null): array
    {
        $extraConfiguration = $this->data['extra'] ?? [];

        if (! \is_array($extraConfiguration)) {
            return [];
        }

        if (null === $extra) {
            return $extraConfiguration;
        }

        $value = $extraConfiguration[$extra] ?? [];

        return \is_array($value) ? $value : [];
    }

    /**
     * Returns the executable binary declarations from the Composer file.
     *
     * This method SHALL return the `bin` value as declared when it is a
     * string or an array. If the section is absent or invalid, the method
     * MUST return an empty array.
     *
     * @return string|array<int, string> the declared binary path or paths
     */
    public function getBin(): string|array
    {
        $bin = $this->data['bin'] ?? [];

        if (\is_string($bin)) {
            return $bin;
        }

        if (! \is_array($bin)) {
            return [];
        }

        return array_values(array_filter($bin, \is_string(...)));
    }

    /**
     * Returns the package suggestions declared in the Composer file.
     *
     * This method SHALL return the `suggest` section as a string map.
     * Non-string keys or values MUST be ignored. If the section is absent,
     * the method MUST return an empty array.
     *
     * @return array<string, string> the package suggestion map
     */
    public function getSuggest(): array
    {
        $suggest = $this->data['suggest'] ?? [];

        if (! \is_array($suggest)) {
            return [];
        }

        $result = [];

        foreach ($suggest as $package => $description) {
            if (! \is_string($package)) {
                continue;
            }

            if (! \is_string($description)) {
                continue;
            }

            $result[$package] = $description;
        }

        return $result;
    }

    /**
     * Returns comment metadata associated with the Composer file.
     *
     * Since standard Composer JSON does not define a comments section, this
     * method SHALL return the `_comment` key when present and valid. When
     * comment metadata is unavailable, the method MUST return an empty array.
     *
     * @return array<int|string, mixed> the comment metadata, or an empty array when unavailable
     */
    public function getComments(): array
    {
        $comments = $this->data['_comment'] ?? [];

        if (\is_string($comments)) {
            return [$comments];
        }

        return \is_array($comments) ? $comments : [];
    }
}
