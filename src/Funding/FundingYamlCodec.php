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

use Symfony\Component\Yaml\Yaml;

use function array_filter;
use function array_values;
use function count;
use function is_array;
use function is_string;
use function trim;

/**
 * Parses and renders GitHub funding YAML metadata.
 */
final readonly class FundingYamlCodec
{
    /**
     * Parses a GitHub funding YAML payload into a normalized profile.
     *
     * @param string|null $contents the YAML contents, or null when the file does not exist
     *
     * @return FundingProfile the normalized funding profile
     */
    public function parse(?string $contents): FundingProfile
    {
        if (null === $contents || '' === trim($contents)) {
            return new FundingProfile();
        }

        $data = Yaml::parse($contents);

        if (! is_array($data)) {
            return new FundingProfile();
        }

        $unsupported = array_filter(
            $data,
            static fn(string $key): bool => ! \in_array($key, ['github', 'custom'], true),
            ARRAY_FILTER_USE_KEY,
        );

        return new FundingProfile(
            $this->normalizeList($data['github'] ?? []),
            $this->normalizeList($data['custom'] ?? []),
            $unsupported,
        );
    }

    /**
     * Renders a normalized funding profile into GitHub funding YAML.
     *
     * @param FundingProfile $profile the profile to render
     *
     * @return string the YAML document contents
     */
    public function dump(FundingProfile $profile): string
    {
        $data = $profile->getUnsupportedYamlEntries();

        if ([] !== $profile->getGithubSponsors()) {
            $data['github'] = $this->denormalizeList($profile->getGithubSponsors());
        }

        if ([] !== $profile->getCustomUrls()) {
            $data['custom'] = $this->denormalizeList($profile->getCustomUrls());
        }

        return Yaml::dump($data, 4, 2);
    }

    /**
     * Normalizes a scalar-or-list YAML node into a string list.
     *
     * @param mixed $value the YAML node to normalize
     *
     * @return array<int, string> the normalized string list
     */
    private function normalizeList(mixed $value): array
    {
        if (is_string($value) && '' !== trim($value)) {
            return [trim($value)];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                static fn(mixed $entry): ?string => is_string($entry) && '' !== trim($entry) ? trim($entry) : null,
                $value,
            ),
        ));
    }

    /**
     * Converts a normalized list into the compact YAML representation.
     *
     * @param array<int, string> $values the normalized values
     *
     * @return string|array<int, string> the scalar-or-list YAML node
     */
    private function denormalizeList(array $values): string|array
    {
        return 1 === count($values) ? $values[0] : $values;
    }
}
