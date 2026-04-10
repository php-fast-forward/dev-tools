<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\Command;

use FastForward\DevTools\Command\DependenciesCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

use function Safe\getcwd;

#[CoversClass(DependenciesCommand::class)]
final class DependenciesCommandTest extends AbstractCommandTestCase
{
    /**
     * @var list<array{exitCode:int, output:string}>
     */
    private array $queuedResults = [];

    /**
     * @var list<list<string>>
     */
    private array $receivedCommands = [];

    private DependenciesCommand $dependenciesCommand;

    /**
     * @return DependenciesCommand
     */
    protected function getCommandClass(): DependenciesCommand
    {
        $this->queuedResults = [];
        $this->receivedCommands = [];
        $queuedResults = &$this->queuedResults;
        $receivedCommands = &$this->receivedCommands;

        $this->dependenciesCommand = new DependenciesCommand(
            $this->filesystem->reveal(),
            static function (array $command) use (&$queuedResults, &$receivedCommands): array {
                $receivedCommands[] = $command;

                return array_shift($queuedResults) ?? [
                    'exitCode' => DependenciesCommand::SUCCESS,
                    'output' => '',
                ];
            },
        );

        return $this->dependenciesCommand;
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'dependencies';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Analyzes missing and unused Composer dependencies.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command runs composer-dependency-analyser and composer-unused to report missing and unused Composer dependencies.';
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillFailWhenRequiredFilesAreMissing(): void
    {
        $cwd = getcwd();

        $this->filesystem->exists($cwd . '/composer.json')->willReturn(true);
        $this->filesystem->exists($cwd . '/vendor/bin/composer-dependency-analyser')->willReturn(false);
        $this->filesystem->exists($cwd . '/vendor/bin/composer-unused')->willReturn(false);

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalled();
        $this->output->writeln('<error>Dependency analysis requires the following files:</error>')
            ->shouldBeCalled();
        $this->output->writeln(
            '- vendor/bin/composer-dependency-analyser not found. Install: composer require --dev shipmonk/composer-dependency-analyser'
        )
            ->shouldBeCalled();
        $this->output->writeln(
            '- vendor/bin/composer-unused not found. Install: composer require --dev icanhazstring/composer-unused'
        )
            ->shouldBeCalled();

        self::assertSame(DependenciesCommand::FAILURE, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillRenderNormalizedDependencyReport(): void
    {
        $cwd = getcwd();

        $this->filesystem->exists($cwd . '/composer.json')->willReturn(true);
        $this->filesystem->exists($cwd . '/vendor/bin/composer-dependency-analyser')->willReturn(true);
        $this->filesystem->exists($cwd . '/vendor/bin/composer-unused')->willReturn(true);

        $this->queuedResults[] = [
            'exitCode' => DependenciesCommand::FAILURE,
            'output' => <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <testsuites>
                  <testsuite name="shadow dependencies" failures="1">
                    <testcase name="symfony/console">
                      <failure message="Symfony\Component\Console\Command\Command">src/Command/DependenciesCommand.php:42</failure>
                    </testcase>
                  </testsuite>
                </testsuites>
                XML,
        ];
        $this->queuedResults[] = [
            'exitCode' => DependenciesCommand::FAILURE,
            'output' => <<<'JSON'
                {
                    "unused-packages": [
                        "monolog/monolog"
                    ]
                }
                JSON,
        ];

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalled();
        $this->output->writeln('Dependency Analysis Report')
            ->shouldBeCalled();
        $this->output->writeln('[Missing Dependencies]')
            ->shouldBeCalled();
        $this->output->writeln('- symfony/console <comment>(src/Command/DependenciesCommand.php:42)</comment>')
            ->shouldBeCalled();
        $this->output->writeln('[Unused Dependencies]')
            ->shouldBeCalled();
        $this->output->writeln('- monolog/monolog')
            ->shouldBeCalled();
        $this->output->writeln('Summary:')
            ->shouldBeCalled();
        $this->output->writeln('- 1 missing')
            ->shouldBeCalled();
        $this->output->writeln('- 1 unused')
            ->shouldBeCalled();
        $this->output->writeln('')
            ->shouldBeCalledTimes(4);

        self::assertSame(DependenciesCommand::FAILURE, $this->invokeExecute());
        self::assertSame([
            [
                $cwd . '/vendor/bin/composer-dependency-analyser',
                '--composer-json=' . $cwd . '/composer.json',
                '--format=junit',
                '--ignore-unused-deps',
                '--ignore-dev-in-prod-deps',
                '--ignore-prod-only-in-dev-deps',
                '--ignore-unknown-classes',
                '--ignore-unknown-functions',
            ],
            [
                $cwd . '/vendor/bin/composer-unused',
                $cwd . '/composer.json',
                '--output-format=json',
                '--no-progress',
            ],
        ], $this->receivedCommands);
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenNoFindingsAreReported(): void
    {
        $cwd = getcwd();

        $this->filesystem->exists($cwd . '/composer.json')->willReturn(true);
        $this->filesystem->exists($cwd . '/vendor/bin/composer-dependency-analyser')->willReturn(true);
        $this->filesystem->exists($cwd . '/vendor/bin/composer-unused')->willReturn(true);

        $this->queuedResults[] = [
            'exitCode' => DependenciesCommand::SUCCESS,
            'output' => '<?xml version="1.0" encoding="UTF-8"?><testsuites></testsuites>',
        ];
        $this->queuedResults[] = [
            'exitCode' => DependenciesCommand::SUCCESS,
            'output' => '{"unused-packages":[]}',
        ];

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalled();
        $this->output->writeln('Dependency Analysis Report')
            ->shouldBeCalled();
        $this->output->writeln('[Missing Dependencies]')
            ->shouldBeCalled();
        $this->output->writeln('None detected.')
            ->shouldBeCalledTimes(2);
        $this->output->writeln('[Unused Dependencies]')
            ->shouldBeCalled();
        $this->output->writeln('Summary:')
            ->shouldBeCalled();
        $this->output->writeln('- 0 missing')
            ->shouldBeCalled();
        $this->output->writeln('- 0 unused')
            ->shouldBeCalled();
        $this->output->writeln('')
            ->shouldBeCalledTimes(4);

        self::assertSame(DependenciesCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReportUnreadableAnalyzerOutputAsFailure(): void
    {
        $cwd = getcwd();

        $this->filesystem->exists($cwd . '/composer.json')->willReturn(true);
        $this->filesystem->exists($cwd . '/vendor/bin/composer-dependency-analyser')->willReturn(true);
        $this->filesystem->exists($cwd . '/vendor/bin/composer-unused')->willReturn(true);

        $this->queuedResults[] = [
            'exitCode' => DependenciesCommand::FAILURE,
            'output' => 'unexpected failure',
        ];
        $this->queuedResults[] = [
            'exitCode' => DependenciesCommand::SUCCESS,
            'output' => '{"unused-packages":[]}',
        ];

        $this->output->writeln('<info>Running dependency analysis...</info>')
            ->shouldBeCalled();
        $this->output->writeln('Dependency Analysis Report')
            ->shouldBeCalled();
        $this->output->writeln('[Missing Dependencies]')
            ->shouldBeCalled();
        $this->output->writeln('<error>composer-dependency-analyser did not return a readable report.</error>')
            ->shouldBeCalled();
        $this->output->writeln('unexpected failure')
            ->shouldBeCalled();
        $this->output->writeln('[Unused Dependencies]')
            ->shouldBeCalled();
        $this->output->writeln('None detected.')
            ->shouldBeCalled();
        $this->output->writeln('Summary:')
            ->shouldBeCalled();
        $this->output->writeln('- dependency analysis could not be completed.')
            ->shouldBeCalled();
        $this->output->writeln('')
            ->shouldBeCalledTimes(4);

        self::assertSame(DependenciesCommand::FAILURE, $this->invokeExecute());
    }
}
