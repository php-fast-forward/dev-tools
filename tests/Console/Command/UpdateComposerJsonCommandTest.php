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

namespace FastForward\DevTools\Tests\Console\Command;

use FastForward\DevTools\Console\Command\UpdateComposerJsonCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(UpdateComposerJsonCommand::class)]
final class UpdateComposerJsonCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private UpdateComposerJsonCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new UpdateComposerJsonCommand(
            $this->filesystem->reveal(),
            $this->fileLocator->reveal(),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('composer-json:update', $this->command->getName());
        self::assertSame(
            'Updates composer.json with Fast Forward dev-tools scripts and metadata.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command adds or updates composer.json scripts and GrumPHP extra configuration required by dev-tools.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillUpdateComposerJsonScriptsAndExtraConfiguration(): void
    {
        $this->input->getOption('file')
            ->willReturn('/app/composer.json');
        $this->filesystem->exists('/app/composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('/app/composer.json')
            ->willReturn('{"name":"example/package"}');
        $this->fileLocator->locate('grumphp.yml', Argument::type('string'))
            ->willReturn('/app/vendor/fast-forward/dev-tools/grumphp.yml');
        $this->filesystem->dumpFile(
            '/app/composer.json',
            Argument::that(static fn (string $contents): bool => str_contains($contents, '"dev-tools"')
                && str_contains($contents, '"grumphp"')),
        )->shouldBeCalledOnce();

        self::assertSame(UpdateComposerJsonCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return int
     */
    private function executeCommand(): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
