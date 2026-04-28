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

use FastForward\DevTools\Environment\EnvironmentInterface;
use FastForward\DevTools\SelfUpdate\ComposerSelfUpdateScopeResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(ComposerSelfUpdateScopeResolver::class)]
final class ComposerSelfUpdateScopeResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<EnvironmentInterface>
     */
    private ObjectProphecy $environment;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->environment = $this->prophesize(EnvironmentInterface::class);
    }

    /**
     * @return void
     */
    #[Test]
    public function isGlobalInstallationWillReturnTrueWhenPackageLivesUnderComposerHome(): void
    {
        $this->environment->get('COMPOSER_HOME')
            ->willReturn('/home/felipe/.composer');
        $this->environment->get('HOME')
            ->willReturn(null);
        $this->environment->get('APPDATA')
            ->willReturn(null);
        $resolver = new ComposerSelfUpdateScopeResolver(
            $this->environment->reveal(),
            '/home/felipe/.composer/vendor/fast-forward/dev-tools',
        );

        self::assertTrue($resolver->isGlobalInstallation());
    }

    /**
     * @return void
     */
    #[Test]
    public function isGlobalInstallationWillReturnTrueWhenPackageLivesUnderDefaultComposerHome(): void
    {
        $this->environment->get('COMPOSER_HOME')
            ->willReturn(null);
        $this->environment->get('HOME')
            ->willReturn('/Users/felipe');
        $this->environment->get('APPDATA')
            ->willReturn(null);
        $resolver = new ComposerSelfUpdateScopeResolver(
            $this->environment->reveal(),
            '/Users/felipe/Library/Application Support/Composer/vendor/fast-forward/dev-tools',
        );

        self::assertTrue($resolver->isGlobalInstallation());
    }

    /**
     * @return void
     */
    #[Test]
    public function isGlobalInstallationWillReturnFalseWhenPackageLivesUnderProjectVendor(): void
    {
        $this->environment->get('COMPOSER_HOME')
            ->willReturn('/home/felipe/.composer');
        $this->environment->get('HOME')
            ->willReturn('/home/felipe');
        $this->environment->get('APPDATA')
            ->willReturn(null);
        $resolver = new ComposerSelfUpdateScopeResolver(
            $this->environment->reveal(),
            '/home/felipe/project/vendor/fast-forward/dev-tools',
        );

        self::assertFalse($resolver->isGlobalInstallation());
    }
}
