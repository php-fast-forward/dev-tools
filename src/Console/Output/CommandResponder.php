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

namespace FastForward\DevTools\Console\Output;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Renders success and failure responses for one resolved command execution.
 */
final readonly class CommandResponder implements CommandResponderInterface
{
    /**
     * @param OutputInterface $output the active command output
     * @param OutputFormat $format the resolved output format
     * @param CommandResultRendererInterface $commandResultRenderer renders normalized command results
     */
    public function __construct(
        private OutputInterface $output,
        private OutputFormat $format,
        private CommandResultRendererInterface $commandResultRenderer,
    ) {}

    /**
     * @param string $message the human-readable summary
     * @param array<string, mixed> $context structured response context
     *
     * @return int the selected exit code
     */
    public function success(string $message, array $context = []): int
    {
        $this->commandResultRenderer->render(
            $this->output,
            CommandResult::success($message, $context),
            $this->format,
        );

        return Command::SUCCESS;
    }

    /**
     * @param string $message the human-readable summary
     * @param array<string, mixed> $context structured response context
     *
     * @return int the selected exit code
     */
    public function failure(string $message, array $context = []): int
    {
        $this->commandResultRenderer->render(
            $this->output,
            CommandResult::failure($message, $context),
            $this->format,
        );

        return Command::FAILURE;
    }
}
