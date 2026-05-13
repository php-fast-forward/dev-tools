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

namespace FastForward\DevTools\Tests\Config;

use FastForward\DevTools\Config\ComposerDependencyAnalyserConfig;
use FastForward\DevTools\Environment\Environment;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\Environment\EnvironmentInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

#[CoversClass(ComposerDependencyAnalyserConfig::class)]
#[UsesClass(Environment::class)]
#[UsesClass(DevToolsPathResolver::class)]
final class ComposerDependencyAnalyserConfigTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return void
     */
    #[Test]
    public function configureWillReturnConfiguration(): void
    {
        $configuration = ComposerDependencyAnalyserConfig::configure();

        self::assertInstanceOf(Configuration::class, $configuration);
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWillIgnoreShadowDependenciesByDefault(): void
    {
        $configuration = ComposerDependencyAnalyserConfig::configure(environment: $this->createEnvironment());

        self::assertTrue(
            $configuration->getIgnoreList()
                ->shouldIgnoreError(ErrorType::SHADOW_DEPENDENCY, null, 'vendor/shadow-package')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWillKeepShadowDependenciesVisibleWhenRequested(): void
    {
        $configuration = ComposerDependencyAnalyserConfig::configure(environment: $this->createEnvironment('1'));

        self::assertFalse(
            $configuration->getIgnoreList()
                ->shouldIgnoreError(ErrorType::SHADOW_DEPENDENCY, null, 'vendor/shadow-package')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWillApplyRepositoryIgnoresAndInvokeCustomizationCallback(): void
    {
        $customizeWasCalled = false;

        $configuration = ComposerDependencyAnalyserConfig::configure(
            static function (Configuration $configuration) use (&$customizeWasCalled): void {
                $customizeWasCalled = true;
                $configuration->ignoreErrorsOnPackage('vendor/custom-package', [ErrorType::UNUSED_DEPENDENCY]);
            },
        );

        self::assertTrue($customizeWasCalled);
        self::assertTrue(
            $configuration->getIgnoreList()
                ->shouldIgnoreError(ErrorType::SHADOW_DEPENDENCY, null, 'ext-pcntl')
        );
        self::assertTrue(
            $configuration->getIgnoreList()
                ->shouldIgnoreError(ErrorType::UNUSED_DEPENDENCY, null, 'rector/jack')
        );
        self::assertTrue(
            $configuration->getIgnoreList()
                ->shouldIgnoreError(ErrorType::UNUSED_DEPENDENCY, null, 'ergebnis/phpunit-agent-reporter')
        );
        self::assertTrue(
            $configuration->getIgnoreList()
                ->shouldIgnoreError(ErrorType::UNUSED_DEPENDENCY, null, 'vendor/custom-package')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function itWillExposeReusablePackagedDependencyDefaults(): void
    {
        self::assertContains(
            'ergebnis/phpunit-agent-reporter',
            ComposerDependencyAnalyserConfig::DEFAULT_PACKAGED_UNUSED_DEPENDENCIES,
        );
        self::assertContains(
            'rector/jack',
            ComposerDependencyAnalyserConfig::DEFAULT_PACKAGED_UNUSED_DEPENDENCIES,
        );
        self::assertContains(
            'phpspec/prophecy',
            ComposerDependencyAnalyserConfig::DEFAULT_PACKAGED_PROD_ONLY_IN_DEV_DEPENDENCIES,
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function applyPackagedRepositoryIgnoresWillReturnTheSameConfigurationInstance(): void
    {
        $configuration = new Configuration();

        self::assertSame(
            $configuration,
            ComposerDependencyAnalyserConfig::applyPackagedRepositoryIgnores($configuration)
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function applyIgnoresShadowDependenciesWillReturnTheSameConfigurationInstance(): void
    {
        $configuration = new Configuration();

        self::assertSame(
            $configuration,
            ComposerDependencyAnalyserConfig::applyIgnoresShadowDependencies($configuration)
        );
    }

    /**
     * @param string|null $value
     *
     * @return EnvironmentInterface
     */
    private function createEnvironment(?string $value = null): EnvironmentInterface
    {
        $environment = $this->prophesize(EnvironmentInterface::class);

        $environment->get(ComposerDependencyAnalyserConfig::ENV_SHOW_SHADOW_DEPENDENCIES)
            ->willReturn($value);

        return $environment->reveal();
    }
}
