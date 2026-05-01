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
    /**
     * @var string the default project-relative test directory checked by repository capability discovery
     */
    public const string DEFAULT_TESTS_PATH = './tests';

    /**
     * @var string the default project-relative guide directory checked by repository capability discovery
     */
    public const string DEFAULT_GUIDE_DIRECTORY = 'docs';

    /**
     * @var string the default project-relative wiki target used for generated API wiki output
     */
    public const string DEFAULT_WIKI_TARGET = '.github/wiki';

    /**
     * Resolves which documentation, testing, and wiki surfaces are available for the current repository.
     *
     * @param string $testsPath the project-relative tests directory to inspect
     * @param string $guideDirectory the project-relative guide directory to inspect
     * @param string $wikiTarget the project-relative wiki output target to inspect
     */
    public function resolve(
        string $testsPath = self::DEFAULT_TESTS_PATH,
        string $guideDirectory = self::DEFAULT_GUIDE_DIRECTORY,
        string $wikiTarget = self::DEFAULT_WIKI_TARGET,
    ): ProjectCapabilities;
}
