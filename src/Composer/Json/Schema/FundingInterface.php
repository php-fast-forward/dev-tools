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

namespace FastForward\DevTools\Composer\Json\Schema;

/**
 * Defines the contract for representing a single entry of the "funding" section
 * of a composer.json file.
 *
 * A funding entry describes a funding channel through which package users MAY
 * support the package authors in the maintenance of the project and the
 * development of new functionality. Each funding entry consists of a funding
 * type and a URL.
 *
 * Implementations of this interface MUST provide access to both the funding
 * platform type and the funding URL. The type SHOULD describe the platform or
 * mechanism through which funding can be provided, such as "patreon",
 * "opencollective", "tidelift", "github", or another recognized identifier.
 *
 * The URL MUST point to a resource where funding details are available and
 * through which financial support MAY be provided. Implementations SHOULD
 * return a fully qualified URL.
 *
 * Since the "funding" section is optional in composer.json, implementations MAY
 * be used in collections containing zero or more funding entries.
 *
 * The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT",
 * "SHOULD", "SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL" in this
 * interface are to be interpreted as described in RFC 2119.
 */
interface FundingInterface
{
    /**
     * Retrieves the funding type.
     *
     * This method MUST return the funding platform or funding mechanism
     * identifier associated with this entry.
     *
     * Implementations SHOULD return a normalized and meaningful value, such as
     * "patreon", "opencollective", "tidelift", "github", or "other".
     *
     * @return string the funding type identifier
     */
    public function getType(): string;

    /**
     * Retrieves the funding URL.
     *
     * This method MUST return the URL that provides funding details and a way
     * to financially support the package.
     *
     * Implementations SHOULD return a fully qualified URL and MUST preserve the
     * semantic meaning of the configured funding destination.
     *
     * @return string the funding URL
     */
    public function getUrl(): string;
}
