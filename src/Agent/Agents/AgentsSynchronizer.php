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

namespace FastForward\DevTools\Agent\Agents;

use FastForward\DevTools\Agent\Sync\PackagedDirectorySynchronizer;
use FastForward\DevTools\Agent\Sync\SynchronizeResult;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Synchronizes packaged Fast Forward project agents into consumer repositories.
 */
final readonly class AgentsSynchronizer implements LoggerAwareInterface
{
    /**
     * @param PackagedDirectorySynchronizer $synchronizer
     */
    public function __construct(
        private PackagedDirectorySynchronizer $synchronizer,
    ) {}

    /**
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->synchronizer->setLogger($logger);
    }

    /**
     * @param string $agentsDir
     * @param string $packageAgentsPath
     *
     * @return SynchronizeResult
     */
    public function synchronize(string $agentsDir, string $packageAgentsPath): SynchronizeResult
    {
        return $this->synchronizer->synchronize($agentsDir, $packageAgentsPath, '.agents/agents');
    }
}
