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

namespace FastForward\DevTools\Project;

/**
 * Resolves the effective documentation, testing, and wiki capabilities of the current repository.
 */
interface ProjectCapabilitiesResolverInterface
{
    public const string DEFAULT_TESTS_PATH = './tests';

    public const string DEFAULT_GUIDE_DIRECTORY = 'docs';

    public const string DEFAULT_WIKI_TARGET = '.github/wiki';

    /**
     * @param string $testsPath
     * @param string $guideDirectory
     * @param string $wikiTarget
     *
     * @return ProjectCapabilities
     */
    public function resolve(
        string $testsPath = self::DEFAULT_TESTS_PATH,
        string $guideDirectory = self::DEFAULT_GUIDE_DIRECTORY,
        string $wikiTarget = self::DEFAULT_WIKI_TARGET,
    ): ProjectCapabilities;
}
