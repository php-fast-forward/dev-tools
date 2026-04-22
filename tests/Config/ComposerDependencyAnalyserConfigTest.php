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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

#[CoversClass(ComposerDependencyAnalyserConfig::class)]
final class ComposerDependencyAnalyserConfigTest extends TestCase
{
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
    public function isInstalledAsDependencyWillDetectVendorPackagePaths(): void
    {
        self::assertFalse($this->invokeDetector('isInstalledAsDependency', '/workspaces/dev-tools/src/Config'));
        self::assertTrue($this->invokeDetector(
            'isInstalledAsDependency',
            '/workspaces/project/vendor/fast-forward/dev-tools/src/Config',
        ));
    }

    /**
     * @return void
     */
    #[Test]
    public function isDevToolsRepositoryWillDetectRepositoryPaths(): void
    {
        self::assertTrue($this->invokeDetector('isDevToolsRepository', '/workspaces/dev-tools/src/Config'));
        self::assertFalse($this->invokeDetector(
            'isDevToolsRepository',
            '/workspaces/project/vendor/fast-forward/dev-tools/src/Config',
        ));
    }

    /**
     * @param string $methodName
     * @param string $configDirectory
     *
     * @return bool
     */
    private function invokeDetector(string $methodName, string $configDirectory): bool
    {
        $reflectionMethod = new ReflectionMethod(ComposerDependencyAnalyserConfig::class, $methodName);

        return $reflectionMethod->invoke(null, $configDirectory);
    }
}
