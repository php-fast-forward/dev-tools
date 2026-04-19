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

namespace FastForward\DevTools\Changelog\Renderer;

use FastForward\DevTools\Changelog\Document\ChangelogDocument;
use FastForward\DevTools\Changelog\Document\ChangelogRelease;

/**
 * Renders managed changelog domain objects into markdown.
 */
interface MarkdownRendererInterface
{
    /**
     * Renders the full changelog markdown content.
     *
     * @param ChangelogDocument $document
     * @param ?string $repositoryUrl
     */
    public function render(ChangelogDocument $document, ?string $repositoryUrl = null): string;

    /**
     * Renders only the body content of one released version.
     *
     * @param ChangelogRelease $release
     */
    public function renderReleaseBody(ChangelogRelease $release): string;
}
