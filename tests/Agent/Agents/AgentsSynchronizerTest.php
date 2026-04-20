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

namespace FastForward\DevTools\Tests\Agent\Agents;

use FastForward\DevTools\Agent\Agents\AgentsSynchronizer;
use FastForward\DevTools\Agent\Sync\PackagedDirectorySynchronizer;
use FastForward\DevTools\Agent\Sync\SynchronizeResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

#[CoversClass(AgentsSynchronizer::class)]
#[UsesClass(PackagedDirectorySynchronizer::class)]
#[UsesClass(SynchronizeResult::class)]
final class AgentsSynchronizerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PackagedDirectorySynchronizer>
     */
    private ObjectProphecy $synchronizer;

    private AgentsSynchronizer $agentsSynchronizer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->synchronizer = $this->prophesize(PackagedDirectorySynchronizer::class);
        $this->agentsSynchronizer = new AgentsSynchronizer($this->synchronizer->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function setLoggerWillDelegateToInnerSynchronizer(): void
    {
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $this->synchronizer->setLogger($logger)
            ->shouldBeCalledOnce();

        $this->agentsSynchronizer->setLogger($logger);
    }

    /**
     * @return void
     */
    #[Test]
    public function synchronizeWillDelegateWithAgentsDirectoryLabel(): void
    {
        $result = new SynchronizeResult();

        $this->synchronizer->synchronize('/consumer/.agents/agents', '/package/.agents/agents', '.agents/agents')
            ->willReturn($result)
            ->shouldBeCalledOnce();

        self::assertSame(
            $result,
            $this->agentsSynchronizer->synchronize('/consumer/.agents/agents', '/package/.agents/agents')
        );
    }
}
