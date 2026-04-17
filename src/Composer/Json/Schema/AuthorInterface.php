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

use Stringable;

/**
 * Defines the contract for representing an author entry within a composer.json file.
 *
 * Implementations of this interface MUST provide consistent and valid author metadata,
 * including name, email, homepage, and role. These values SHALL be used for serialization
 * and interoperability with Composer specifications.
 *
 * Implementing classes MUST also implement the Stringable interface, meaning they SHALL
 * provide a __toString() method that returns a string representation of the author.
 *
 * The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT",
 * "SHOULD", "SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL" in this
 * interface are to be interpreted as described in RFC 2119.  [oai_citation:0‡rfc2119.txt](file-service://file-6PyYAHaGB569Cn3X4DVdVh)
 */
interface AuthorInterface extends Stringable
{
    /**
     * Retrieves the name of the author.
     *
     * This method MUST return a non-empty string representing the author's name.
     * Implementations SHOULD ensure that the name is human-readable and properly formatted.
     *
     * @return string the full name of the author
     */
    public function getName(): string;

    /**
     * Retrieves the email address of the author.
     *
     * This method MUST return a valid email address string.
     * Implementations SHOULD validate the format according to RFC standards where applicable.
     *
     * @return string the email address of the author
     */
    public function getEmail(): string;

    /**
     * Retrieves the homepage URL of the author.
     *
     * This method MUST return a valid URL string.
     * Implementations MAY return an empty string if no homepage is defined,
     * but SHOULD prefer a fully qualified URL when available.
     *
     * @return string the homepage URL of the author
     */
    public function getHomepage(): string;

    /**
     * Retrieves the role of the author.
     *
     * This method MUST describe the role of the author in the project (e.g., "Developer", "Maintainer").
     * Implementations SHOULD use consistent and meaningful role definitions.
     *
     * @return string the role of the author
     */
    public function getRole(): string;
}
