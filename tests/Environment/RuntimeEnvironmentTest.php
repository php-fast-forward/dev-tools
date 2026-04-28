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

namespace FastForward\DevTools\Tests\Environment;

use FastForward\DevTools\Environment\EnvironmentInterface;
use FastForward\DevTools\Environment\RuntimeEnvironment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(RuntimeEnvironment::class)]
final class RuntimeEnvironmentTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<EnvironmentInterface>
     */
    private ObjectProphecy $environment;

    private RuntimeEnvironment $runtimeEnvironment;

    /**
     * @return iterable<string, array{value: ?string, enabled: bool}>
     */
    public static function truthyEnvironmentFlagsProvider(): iterable
    {
        yield 'missing' => [
            'value' => null,
            'enabled' => false,
        ];
        yield 'empty' => [
            'value' => '',
            'enabled' => false,
        ];
        yield 'zero' => [
            'value' => '0',
            'enabled' => false,
        ];
        yield 'false' => [
            'value' => 'false',
            'enabled' => false,
        ];
        yield 'one' => [
            'value' => '1',
            'enabled' => true,
        ];
        yield 'true' => [
            'value' => 'true',
            'enabled' => true,
        ];
        yield 'yes' => [
            'value' => 'yes',
            'enabled' => true,
        ];
        yield 'on' => [
            'value' => 'on',
            'enabled' => true,
        ];
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->runtimeEnvironment = new RuntimeEnvironment($this->environment->reveal());
    }

    /**
     * @param string|null $value
     * @param bool $enabled
     *
     * @return void
     */
    #[DataProvider('truthyEnvironmentFlagsProvider')]
    #[Test]
    public function isEnabledWillReturnWhetherEnvironmentFlagIsTruthy(?string $value, bool $enabled): void
    {
        $this->environment->get('FEATURE_FLAG', '')
            ->willReturn($value);

        self::assertSame($enabled, $this->runtimeEnvironment->isEnabled('FEATURE_FLAG'));
    }

    /**
     * @return void
     */
    #[Test]
    public function isGithubActionsWillReturnWhetherGithubActionsFlagIsEnabled(): void
    {
        $this->environment->get('GITHUB_ACTIONS', '')
            ->willReturn('true');

        self::assertTrue($this->runtimeEnvironment->isGithubActions());
    }

    /**
     * @return void
     */
    #[Test]
    public function isCiWillReturnTrueWhenGithubActionsIsEnabled(): void
    {
        $this->environment->get('GITHUB_ACTIONS', '')
            ->willReturn('true');

        self::assertTrue($this->runtimeEnvironment->isCi());
    }

    /**
     * @return void
     */
    #[Test]
    public function isCiWillReturnTrueWhenGenericCiIsEnabled(): void
    {
        $this->environment->get('GITHUB_ACTIONS', '')
            ->willReturn('');
        $this->environment->get('CI', '')
            ->willReturn('1');

        self::assertTrue($this->runtimeEnvironment->isCi());
    }

    /**
     * @return void
     */
    #[Test]
    public function isComposerTestRunWillReturnWhetherComposerTestsFlagIsEnabled(): void
    {
        $this->environment->get('COMPOSER_TESTS_ARE_RUNNING', '')
            ->willReturn('true');

        self::assertTrue($this->runtimeEnvironment->isComposerTestRun());
    }
}
