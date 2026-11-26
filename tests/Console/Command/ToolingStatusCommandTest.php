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

use FastForward\DevTools\Console\Command\ToolingStatusCommand;
use FastForward\DevTools\Console\Input\HasJsonOption;
use FastForward\DevTools\Tooling\ToolingProfileInterface;
use FastForward\DevTools\Container\ContainerFactory;
use FastForward\DevTools\Container\ServiceProvider\DevToolsServiceProvider;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Environment\RuntimeEnvironmentInterface;
use FastForward\DevTools\Tests\Container\UsesContainerFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Tester\CommandTester;

use function Safe\json_decode;

/**
 * @copyright 2026 Fast Forward
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[CoversClass(ToolingStatusCommand::class)]
#[UsesTrait(HasJsonOption::class)]
#[UsesClass(ContainerFactory::class)]
#[UsesClass(DevToolsServiceProvider::class)]
#[UsesClass(DevToolsPathResolver::class)]
final class ToolingStatusCommandTest extends TestCase
{
    use ProphecyTrait;
    use UsesContainerFactory;

    /**
     * @return void
     */
    #[Test]
    public function textStatusWillDisplaySelectionAndMissingTools(): void
    {
        $environment = $this->prophesize(RuntimeEnvironmentInterface::class);
        $environment->isAgentPresent()
            ->willReturn(false);
        $this->setContainerEntry(RuntimeEnvironmentInterface::class, $environment->reveal());
        $profile = $this->prophesize(ToolingProfileInterface::class);
        $profile->status()
            ->willReturn([
                'tests' => [
                    'selected' => true,
                    'available' => false,
                    'missing' => ['phpunit'],
                    'binaries' => [],
                ],
            ]);
        $tester = new CommandTester(new ToolingStatusCommand($profile->reveal()));

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('Missing tools', $tester->getDisplay());
        self::assertStringContainsString('phpunit', $tester->getDisplay());
    }

    /**
     * @return void
     */
    #[Test]
    public function invalidProfileWillReturnFailureWithoutPretendingToolsAreAvailable(): void
    {
        $profile = $this->prophesize(ToolingProfileInterface::class);
        $profile->status()
            ->willThrow(new InvalidArgumentException('Invalid profile schema.'));
        $tester = new CommandTester(new ToolingStatusCommand($profile->reveal()));

        self::assertSame(1, $tester->execute([
            '--json' => true,
        ]));
        self::assertStringContainsString('Invalid profile schema', $tester->getDisplay());
    }

    /**
     * @return void
     */
    #[Test]
    public function structuredStatusWillDistinguishSelectedAndAvailableWithoutInstalling(): void
    {
        $profile = $this->prophesize(ToolingProfileInterface::class);
        $metadata = [
            'tests' => [
                'selected' => true,
                'available' => false,
                'missing' => ['phpunit'],
                'binaries' => [
                    'phpunit' => '/runtime/vendor/bin/phpunit',
                ],
            ],
        ];
        $profile->status()
            ->willReturn($metadata);
        $profile->read()
            ->willReturn(['tests']);
        $tester = new CommandTester(new ToolingStatusCommand($profile->reveal()));

        self::assertSame(0, $tester->execute([
            '--json' => true,
        ]));
        self::assertSame([
            'version' => 1,
            'profile' => ['tests'],
            'capabilities' => $metadata,
        ], json_decode($tester->getDisplay(), true));
    }
}
