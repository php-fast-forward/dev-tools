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

use FastForward\DevTools\Console\Output\OutputCapabilityDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

use function Safe\fopen;
use function Safe\fclose;

#[CoversClass(OutputCapabilityDetector::class)]
final class OutputCapabilityDetectorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function supportsAnsiWhenOutputIsDecorated(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output->isDecorated()
            ->willReturn(true);

        self::assertTrue((new OutputCapabilityDetector())->supportsAnsi($output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function doesNotSupportAnsiForPlainNonStreamOutput(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output->isDecorated()
            ->willReturn(false);

        self::assertFalse((new OutputCapabilityDetector())->supportsAnsi($output->reveal()));
    }

    /**
     * @return void
     */
    #[Test]
    public function doesNotSupportAnsiForPlainNonTtyStreamOutput(): void
    {
        $stream = fopen('php://memory', 'w');

        self::assertIsResource($stream);

        try {
            self::assertFalse(
                (new OutputCapabilityDetector())->supportsAnsi(new StreamOutput($stream, decorated: false))
            );
        } finally {
            fclose($stream);
        }
    }
}
