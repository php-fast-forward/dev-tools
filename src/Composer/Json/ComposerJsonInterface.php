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
 * @see     https://github.com/php-fast-forward/
 * @see     https://github.com/php-fast-forward/dev-tools
 * @see     https://github.com/php-fast-forward/dev-tools/issues
 * @see     https://php-fast-forward.github.io/dev-tools/
 * @see     https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Composer\Json;

use DateTimeImmutable;
use FastForward\DevTools\Composer\Json\Schema\AuthorInterface;
use FastForward\DevTools\Composer\Json\Schema\SupportInterface;

/**
 * Defines the contract for reading and exposing normalized metadata from a
 * Composer `composer.json` file.
 *
 * This interface provides convenient accessors for commonly used package
 * metadata, including identification, descriptive attributes, licensing,
 * authorship, support channels, funding declarations, autoload mappings,
 * configuration entries, scripts, and additional package-specific sections.
 *
 * Implementations of this interface MUST read data from a Composer-compatible
 * source and SHALL expose that data through stable, predictable, typed accessors.
 * When a given field is optional in Composer metadata and not declared by the
 * package, implementations SHOULD return an appropriate empty value, nullable
 * value, or default representation as described by each method contract.
 *
 * Returned values SHOULD preserve the semantic meaning of the underlying
 * `composer.json` document. Implementations MAY normalize values for
 * interoperability and developer convenience, provided that such normalization
 * does not materially alter the intended meaning of the original metadata.
 *
 * The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT",
 * "SHOULD", "SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL" in this
 * interface are to be interpreted as described in RFC 2119.
 */
interface ComposerJsonInterface
{
    /**
     * Returns the package name declared in the Composer file.
     *
     * The package name SHOULD follow the conventional Composer vendor/package
     * notation when defined. Implementations MAY return an empty string when
     * the name field is absent or cannot be resolved.
     *
     * @return string the package name, or an empty string when undefined
     */
    public function getName(): string;

    /**
     * Returns the package description declared in the Composer file.
     *
     * Implementations SHOULD return a human-readable summary of the package
     * purpose. When the description field is not defined, an empty string
     * SHOULD be returned.
     *
     * @return string the package description, or an empty string when undefined
     */
    public function getDescription(): string;

    /**
     * Returns the package version declared in the Composer file.
     *
     * Implementations SHOULD return the version string exactly as declared or
     * as normalized by the underlying Composer metadata source. When no version
     * is explicitly declared, implementations MAY return an empty string.
     *
     * @return string the package version, or an empty string when undefined
     */
    public function getVersion(): string;

    /**
     * Returns the package type declared in the Composer file.
     *
     * This value typically identifies how Composer or consuming tools SHOULD
     * interpret the package, such as `library`, `project`, or another valid
     * Composer package type. Implementations MAY return an empty string when
     * the type field is absent.
     *
     * @return string the package type, or an empty string when undefined
     */
    public function getType(): string;

    /**
     * Returns the package keywords declared in the Composer file.
     *
     * Keywords SHOULD be returned in declaration order whenever practical.
     * When the package does not define any keywords, implementations SHOULD
     * return an empty array.
     *
     * @return array<int, string> the package keywords, or an empty array when undefined
     */
    public function getKeywords(): array;

    /**
     * Returns the package homepage URL declared in the Composer file.
     *
     * Implementations SHOULD return a fully qualified URL when one is available.
     * When the homepage field is not defined, an empty string SHOULD be returned.
     *
     * @return string the homepage URL, or an empty string when undefined
     */
    public function getHomepage(): string;

    /**
     * Returns the readme path or readme reference declared in the Composer file.
     *
     * Implementations SHOULD return the value exactly as represented by the
     * underlying metadata source whenever possible. When the readme field is
     * absent, an empty string SHOULD be returned.
     *
     * @return string the readme value, or an empty string when undefined
     */
    public function getReadme(): string;

    /**
     * Returns the package time metadata as an immutable date-time instance.
     *
     * This value SHOULD represent the package release or metadata timestamp
     * associated with the Composer file. Implementations MUST return a
     * DateTimeImmutable instance.
     *
     * @return DateTimeImmutable|null the package time metadata as an immutable date-time value
     */
    public function getTime(): ?DateTimeImmutable;

    /**
     * Returns the package license when it can be resolved to a single value.
     *
     * If the underlying Composer metadata contains a single license identifier,
     * this method SHOULD return that identifier. If the metadata contains no
     * license or multiple license values that cannot be reduced to a single
     * unambiguous result, this method MUST return null.
     *
     * @return string|null the resolved license identifier, or null when no
     *                     single license value can be determined
     */
    public function getLicense(): ?string;

    /**
     * Returns the package authors declared in the Composer file.
     *
     * Implementations SHOULD preserve declaration order whenever practical.
     *
     * When `$onlyFirstAuthor` is set to `true`, this method MUST return the first
     * declared author. If no author is declared, the implementation SHOULD handle
     * that condition consistently with its contract and MUST NOT silently return an
     * invalid author representation.
     *
     * When `$onlyFirstAuthor` is set to `false`, this method MUST return all
     * declared authors as an iterable. If the Composer file does not declare any
     * authors, an empty iterable MUST be returned.
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
    public function getAuthors(bool $onlyFirstAuthor = false): AuthorInterface|iterable;

    /**
     * Returns the support metadata declared in the Composer file.
     *
     * Implementations MUST return an object implementing SupportInterface.
     * When the support section is absent, the returned object SHOULD represent
     * an empty support definition rather than causing failure.
     *
     * @return SupportInterface the support metadata object
     */
    public function getSupport(): SupportInterface;

    /**
     * Returns the funding entries declared in the Composer file.
     *
     * Each returned element SHOULD represent a single funding definition from
     * the optional Composer `funding` section. When no funding entries are
     * declared, implementations SHOULD return an empty array.
     *
     * @return array<int, mixed> the funding entries, or an empty array when undefined
     */
    public function getFunding(): array;

    /**
     * Returns the autoload configuration for the requested autoload type.
     *
     * The requested type typically refers to an autoload mapping section such as
     * `psr-4`, `psr-0`, `classmap`, or `files`. When no type is provided,
     * implementations SHOULD use the default Composer autoload type expected by
     * the implementation, which is commonly `psr-4`.
     *
     * If the requested autoload type does not exist, implementations SHOULD
     * return an empty array.
     *
     * @param string|null $type The autoload mapping type to retrieve. This
     *                          defaults to `psr-4` when null.
     *
     * @return array<string, mixed> the autoload configuration for the requested
     *                              type, or an empty array when unavailable
     */
    public function getAutoload(?string $type = null): array;

    /**
     * Returns the development autoload configuration for the requested type.
     *
     * The requested type typically refers to an autoload-dev mapping section
     * such as `psr-4`, `psr-0`, `classmap`, or `files`. When no type is
     * provided, implementations SHOULD use the default autoload-dev type
     * expected by the implementation, which is commonly `psr-4`.
     *
     * If the requested development autoload type does not exist,
     * implementations SHOULD return an empty array.
     *
     * @param string|null $type The development autoload mapping type to
     *                          retrieve. This defaults to `psr-4` when null.
     *
     * @return array<string, mixed> the autoload-dev configuration for the
     *                              requested type, or an empty array when unavailable
     */
    public function getAutoloadDev(?string $type = null): array;

    /**
     * Returns the minimum stability declared in the Composer file.
     *
     * Implementations SHOULD return the configured Composer minimum stability
     * value, such as `stable`, `RC`, `beta`, `alpha`, or `dev`. When the field
     * is not explicitly defined, implementations MAY return an empty string or
     * a normalized default value according to implementation policy.
     *
     * @return string the minimum stability value
     */
    public function getMinimumStability(): string;

    /**
     * Returns configuration data from the Composer `config` section.
     *
     * When a specific configuration key is provided, implementations SHOULD
     * resolve and return the matching configuration value whenever possible.
     * When the key is null, implementations MAY return the complete
     * configuration structure. The returned value MAY therefore be either an
     * array or a scalar string according to the accessed configuration entry.
     *
     * @param string|null $config the configuration key to retrieve, or null to
     *                            retrieve the complete config section
     *
     * @return array<string, mixed>|string the requested config value or the full
     *                                     config structure, depending on the
     *                                     requested key
     */
    public function getConfig(?string $config): array|string;

    /**
     * Returns the scripts declared in the Composer file.
     *
     * Implementations SHOULD return the Composer `scripts` section as a
     * structured array. When no scripts are declared, an empty array SHOULD
     * be returned.
     *
     * @return array<string, mixed> the Composer scripts configuration
     */
    public function getScripts(): array;

    /**
     * Returns the extra configuration section declared in the Composer file.
     *
     * When a specific extra key is provided, implementations SHOULD return the
     * matching extra configuration subset when available. When the key is null,
     * implementations SHOULD return the complete extra section. If the section
     * or requested key is not defined, an empty array SHOULD be returned.
     *
     * @param string|null $extra the extra configuration key to retrieve, or
     *                           null to retrieve the complete extra section
     *
     * @return array<string, mixed> the extra configuration data, or an empty
     *                              array when undefined
     */
    public function getExtra(?string $extra = null): array;

    /**
     * Returns the executable binary declarations from the Composer file.
     *
     * Composer `bin` entries MAY be declared as a single string or as a list of
     * strings. Implementations MUST therefore return either a string or an
     * array, preserving the semantic shape expected by the consumer.
     *
     * @return string|array<int, string> the declared binary path or paths
     */
    public function getBin(): string|array;

    /**
     * Returns the package suggestions declared in the Composer file.
     *
     * The returned array SHOULD represent the Composer `suggest` section, where
     * keys typically correspond to package names and values describe why the
     * suggested package may be useful. When no suggestions are declared,
     * implementations SHOULD return an empty array.
     *
     * @return array<string, string> the package suggestion map
     */
    public function getSuggest(): array;

    /**
     * Returns comment metadata associated with the Composer file.
     *
     * This method SHOULD expose any parsed or implementation-specific comment
     * data that is associated with the source Composer document. When no such
     * comments are available, implementations SHOULD return an empty array.
     *
     * @return array<int|string, mixed> the comment metadata, or an empty array when unavailable
     */
    public function getComments(): array;
}
