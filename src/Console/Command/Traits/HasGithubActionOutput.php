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

namespace FastForward\DevTools\Console\Command\Traits;

use Composer\Util\Platform;
use FastForward\DevTools\Console\DevTools;
use FastForward\DevTools\Console\Output\GithubActionOutput;

/**
 * Provides lazy access to GitHub Actions output helpers for Composer commands.
 */
trait HasGithubActionOutput
{
    private ?GithubActionOutput $githubActionOutput = null;

    /**
     * @return bool
     */
    private function supportsGithubActionOutput(): bool
    {
        return 'true' === Platform::getEnv('GITHUB_ACTIONS');
    }

    /**
     * @return GithubActionOutput
     */
    private function getGithubActionOutput(): GithubActionOutput
    {
        return $this->githubActionOutput ??= DevTools::getContainer()
            ->get(GithubActionOutput::class);
    }
}
