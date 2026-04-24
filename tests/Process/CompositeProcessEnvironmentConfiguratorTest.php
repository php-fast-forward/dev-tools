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

namespace FastForward\DevTools\Tests\Process;

use FastForward\DevTools\Process\CompositeProcessEnvironmentConfigurator;
use FastForward\DevTools\Process\ProcessEnvironmentConfiguratorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(CompositeProcessEnvironmentConfigurator::class)]
final class CompositeProcessEnvironmentConfiguratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function configureDelegatesToEveryConfigurator(): void
    {
        $process = $this->prophesize(Process::class);
        $output = $this->prophesize(OutputInterface::class);
        $firstConfigurator = $this->createConfiguratorMock($process, $output);
        $secondConfigurator = $this->createConfiguratorMock($process, $output);

        $configurator = new CompositeProcessEnvironmentConfigurator([
            $firstConfigurator->reveal(),
            $secondConfigurator->reveal(),
        ]);

        $configurator->configure($process->reveal(), $output->reveal());
    }

    /**
     * @param ObjectProphecy<Process> $process
     * @param ObjectProphecy<OutputInterface> $output
     *
     * @return ObjectProphecy<ProcessEnvironmentConfiguratorInterface>
     */
    private function createConfiguratorMock(ObjectProphecy $process, ObjectProphecy $output): ObjectProphecy
    {
        $configurator = $this->prophesize(ProcessEnvironmentConfiguratorInterface::class);
        $configurator->configure($process->reveal(), $output->reveal())
            ->shouldBeCalledOnce();

        return $configurator;
    }
}
