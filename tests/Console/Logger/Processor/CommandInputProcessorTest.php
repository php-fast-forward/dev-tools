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

namespace FastForward\DevTools\Tests\Console\Logger\Processor;

use FastForward\DevTools\Console\Logger\Processor\CommandInputProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

#[CoversClass(CommandInputProcessor::class)]
final class CommandInputProcessorTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function processWillExtractProvidedArgumentsAndOptions(): void
    {
        $processor = new CommandInputProcessor();
        $input = new ArrayInput(
            [
                'command' => 'tests',
                'path' => './tests',
                '--filter' => 'CodeStyle',
            ],
            new InputDefinition([
                new InputArgument('command', InputArgument::OPTIONAL),
                new InputArgument('path', InputArgument::OPTIONAL),
                new InputOption('filter', null, InputOption::VALUE_REQUIRED),
                new InputOption('coverage', null, InputOption::VALUE_OPTIONAL, '', '.dev-tools/coverage'),
            ]),
        );

        $context = $processor->process([
            'input' => $input,
        ]);

        self::assertSame('tests', $context['command']);
        self::assertSame([
            'path' => './tests',
        ], $context['arguments']);
        self::assertSame([
            'filter' => 'CodeStyle',
            'coverage' => '.dev-tools/coverage',
        ], $context['options'],);
        self::assertArrayNotHasKey('input', $context);
    }

    /**
     * @return void
     */
    #[Test]
    public function processWillPreserveExistingCommandValue(): void
    {
        $processor = new CommandInputProcessor();
        $input = new ArrayInput(
            [
                'command' => 'tests',
            ],
            new InputDefinition([new InputArgument('command', InputArgument::OPTIONAL)]),
        );

        $context = $processor->process([
            'command' => 'custom',
            'input' => $input,
        ]);

        self::assertSame('custom', $context['command']);
    }
}
