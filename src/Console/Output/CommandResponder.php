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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Resolves a command responder bound to the current input and output context.
 */
final readonly class CommandResponder implements CommandResponderInterface
{
    /**
     * @param OutputFormatResolverInterface $outputFormatResolver resolves the output format from console input
     * @param CommandResultRendererInterface $commandResultRenderer renders normalized command results
     */
    public function __construct(
        private OutputFormatResolverInterface $outputFormatResolver,
        private CommandResultRendererInterface $commandResultRenderer,
    ) {}

    /**
     * @param InputInterface $input the active command input
     * @param OutputInterface $output the active command output
     *
     * @return ResolvedCommandResponderInterface the responder bound to the resolved format and output
     */
    public function from(InputInterface $input, OutputInterface $output): ResolvedCommandResponderInterface
    {
        return new ResolvedCommandResponder(
            $output,
            $this->outputFormatResolver->resolve($input),
            $this->commandResultRenderer,
        );
    }
}
