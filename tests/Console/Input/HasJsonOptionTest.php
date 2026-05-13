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

#[CoversTrait(HasJsonOption::class)]
final class HasJsonOptionTest extends TestCase
{
    use ProphecyTrait;

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

        $command = new HasJsonOptionAwareCommand($runtimeEnvironment->reveal());

        self::assertTrue($command->isStructured($input->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function isJsonOutputWillIgnoreAgentOutputDuringComposerRuns(): void
    {
        $runtimeEnvironment = $this->prophesize(RuntimeEnvironmentInterface::class);
        $runtimeEnvironment->isAgentPresent()
            ->willReturn(true);
        $runtimeEnvironment->isComposerTestRun()
            ->willReturn(true);

        $input = $this->prophesize(InputInterface::class);
        $input->getOption('pretty-json')
            ->willReturn(false);
        $input->getOption('json')
            ->willReturn(false);

        $command = new HasJsonOptionAwareCommand($runtimeEnvironment->reveal());

        self::assertFalse($command->isStructured($input->reveal()));
    }
}

/**
 * @internal
 */
final readonly class HasJsonOptionAwareCommand
{
    use HasJsonOption;

    /**
     * @param RuntimeEnvironmentInterface $runtimeEnvironment
     */
    public function __construct(
        private RuntimeEnvironmentInterface $runtimeEnvironment,
    ) {}

    /**
     * @param InputInterface $input
     *
     * @return bool
     */
    public function isStructured(InputInterface $input): bool
    {
        return $this->isJsonOutput($input);
    }
}
