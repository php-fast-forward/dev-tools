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

namespace FastForward\DevTools\Tests\Console\Command;

use FastForward\DevTools\Console\Command\SetupCommand;
use FastForward\DevTools\Tooling\ToolingProfile;
use FastForward\DevTools\Tooling\ToolingProfileInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @copyright 2026 Fast Forward
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[CoversClass(SetupCommand::class)]
#[UsesClass(ToolingProfile::class)]
final class SetupCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function repeatableCapabilitiesWillPersistWithoutInteractiveInput(): void
    {
        $profile = $this->prophesize(ToolingProfileInterface::class);
        $profile->read()
            ->willReturn(null);
        $profile->configure(['tests', 'docs'], false)->shouldBeCalledOnce()->willReturn(true);
        $tester = new CommandTester(new SetupCommand($profile->reveal()));

        self::assertSame(0, $tester->execute([
            '--capability' => ['docs', 'tests'],
        ], [
            'interactive' => false,
        ]));
        self::assertStringContainsString('no tools installed', $tester->getDisplay());
    }

    /**
     * @return void
     */
    #[Test]
    public function dryRunWillForwardPreviewWithoutWriting(): void
    {
        $profile = $this->prophesize(ToolingProfileInterface::class);
        $profile->read()
            ->willReturn(null);
        $profile->configure(['metrics'], true)->shouldBeCalledOnce()->willReturn(true);
        $tester = new CommandTester(new SetupCommand($profile->reveal()));

        self::assertSame(0, $tester->execute([
            '--capability' => ['metrics'],
            '--dry-run' => true,
        ], [
            'interactive' => false,
        ]));
        self::assertStringContainsString('no files changed', $tester->getDisplay());
    }

    /**
     * @return void
     */
    #[Test]
    public function interactiveSelectionWillPersistChosenCapabilities(): void
    {
        $profile = $this->prophesize(ToolingProfileInterface::class);
        $profile->read()
            ->willReturn(null);
        $profile->configure(['tests', 'metrics'], false)->shouldBeCalledOnce()->willReturn(true);
        $tester = new CommandTester(new SetupCommand($profile->reveal()));
        $tester->setInputs(['0,3']);

        self::assertSame(0, $tester->execute([], [
            'interactive' => true,
        ]));
    }

    /**
     * @return void
     */
    #[Test]
    public function missingNoninteractiveSelectionWillFailWithoutWriting(): void
    {
        $profile = $this->prophesize(ToolingProfileInterface::class);
        $profile->read()
            ->willReturn(null);
        $profile->configure(Argument::cetera())->shouldNotBeCalled();
        $tester = new CommandTester(new SetupCommand($profile->reveal()));

        self::assertSame(1, $tester->execute([], [
            'interactive' => false,
        ]));
    }

    /**
     * @return void
     */
    #[Test]
    public function malformedProfileWillFailBeforeAnyOverwrite(): void
    {
        $profile = $this->prophesize(ToolingProfileInterface::class);
        $profile->read()
            ->willThrow(new InvalidArgumentException('Malformed dev-tools.json.'));
        $profile->configure(Argument::cetera())->shouldNotBeCalled();
        $tester = new CommandTester(new SetupCommand($profile->reveal()));

        self::assertSame(1, $tester->execute([
            '--capability' => ['tests'],
        ], [
            'interactive' => false,
        ]));
        self::assertStringContainsString('Malformed dev-tools.json', $tester->getDisplay());
    }
}
