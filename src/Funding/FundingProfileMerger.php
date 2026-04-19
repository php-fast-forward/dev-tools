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

use function array_merge;
use function array_unique;
use function sort;

/**
 * Merges normalized funding profiles into a deterministic synchronized view.
 */
final readonly class FundingProfileMerger
{
    /**
     * Merges funding metadata from Composer and GitHub sources.
     *
     * @param FundingProfile $composerProfile the funding metadata sourced from composer.json
     * @param FundingProfile $yamlProfile the funding metadata sourced from .github/FUNDING.yml
     *
     * @return FundingProfile the merged synchronized funding metadata
     */
    public function merge(FundingProfile $composerProfile, FundingProfile $yamlProfile): FundingProfile
    {
        return new FundingProfile(
            $this->sortedUnique([...$composerProfile->getGithubSponsors(), ...$yamlProfile->getGithubSponsors()]),
            $this->sortedUnique([...$composerProfile->getCustomUrls(), ...$yamlProfile->getCustomUrls()]),
            $yamlProfile->getUnsupportedYamlEntries(),
            $composerProfile->getUnsupportedComposerEntries(),
        );
    }

    /**
     * Returns a sorted unique list of scalar values.
     *
     * @param array<int, string> $values the values to normalize
     *
     * @return array<int, string> the sorted unique values
     */
    private function sortedUnique(array $values): array
    {
        $values = array_values(array_unique($values));
        sort($values);

        return $values;
    }
}
