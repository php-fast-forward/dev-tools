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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use FastForward\DevTools\Console\Command\PhpDocCommand;
use FastForward\DevTools\Template\EngineInterface;
use FastForward\DevTools\Template\VariablesFactoryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use ReflectionMethod;

#[CoversClass(PhpDocCommand::class)]
final class PhpDocCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<EngineInterface>
     */
    private ObjectProphecy $engine;

    /**
     * @var ObjectProphecy<VariablesFactoryInterface>
     */
    private ObjectProphecy $variablesFactory;

    /**
     * @var ObjectProphecy<FileLocatorInterface>
     */
    private ObjectProphecy $fileLocator;

    /**
     * @var ObjectProphecy<Filesystem>
     */
    private ObjectProphecy $filesystem;

    private PhpDocCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->engine = $this->prophesize(EngineInterface::class);
        $this->variablesFactory = $this->prophesize(VariablesFactoryInterface::class);
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->command = new PhpDocCommand(
            $this->engine->reveal(),
            $this->variablesFactory->reveal(),
            $this->fileLocator->reveal(),
            $this->filesystem->reveal()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('phpdoc', $this->command->getName());
        self::assertSame('Checks and fixes PHPDocs.', $this->command->getDescription());
        self::assertSame('This command checks and fixes PHPDocs in your PHP files.', $this->command->getHelp());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillCopyDocHeaderWhenMissing(): void
    {
        $input = $this->prophesize(InputInterface::class);
        $output = $this->prophesize(OutputInterface::class);

        $input->getOption('fix')
            ->willReturn(false);

        // Path is missing because it's not base path scenario
        $this->fileLocator->locate(PhpDocCommand::FILENAME)->willReturn('/vendor/packaged/.docheader');

        $variables = [
            '{{ project }}' => 'fast-forward/dev-tools',
        ];
        $this->variablesFactory->getVariables()
            ->willReturn($variables);

        $this->engine->render('resources/dockblock/.docheader', $variables)
            ->willReturn('Content');
        $this->filesystem->dumpFile('/vendor/packaged/.docheader', 'Content')
            ->shouldBeCalled();

        self::assertSame(PhpDocCommand::SUCCESS, $this->invokeExecute($input->reveal(), $output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillHandleDumpFileException(): void
    {
        $input = $this->prophesize(InputInterface::class);
        $output = $this->prophesize(OutputInterface::class);

        $input->getOption('fix')
            ->willReturn(false);

        $this->fileLocator->locate(PhpDocCommand::FILENAME)->willReturn('/vendor/packaged/.docheader');

        $this->variablesFactory->getVariables()
            ->willReturn([]);
        $this->engine->render('resources/dockblock/.docheader', [])->willReturn('Content');

        $this->filesystem->dumpFile('/vendor/packaged/.docheader', 'Content')
            ->willThrow(new RuntimeException('dump error'));

        self::assertSame(PhpDocCommand::SUCCESS, $this->invokeExecute($input->reveal(), $output->reveal()));
    }

    /**
     * @param mixed $input
     * @param mixed $output
     *
     * @return int
     */
    private function invokeExecute($input, $output): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $input, $output);
    }
}
