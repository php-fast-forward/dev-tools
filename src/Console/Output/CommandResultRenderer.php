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

use Symfony\Component\Console\Output\OutputInterface;

use function Safe\json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Renders normalized command results for human and machine-readable consumers.
 */
final class CommandResultRenderer implements CommandResultRendererInterface
{
    /**
     * @param OutputInterface $output the target console output
     * @param CommandResult $result the normalized command result
     * @param OutputFormat $format the selected output format
     */
    public function render(OutputInterface $output, CommandResult $result, OutputFormat $format): void
    {
        if (OutputFormat::JSON === $format) {
            $output->writeln(json_encode($result->toArray(), JSON_THROW_ON_ERROR));

            return;
        }

        $tag = 'success' === $result->status ? 'info' : 'error';

        $output->writeln(\sprintf('<%s>%s</%s>', $tag, $result->message, $tag));
    }
}
