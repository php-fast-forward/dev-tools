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

namespace FastForward\DevTools\CodeOwners;

use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Composer\Json\Schema\AuthorInterface;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use Symfony\Component\Config\FileLocatorInterface;

use function Safe\preg_match;
use function Safe\parse_url;
use function Safe\preg_replace;
use function Safe\preg_split;
use function array_filter;
use function array_map;
use function array_unique;
use function implode;
use function is_iterable;
use function str_contains;
use function str_starts_with;
use function trim;

/**
 * Generates CODEOWNERS content from repository metadata.
 */
final readonly class CodeOwnersGenerator
{
    /**
     * Creates a new generator instance.
     *
     * @param ComposerJsonInterface $composer the composer metadata accessor
     * @param FilesystemInterface $filesystem the filesystem used to read the packaged template
     * @param FileLocatorInterface $fileLocator the locator used to find the packaged template
     */
    public function __construct(
        private ComposerJsonInterface $composer,
        private FilesystemInterface $filesystem,
        private FileLocatorInterface $fileLocator,
    ) {}

    /**
     * Returns the automatically inferred CODEOWNERS handles.
     *
     * @return list<string>
     */
    public function inferOwners(): array
    {
        $owners = [];
        $groupOwner = $this->inferGroupOwner();

        if (null !== $groupOwner) {
            $owners[] = $groupOwner;
        }

        $authors = $this->composer->getAuthors();

        if (! is_iterable($authors)) {
            return $owners;
        }

        foreach ($authors as $author) {
            if (! $author instanceof AuthorInterface) {
                continue;
            }

            $handle = $this->extractGitHubHandleFromUrl($author->getHomepage());

            if (null === $handle) {
                continue;
            }

            $owners[] = '@' . $handle;
        }

        return array_values(array_unique($owners));
    }

    /**
     * Normalizes user-provided owner tokens.
     *
     * @param string $owners the raw owner input
     *
     * @return list<string>
     */
    public function normalizeOwners(string $owners): array
    {
        $tokens = preg_split('/[\s,]+/', trim($owners));
        $normalized = array_map(
            static function (string $owner): string {
                if ('' === $owner) {
                    return '';
                }

                if (str_contains($owner, '@') && ! str_starts_with($owner, '@')) {
                    return $owner;
                }

                return str_starts_with($owner, '@') ? $owner : '@' . $owner;
            },
            $tokens,
        );

        return array_values(array_unique(array_filter($normalized, static fn(string $owner): bool => '' !== $owner)));
    }

    /**
     * Generates CODEOWNERS contents.
     *
     * @param list<string>|null $owners explicit owners to render; inferred owners are used when null
     *
     * @return string the rendered CODEOWNERS file contents
     */
    public function generate(?array $owners = null): string
    {
        $owners ??= $this->inferOwners();
        $template = $this->filesystem->readFile($this->fileLocator->locate('resources/CODEOWNERS.dist'));
        $suggestionBlock = [] === $owners
            ? '# No GitHub owners could be inferred from composer.json metadata.'
            : '';
        $rule = [] === $owners
            ? '# * @your-github-user'
            : \sprintf('* %s', implode(' ', $owners));

        return str_replace(['{{ suggestions }}', '{{ rule }}'], [$suggestionBlock, $rule], $template);
    }

    /**
     * Returns the repository or organization owner inferred from support metadata.
     *
     * @return string|null the inferred group owner with `@`, or null when unavailable
     */
    public function inferGroupOwner(): ?string
    {
        $source = $this->composer->getSupport()
            ->getSource();

        if ('' === $source) {
            return null;
        }

        $owner = $this->extractGitHubRepositoryOwner($source);

        if (null === $owner) {
            return null;
        }

        return '@' . $owner;
    }

    /**
     * Extracts a GitHub user handle from a homepage URL.
     *
     * @param string $url the homepage URL
     *
     * @return string|null the GitHub handle without `@`, or null when unavailable
     */
    private function extractGitHubHandleFromUrl(string $url): ?string
    {
        $path = $this->githubPath($url);

        if (null === $path) {
            return null;
        }

        if (0 === preg_match('#^/([^/]+)/?$#', $path, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Extracts the repository owner from a GitHub repository URL.
     *
     * @param string $url the repository URL
     *
     * @return string|null the owner without `@`, or null when unavailable
     */
    private function extractGitHubRepositoryOwner(string $url): ?string
    {
        $path = $this->githubPath($url);

        if (null === $path) {
            return null;
        }

        if (0 === preg_match('#^/([^/]+)/([^/]+)/?$#', $path, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Returns the path portion of a GitHub URL when the host matches github.com.
     *
     * @param string $url the URL to inspect
     *
     * @return string|null the URL path, or null when the URL is not a GitHub URL
     */
    private function githubPath(string $url): ?string
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if ('github.com' !== $host || ! \is_string($path)) {
            return null;
        }

        return preg_replace('#/+?#', '/', $path);
    }
}
