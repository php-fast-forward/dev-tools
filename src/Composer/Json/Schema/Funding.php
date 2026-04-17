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

namespace FastForward\DevTools\Composer\Json\Schema;

/**
 * Represents a single funding entry from the optional "funding" section of a
 * composer.json file.
 *
 * A funding entry identifies a funding platform or mechanism and the URL through
 * which users MAY financially support package maintenance and future development.
 *
 * This class is an immutable value object. All promoted properties are assigned
 * at construction time and MUST NOT be modified afterward.
 *
 * The type property SHOULD contain a meaningful funding platform identifier such
 * as "patreon", "opencollective", "tidelift", "github", or "other".
 *
 * The url property MUST contain the funding destination URL and SHOULD be a
 * fully qualified URL.
 */
final readonly class Funding implements FundingInterface
{
    /**
     * Constructs a new funding entry.
     *
     * The provided values SHALL be stored exactly as received.
     *
     * @param string $type the funding platform or mechanism identifier
     * @param string $url the URL that provides funding details and support options
     */
    public function __construct(
        private string $type,
        private string $url,
    ) {}

    /**
     * Retrieves the funding type.
     *
     * This method MUST return the funding platform or mechanism identifier
     * associated with this entry.
     *
     * @return string the funding type identifier
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Retrieves the funding URL.
     *
     * This method MUST return the URL that provides funding details and a way
     * to financially support the package.
     *
     * @return string the funding URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
