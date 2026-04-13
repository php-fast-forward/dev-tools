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

use FastForward\DevTools\Console\Command\CopyLicenseCommand;
use FastForward\DevTools\License\Generator;
use FastForward\DevTools\License\GeneratorInterface;
use FastForward\DevTools\License\PlaceholderResolver;
use FastForward\DevTools\License\Reader;
use FastForward\DevTools\License\Resolver;
use FastForward\DevTools\License\TemplateLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

use function Safe\getcwd;

#[CoversClass(CopyLicenseCommand::class)]
#[UsesClass(Reader::class)]
#[UsesClass(Resolver::class)]
#[UsesClass(TemplateLoader::class)]
#[UsesClass(PlaceholderResolver::class)]
#[UsesClass(Generator::class)]
final class CopyLicenseCommandTest extends AbstractCommandTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<GeneratorInterface>
     */
    private ObjectProphecy $generator;

    /**
     * @return CopyLicenseCommand
     */
    protected function getCommandClass(): CopyLicenseCommand
    {
        return new CopyLicenseCommand($this->generator->reveal(), $this->filesystem->reveal());
    }

    /**
     * @return string
     */
    protected function getCommandName(): string
    {
        return 'license';
    }

    /**
     * @return string
     */
    protected function getCommandDescription(): string
    {
        return 'Generates a LICENSE file from composer.json license information.';
    }

    /**
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return 'This command generates a LICENSE file if one does not exist and a supported license is declared in composer.json.';
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->generator = $this->prophesize(GeneratorInterface::class);

        parent::setUp();
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessAndWriteInfo(): void
    {
        $targetPath = getcwd() . '/LICENSE';
        $this->filesystem->exists($targetPath)
            ->willReturn(false);
        $this->generator->generate($targetPath)
            ->willReturn(null);

        self::assertSame(CopyLicenseCommand::SUCCESS, $this->invokeExecute());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipWhenLicenseFileExists(): void
    {
        $targetPath = getcwd() . '/LICENSE';
        $this->filesystem->exists($targetPath)
            ->willReturn(true);

        self::assertSame(CopyLicenseCommand::SUCCESS, $this->invokeExecute());
    }
}
