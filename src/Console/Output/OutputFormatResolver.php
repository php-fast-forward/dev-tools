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

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Validates the requested output format from console input.
 */
final class OutputFormatResolver implements OutputFormatResolverInterface
{
    /**
     * @param InputInterface $input the active command input
     *
     * @return OutputFormat the validated output format for this execution
     */
    public function resolve(InputInterface $input): OutputFormat
    {
        $format = $input->getOption('output-format');

        if (! \is_string($format) || '' === $format) {
            return OutputFormat::TEXT;
        }

        $resolvedFormat = OutputFormat::tryFrom($format);

        if ($resolvedFormat instanceof OutputFormat) {
            return $resolvedFormat;
        }

        throw new InvalidArgumentException('The --output-format option MUST be one of: text, json.');
    }
}
