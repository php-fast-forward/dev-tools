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

use FastForward\DevTools\Console\Output\CommandResult;
use FastForward\DevTools\Console\Output\CommandResultRenderer;
use FastForward\DevTools\Console\Output\OutputFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(CommandResult::class)]
#[CoversClass(CommandResultRenderer::class)]
final class CommandResultRendererTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function renderWillWriteHumanReadableSuccessMessages(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output->writeln('<info>Everything is fine.</info>')
            ->shouldBeCalled();

        $renderer = new CommandResultRenderer();
        $renderer->render($output->reveal(), CommandResult::success('Everything is fine.'), OutputFormat::TEXT);
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillWriteHumanReadableFailureMessages(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output->writeln('<error>Something failed.</error>')
            ->shouldBeCalled();

        $renderer = new CommandResultRenderer();
        $renderer->render($output->reveal(), CommandResult::failure('Something failed.'), OutputFormat::TEXT);
    }

    /**
     * @return void
     */
    #[Test]
    public function renderWillWriteJsonPayloadWhenJsonOutputIsRequested(): void
    {
        $output = $this->prophesize(OutputInterface::class);
        $output->writeln('{"status":"success","message":"Everything is fine.","context":{"command":"changelog:check"}}')
            ->shouldBeCalled();

        $renderer = new CommandResultRenderer();
        $renderer->render(
            $output->reveal(),
            CommandResult::success('Everything is fine.', [
                'command' => 'changelog:check',
            ]),
            OutputFormat::JSON,
        );
    }
}
