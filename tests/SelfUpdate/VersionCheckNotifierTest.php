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

namespace FastForward\DevTools\Tests\SelfUpdate;

use Prophecy\Argument;
use FastForward\DevTools\Environment\EnvironmentInterface;
use FastForward\DevTools\SelfUpdate\VersionCheckerInterface;
use FastForward\DevTools\SelfUpdate\VersionCheckNotifier;
use FastForward\DevTools\SelfUpdate\VersionCheckResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(VersionCheckNotifier::class)]
#[UsesClass(VersionCheckResult::class)]
final class VersionCheckNotifierTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<VersionCheckerInterface>
     */
    private ObjectProphecy $versionChecker;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    /**
     * @var ObjectProphecy<EnvironmentInterface>
     */
    private ObjectProphecy $environment;

    private VersionCheckNotifier $notifier;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->versionChecker = $this->prophesize(VersionCheckerInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->notifier = new VersionCheckNotifier($this->versionChecker->reveal(), $this->environment->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function notifyWillWriteWarningWhenDevToolsIsOutdated(): void
    {
        $this->willRunVersionCheck();
        $this->versionChecker->check()
            ->willReturn(new VersionCheckResult('1.2.0', 'v1.3.0'));
        $this->output->writeln(
            '<comment>DevTools v1.3.0 is available; current version is 1.2.0. '
            . 'Run "dev-tools self-update" to update.</comment>',
        )->shouldBeCalledOnce();

        $this->notifier->notify($this->output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function notifyWillStaySilentWhenCheckFails(): void
    {
        $this->willRunVersionCheck();
        $this->versionChecker->check()
            ->willThrow(new RuntimeException('network unavailable'));
        $this->output->writeln(Argument::any())
            ->shouldNotBeCalled();

        $this->notifier->notify($this->output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function notifyWillStaySilentInCi(): void
    {
        $this->environment->get('FAST_FORWARD_SKIP_VERSION_CHECK', '')
            ->willReturn('');
        $this->environment->get('GITHUB_ACTIONS', '')
            ->willReturn('');
        $this->environment->get('CI', '')
            ->willReturn('true');
        $this->versionChecker->check()
            ->shouldNotBeCalled();
        $this->output->writeln(Argument::any())
            ->shouldNotBeCalled();

        $this->notifier->notify($this->output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function notifyWillStaySilentWhenVersionCheckIsDisabled(): void
    {
        $this->environment->get('FAST_FORWARD_SKIP_VERSION_CHECK', '')
            ->willReturn('1');
        $this->versionChecker->check()
            ->shouldNotBeCalled();
        $this->output->writeln(Argument::any())
            ->shouldNotBeCalled();

        $this->notifier->notify($this->output->reveal());
    }

    /**
     * @return void
     */
    private function willRunVersionCheck(): void
    {
        $this->environment->get('FAST_FORWARD_SKIP_VERSION_CHECK', '')
            ->willReturn('');
        $this->environment->get('GITHUB_ACTIONS', '')
            ->willReturn('');
        $this->environment->get('CI', '')
            ->willReturn('');
    }
}
