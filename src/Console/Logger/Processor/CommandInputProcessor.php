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

use ReflectionProperty;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

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

        $arguments = $this->extractProvidedArguments($input);
        $command = $this->inferCommandName($context, $input, $arguments);

        if (null !== $command && ! \array_key_exists('command', $context)) {
            $context['command'] = $command;
        }

        unset($arguments['command']);

        if ([] !== $arguments && ! \array_key_exists('arguments', $context)) {
            $context['arguments'] = $arguments;
        }

        $options = $this->extractProvidedOptions($input);
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

    /**
     * @param InputInterface $input
     *
     * @return array<string, mixed>
     */
    private function extractProvidedArguments(InputInterface $input): array
    {
        $arguments = [];
        $arrayParameters = $this->resolveArrayParameters($input);

        foreach ($input->getArguments() as $name => $value) {
            if (null === $value) {
                continue;
            }

            if (\is_array($value)) {
                if ([] === $value) {
                    continue;
                }

                $providedValues = array_values(array_filter(
                    $value,
                    fn(mixed $item): bool => \is_scalar($item) && $input->hasParameterOption((string) $item, true),
                ));

                if ([] !== $providedValues) {
                    $arguments[$name] = $providedValues;
                }

                continue;
            }

            if (
                (\is_array($arrayParameters) && \array_key_exists($name, $arrayParameters))
                || (\is_scalar($value) && $input->hasParameterOption((string) $value, true))
            ) {
                $arguments[$name] = $value;
            }
        }

        return $arguments;
    }

    /**
     * @param InputInterface $input
     *
     * @return array<string, mixed>
     */
    private function extractProvidedOptions(InputInterface $input): array
    {
        $definition = $this->resolveDefinition($input);

        if (! $definition instanceof InputDefinition) {
            return [];
        }

        $options = [];

        foreach ($definition->getOptions() as $option) {
            $tokens = $this->optionTokens($option);

            if (! $input->hasParameterOption($tokens, true)) {
                continue;
            }

            $options[$option->getName()] = $input->getOption($option->getName());
        }

        return $options;
    }

    /**
     * @param InputInterface $input
     *
     * @return InputDefinition|null
     */
    private function resolveDefinition(InputInterface $input): ?InputDefinition
    {
        if (! $input instanceof Input) {
            return null;
        }

        static $property;

        if (! $property instanceof ReflectionProperty) {
            $property = new ReflectionProperty(Input::class, 'definition');
        }

        /** @var InputDefinition $definition */
        $definition = $property->getValue($input);

        return $definition;
    }

    /**
     * @param InputInterface $input
     *
     * @return array<string|int, mixed>|null
     */
    private function resolveArrayParameters(InputInterface $input): ?array
    {
        if (! $input instanceof ArrayInput) {
            return null;
        }

        static $property;

        if (! $property instanceof ReflectionProperty) {
            $property = new ReflectionProperty(ArrayInput::class, 'parameters');
        }

        /** @var array<string|int, mixed> $parameters */
        $parameters = $property->getValue($input);

        return $parameters;
    }

    /**
     * @param InputOption $option
     *
     * @return list<string>
     */
    private function optionTokens(InputOption $option): array
    {
        $tokens = ['--' . $option->getName()];
        $shortcut = $option->getShortcut();

        if (null !== $shortcut && '' !== $shortcut) {
            foreach (explode('|', $shortcut) as $alias) {
                if ('' !== $alias) {
                    $tokens[] = '-' . $alias;
                }
            }
        }

        return $tokens;
    }
}
