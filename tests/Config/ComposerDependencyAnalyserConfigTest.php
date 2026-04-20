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

use FastForward\DevTools\Composer\Json\ComposerJson;
use FastForward\DevTools\Config\ComposerDependencyAnalyserConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

use function Safe\json_encode;
use function Safe\chdir;
use function Safe\file_put_contents;
use function Safe\getcwd;
use function Safe\mkdir;
use function Safe\rmdir;
use function Safe\unlink;

#[CoversClass(ComposerDependencyAnalyserConfig::class)]
#[UsesClass(ComposerJson::class)]
final class ComposerDependencyAnalyserConfigTest extends TestCase
{
    private string $workingDirectory;

    /**
     * @var array<int, string>
     */
    private array $temporaryProjectRoots = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->workingDirectory = getcwd();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        chdir($this->workingDirectory);

        foreach ($this->temporaryProjectRoots as $temporaryProjectRoot) {
            if (file_exists($temporaryProjectRoot . '/composer.json')) {
                unlink($temporaryProjectRoot . '/composer.json');
            }

            if (file_exists($temporaryProjectRoot . '/vendor/composer/installed.json')) {
                unlink($temporaryProjectRoot . '/vendor/composer/installed.json');
            }

            if (is_dir($temporaryProjectRoot . '/vendor/composer')) {
                rmdir($temporaryProjectRoot . '/vendor/composer');
            }

            if (is_dir($temporaryProjectRoot . '/vendor')) {
                rmdir($temporaryProjectRoot . '/vendor');
            }

            if (is_dir($temporaryProjectRoot)) {
                rmdir($temporaryProjectRoot);
            }
        }
    }

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
    public function configureWillApplySharedIgnoresAndInvokeCustomizationCallback(): void
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
                ->shouldIgnoreError(ErrorType::UNUSED_DEPENDENCY, null, 'vendor/custom-package')
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWillNotApplyPackagedRepositoryIgnoresForConsumerProjects(): void
    {
        $consumerRoot = $this->createTemporaryProjectRoot('vendor/consumer-project');

        chdir($consumerRoot);

        $configuration = ComposerDependencyAnalyserConfig::configure();

        self::assertFalse(
            $configuration->getIgnoreList()
                ->shouldIgnoreError(ErrorType::UNUSED_DEPENDENCY, null, 'shipmonk/composer-dependency-analyser'),
        );
        self::assertFalse(
            $configuration->getIgnoreList()
                ->shouldIgnoreError(ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV, null, 'phpspec/prophecy'),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWillApplyPackagedRepositorySpecificIgnoresForDevTools(): void
    {
        $packageRoot = $this->createTemporaryProjectRoot('fast-forward/dev-tools');

        chdir($packageRoot);

        $configuration = ComposerDependencyAnalyserConfig::configure();

        self::assertTrue(
            $configuration->getIgnoreList()
                ->shouldIgnoreError(ErrorType::UNUSED_DEPENDENCY, null, 'rector/jack'),
        );
        self::assertTrue(
            $configuration->getIgnoreList()
                ->shouldIgnoreError(ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV, null, 'phpspec/prophecy'),
        );
        self::assertTrue(
            $configuration->getIgnoreList()
                ->shouldIgnoreError(ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV, null, 'symfony/var-exporter'),
        );
    }

    /**
     * @param string $packageName
     *
     * @return string
     */
    private function createTemporaryProjectRoot(string $packageName): string
    {
        $path = sys_get_temp_dir() . '/dev-tools-dependency-config-' . md5($packageName . microtime(true));

        mkdir($path);
        mkdir($path . '/vendor');
        mkdir($path . '/vendor/composer');
        file_put_contents($path . '/composer.json', json_encode([
            'name' => $packageName,
        ], \JSON_THROW_ON_ERROR));
        file_put_contents($path . '/vendor/composer/installed.json', json_encode([
            'packages' => [],
        ], \JSON_THROW_ON_ERROR));
        $this->temporaryProjectRoots[] = $path;

        return $path;
    }
}
