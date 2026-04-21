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

namespace FastForward\DevTools\Console\Formatter;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Defines additional console styles for log level tags.
 *
 * This formatter extends the default Symfony output formatter and SHALL
 * register a predefined set of styles mapped to common PSR-3 log levels.
 * These styles MAY be used by console messages that wrap content in tags
 * such as "<error>", "<info>", or "<debug>".
 *
 * The formatter MUST enable decorated output so that registered styles can
 * be rendered by compatible console outputs. Consumers SHOULD use this
 * formatter when log messages are emitted with tag names that correspond to
 * log levels.
 */
final class LogLevelOutputFormatter extends OutputFormatter
{
    /**
     * Initializes the formatter with predefined styles for log levels.
     *
     * The registered styles SHALL provide visual differentiation for the
     * supported log levels. Implementations MAY extend this formatter if
     * additional custom styles are required, but this constructor MUST
     * preserve the base formatter behavior by delegating initialization to
     * the parent constructor.
     */
    public function __construct()
    {
        $additionalStyles = [
            'emergency' => new OutputFormatterStyle('red', null, ['bold']),
            'alert' => new OutputFormatterStyle('red', null, ['bold']),
            'critical' => new OutputFormatterStyle('red', null, ['bold']),
            'error' => new OutputFormatterStyle('red'),
            'warning' => new OutputFormatterStyle('yellow'),
            'notice' => new OutputFormatterStyle('blue'),
            'info' => new OutputFormatterStyle('green'),
            'debug' => new OutputFormatterStyle('cyan'),
        ];

        parent::__construct(true, $additionalStyles);
    }
}
