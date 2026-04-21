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

namespace FastForward\DevTools\Tests\Console\Output;

use FastForward\DevTools\Console\Output\OutputFormat;
use FastForward\DevTools\Console\Output\OutputFormatResolver;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\InputInterface;

#[CoversClass(OutputFormatResolver::class)]
final class OutputFormatResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function resolveWillReturnTextFormatByDefault(): void
    {
        $input = $this->prophesize(InputInterface::class);
        $input->getOption('format')
            ->willReturn('text');

        $resolver = new OutputFormatResolver();

        self::assertSame(OutputFormat::TEXT, $resolver->resolve($input->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWillReturnJsonFormatWhenRequested(): void
    {
        $input = $this->prophesize(InputInterface::class);
        $input->getOption('format')
            ->willReturn('json');

        $resolver = new OutputFormatResolver();

        self::assertSame(OutputFormat::JSON, $resolver->resolve($input->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWillReturnTextFormatWhenOptionIsEmpty(): void
    {
        $input = $this->prophesize(InputInterface::class);
        $input->getOption('format')
            ->willReturn('');

        $resolver = new OutputFormatResolver();

        self::assertSame(OutputFormat::TEXT, $resolver->resolve($input->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveWillRejectUnsupportedFormats(): void
    {
        $input = $this->prophesize(InputInterface::class);
        $input->getOption('format')
            ->willReturn('xml');

        $resolver = new OutputFormatResolver();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The --format option MUST be one of: text, json.');

        $resolver->resolve($input->reveal());
    }
}
