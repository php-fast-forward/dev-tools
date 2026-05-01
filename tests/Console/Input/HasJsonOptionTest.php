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

namespace FastForward\DevTools\Tests\Console\Input;

use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Environment\RuntimeEnvironmentInterface;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\InputInterface;

use function Safe\putenv;

#[CoversTrait(HasJsonOption::class)]
final class HasJsonOptionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var array<string, mixed>
     */
    private array $server;

    /**
     * @var array<string, mixed>
     */
    private array $environment;

    private string|false $composerTestsAreRunning;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $this->environment = $_ENV;
        $this->composerTestsAreRunning = getenv('COMPOSER_TESTS_ARE_RUNNING');

        $_SERVER = [];
        $_ENV = [];
        putenv('COMPOSER_TESTS_ARE_RUNNING');
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_ENV = $this->environment;

        if (false === $this->composerTestsAreRunning) {
            putenv('COMPOSER_TESTS_ARE_RUNNING');

            return;
        }

        putenv('COMPOSER_TESTS_ARE_RUNNING=' . $this->composerTestsAreRunning);
    }

    /**
     * @return void
     */
    #[Test]
    public function isJsonOutputWillUseRuntimeEnvironmentWhenAvailable(): void
    {
        $runtimeEnvironment = $this->prophesize(RuntimeEnvironmentInterface::class);
        $runtimeEnvironment->isAgentPresent()
            ->willReturn(true);
        $runtimeEnvironment->isComposerTestRun()
            ->willReturn(false);

        $input = $this->prophesize(InputInterface::class);
        $input->getOption('pretty-json')
            ->willReturn(false);
        $input->getOption('json')
            ->willReturn(false);

        $command = new class ($runtimeEnvironment->reveal()) {
            use HasJsonOption;

            public function __construct(
                private readonly RuntimeEnvironmentInterface $runtimeEnvironment,
            ) {}

            public function isStructured(InputInterface $input): bool
            {
                return $this->isJsonOutput($input);
            }
        };

        self::assertTrue($command->isStructured($input->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function isJsonOutputWillIgnoreFallbackAgentDetectionDuringPhpUnitRuns(): void
    {
        $_SERVER['CODEX_CI'] = '1';

        $input = $this->prophesize(InputInterface::class);
        $input->getOption('pretty-json')
            ->willReturn(false);
        $input->getOption('json')
            ->willReturn(false);

        $command = new class {
            use HasJsonOption;

            public function isStructured(InputInterface $input): bool
            {
                return $this->isJsonOutput($input);
            }
        };

        self::assertFalse($command->isStructured($input->reveal()));
    }
}
