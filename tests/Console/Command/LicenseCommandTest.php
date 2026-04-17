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

use FastForward\DevTools\Console\Command\LicenseCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\License\Generator;
use FastForward\DevTools\License\GeneratorInterface;
use FastForward\DevTools\License\Resolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\getcwd;

#[CoversClass(LicenseCommand::class)]
#[UsesClass(Resolver::class)]
#[UsesClass(Generator::class)]
final class LicenseCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<GeneratorInterface>
     */
    private ObjectProphecy $generator;

    /**
     * @var ObjectProphecy<FilesystemInterface>
     */
    private ObjectProphecy $filesystem;

    /**
     * @var ObjectProphecy<InputInterface>
     */
    private ObjectProphecy $input;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    private LicenseCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = $this->prophesize(GeneratorInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new LicenseCommand($this->generator->reveal(), $this->filesystem->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('license', $this->command->getName());
        self::assertSame(
            'Generates a LICENSE file from composer.json license information.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command generates a LICENSE file if one does not exist and a supported license is declared in composer.json.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessAndWriteInfo(): void
    {
        $targetPath = getcwd() . '/LICENSE';

        $this->input->getOption('target')
            ->willReturn('LICENSE');
        $this->filesystem->getAbsolutePath('LICENSE')
            ->willReturn($targetPath);
        $this->filesystem->exists($targetPath)
            ->willReturn(false);
        $this->generator->generate($targetPath)
            ->willReturn('MIT License content');

        $this->output->writeln(Argument::containingString('LICENSE file generated successfully'))->shouldBeCalled();

        self::assertSame(LicenseCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipWhenLicenseFileExists(): void
    {
        $targetPath = getcwd() . '/LICENSE';

        $this->input->getOption('target')
            ->willReturn('LICENSE');
        $this->filesystem->getAbsolutePath('LICENSE')
            ->willReturn($targetPath);
        $this->filesystem->exists($targetPath)
            ->willReturn(true);

        $this->output->writeln(Argument::containingString('file already exists'))->shouldBeCalled();

        self::assertSame(LicenseCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillHandleUnsupportedLicense(): void
    {
        $targetPath = getcwd() . '/LICENSE';

        $this->input->getOption('target')
            ->willReturn('LICENSE');
        $this->filesystem->getAbsolutePath('LICENSE')
            ->willReturn($targetPath);
        $this->filesystem->exists($targetPath)
            ->willReturn(false);
        $this->generator->generate($targetPath)
            ->willReturn(null);

        $this->output->writeln(
            Argument::containingString('No supported license found in composer.json')
        )->shouldBeCalled();

        self::assertSame(LicenseCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return int
     */
    private function invokeExecute(): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
