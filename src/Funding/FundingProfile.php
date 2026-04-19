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

namespace FastForward\DevTools\Funding;

/**
 * Carries normalized funding metadata across Composer and GitHub formats.
 */
final readonly class FundingProfile
{
    /**
     * Creates a new funding profile.
     *
     * @param array<int, string> $githubSponsors the normalized GitHub Sponsors handles
     * @param array<int, string> $customUrls the normalized custom funding URLs
     * @param array<string, mixed> $unsupportedYamlEntries the YAML entries preserved without synchronization
     * @param array<int, array<string, mixed>> $unsupportedComposerEntries the Composer entries preserved without synchronization
     */
    public function __construct(
        private array $githubSponsors = [],
        private array $customUrls = [],
        private array $unsupportedYamlEntries = [],
        private array $unsupportedComposerEntries = [],
    ) {}

    /**
     * Returns the normalized GitHub Sponsors handles.
     *
     * @return array<int, string> the GitHub Sponsors handles
     */
    public function getGithubSponsors(): array
    {
        return $this->githubSponsors;
    }

    /**
     * Returns the normalized custom funding URLs.
     *
     * @return array<int, string> the custom funding URLs
     */
    public function getCustomUrls(): array
    {
        return $this->customUrls;
    }

    /**
     * Returns the unsupported YAML entries that MUST be preserved.
     *
     * @return array<string, mixed> the YAML entries kept without transformation
     */
    public function getUnsupportedYamlEntries(): array
    {
        return $this->unsupportedYamlEntries;
    }

    /**
     * Returns the unsupported Composer funding entries that MUST be preserved.
     *
     * @return array<int, array<string, mixed>> the Composer funding entries kept without transformation
     */
    public function getUnsupportedComposerEntries(): array
    {
        return $this->unsupportedComposerEntries;
    }

    /**
     * Reports whether the profile contains any YAML-serializable content.
     *
     * @return bool true when the YAML file SHOULD exist
     */
    public function hasYamlContent(): bool
    {
        return [] !== $this->githubSponsors
            || [] !== $this->customUrls
            || [] !== $this->unsupportedYamlEntries;
    }
}
