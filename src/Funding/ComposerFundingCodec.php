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

use Composer\Json\JsonFile;

use function Safe\json_encode;
use function Safe\parse_url;
use function Safe\preg_match;
use function array_values;
use function trim;

/**
 * Parses and renders Composer funding metadata.
 */
final readonly class ComposerFundingCodec
{
    /**
     * Parses Composer funding entries into a normalized funding profile.
     *
     * @param string $contents the composer.json contents
     *
     * @return FundingProfile the normalized funding profile
     */
    public function parse(string $contents): FundingProfile
    {
        $data = JsonFile::parseJson($contents);
        $funding = $data['funding'] ?? [];

        if (! \is_array($funding)) {
            return new FundingProfile();
        }

        $githubSponsors = [];
        $customUrls = [];
        $unsupported = [];

        foreach ($funding as $entry) {
            if (! \is_array($entry)) {
                continue;
            }

            $type = \is_string($entry['type'] ?? null) ? trim($entry['type']) : '';
            $url = \is_string($entry['url'] ?? null) ? trim($entry['url']) : '';

            if ('' === $url) {
                $unsupported[] = $entry;

                continue;
            }

            $githubSponsor = $this->extractGithubSponsor($url);

            if ('github' === $type && null !== $githubSponsor) {
                $githubSponsors[] = $githubSponsor;

                continue;
            }

            if ('custom' === $type) {
                $customUrls[] = $url;

                continue;
            }

            $unsupported[] = $entry;
        }

        return new FundingProfile(
            array_values(array_unique($githubSponsors)),
            array_values(array_unique($customUrls)),
            unsupportedComposerEntries: $unsupported,
        );
    }

    /**
     * Applies a normalized funding profile to composer.json contents.
     *
     * @param string $contents the composer.json contents
     * @param FundingProfile $profile the merged funding profile
     *
     * @return string the updated composer.json contents
     */
    public function dump(string $contents, FundingProfile $profile): string
    {
        $entries = [];

        foreach ($profile->getGithubSponsors() as $githubSponsor) {
            $entries[] = [
                'type' => 'github',
                'url' => \sprintf('https://github.com/sponsors/%s', $githubSponsor),
            ];
        }

        foreach ($profile->getCustomUrls() as $customUrl) {
            $entries[] = [
                'type' => 'custom',
                'url' => $customUrl,
            ];
        }

        foreach ($profile->getUnsupportedComposerEntries() as $unsupportedEntry) {
            $entries[] = $unsupportedEntry;
        }

        $data = JsonFile::parseJson($contents);
        unset($data['funding']);

        if ([] === $entries) {
            return json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE) . "\n";
        }

        return json_encode(
            $this->insertFundingEntries($data, $entries),
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
        ) . "\n";
    }

    /**
     * Extracts a GitHub Sponsors handle from a funding URL.
     *
     * @param string $url the funding URL
     *
     * @return string|null the sponsor handle, or null when the URL is unsupported
     */
    private function extractGithubSponsor(string $url): ?string
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if (! \is_string($host) || ! \is_string($path)) {
            return null;
        }

        if (! \in_array($host, ['github.com', 'www.github.com'], true)) {
            return null;
        }

        if (1 !== preg_match('#^/sponsors/([^/]+)$#', $path, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Inserts funding entries in a stable Composer key order.
     *
     * @param array<string, mixed> $data the decoded composer.json payload
     * @param array<int, array<string, mixed>> $entries the funding entries to insert
     *
     * @return array<string, mixed> the composer payload with funding inserted
     */
    private function insertFundingEntries(array $data, array $entries): array
    {
        $orderedData = [];
        $inserted = false;

        foreach ($data as $key => $value) {
            $orderedData[$key] = $value;

            if ('support' === $key) {
                $orderedData['funding'] = $entries;
                $inserted = true;
            }
        }

        if (! $inserted) {
            $orderedData['funding'] = $entries;
        }

        return $orderedData;
    }
}
