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
 * Concrete implementation of the ComposerJsonAuthorInterface.
 *
 * This class represents an author entry within a composer.json file and provides
 * structured access to author metadata such as name, email, homepage, and role.
 *
 * Implementations of this class MUST ensure that all provided data is consistent
 * and valid according to Composer expectations. Consumers of this class MAY rely
 * on its immutability if used in a readonly context.
 *
 * The string representation of this class SHALL return a human-readable
 * representation of the author.
 */
final readonly class Author implements AuthorInterface
{
    /**
     * Constructs a new ComposerJsonAuthor instance.
     *
     * All parameters MUST be provided as strings. Implementations SHOULD validate
     * the correctness of email and URL formats before assignment if strict validation is required.
     *
     * @param string $name the name of the author
     * @param string $email the email address of the author
     * @param string $homepage the homepage URL of the author
     * @param string $role the role of the author
     */
    public function __construct(
        private string $name = '',
        private string $email = '',
        private string $homepage = '',
        private string $role = ''
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * {@inheritDoc}
     */
    public function getHomepage(): string
    {
        return $this->homepage;
    }

    /**
     * {@inheritDoc}
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Returns a string representation of the author.
     *
     * This method SHALL return a formatted string combining the author's name and email.
     * Implementations MAY extend this format but SHOULD maintain readability.
     *
     * @return string the string representation of the author
     */
    public function __toString(): string
    {
        if (! $this->name && ! $this->email) {
            return '';
        }

        if ($this->name && ! $this->email) {
            return $this->name;
        }

        if (! $this->name && $this->email) {
            return $this->email;
        }

        return \sprintf('%s <%s>', $this->name, $this->email);
    }
}
