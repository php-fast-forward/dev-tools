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

use Symfony\Component\Console\Input\InputInterface;

/**
 * Expands command input instances into structured context entries.
 */
final class CommandInputProcessor implements ContextProcessorInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function process(array $context): array
    {
        foreach ($context as $key => $value) {
            if (! $value instanceof InputInterface) {
                continue;
            }

            $context = $this->processInputContext($context, $key, $value);
        }

        return $context;
    }

    /**
     * @param array<string, mixed> $context
     * @param string|int $key
     * @param InputInterface $input
     *
     * @return array<string, mixed>
     */
    private function processInputContext(array $context, string|int $key, InputInterface $input): array
    {
        unset($context[$key]);

        $arguments = $input->getArguments();
        $command = $this->inferCommandName($context, $input, $arguments);

        if (null !== $command && ! \array_key_exists('command', $context)) {
            $context['command'] = $command;
        }

        unset($arguments['command']);

        if ([] !== $arguments && ! \array_key_exists('arguments', $context)) {
            $context['arguments'] = $arguments;
        }

        /** @var array<string, mixed> $options */
        $options = $input->getOptions();
        if ([] !== $options && ! \array_key_exists('options', $context)) {
            $context['options'] = $options;
        }

        return $context;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $arguments
     * @param InputInterface $input
     *
     * @return string|null
     */
    private function inferCommandName(array $context, InputInterface $input, array $arguments): ?string
    {
        if (\array_key_exists('command', $context)) {
            return null;
        }

        if (\array_key_exists('command', $arguments) && \is_string($arguments['command'])) {
            return $arguments['command'];
        }

        $command = $input->getFirstArgument();

        return \is_string($command) ? $command : null;
    }
}
