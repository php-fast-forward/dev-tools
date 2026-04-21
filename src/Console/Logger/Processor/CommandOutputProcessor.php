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

namespace FastForward\DevTools\Console\Logger\Processor;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Converts buffered command output objects into serializable context entries.
 */
final class CommandOutputProcessor implements ContextProcessorInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function process(array $context): array
    {
        foreach ($context as $key => $value) {
            if (! $value instanceof OutputInterface) {
                continue;
            }

            unset($context[$key]);

            $outputContent = $this->extractBufferedOutput($value);

            if (null !== $outputContent) {
                $context[$key] = $outputContent;
            }

            if ($value instanceof ConsoleOutputInterface) {
                $errorOutput = $this->extractBufferedOutput($value->getErrorOutput());

                if (null !== $errorOutput && ! \array_key_exists('error_output', $context)) {
                    $context['error_output'] = $errorOutput;
                }
            }
        }

        return $context;
    }

    /**
     * @param OutputInterface $output
     *
     * @return ?string
     */
    private function extractBufferedOutput(OutputInterface $output): ?string
    {
        if (! $output instanceof BufferedOutput) {
            return null;
        }

        return $output->fetch();
    }
}
