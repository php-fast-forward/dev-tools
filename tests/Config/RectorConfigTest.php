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

use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use FastForward\DevTools\Rector\AddMissingMethodPhpDocRector;
use ReflectionProperty;
use FastForward\DevTools\Config\RectorConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rector\Configuration\Option;
use Rector\Configuration\Parameter\SimpleParameterProvider;
use Rector\Config\RectorConfig as RectorConfigInterface;

use function Safe\getcwd;

#[CoversClass(RectorConfig::class)]
final class RectorConfigTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        self::resetSimpleParameterProvider();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        self::resetSimpleParameterProvider();
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWillReturnCallable(): void
    {
        $result = RectorConfig::configure();

        self::assertIsCallable($result);
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWithCustomCallbackWillReturnCallable(): void
    {
        $customCallback = static function ($config): void {
            \assert($config instanceof RectorConfigInterface);
        };

        $result = RectorConfig::configure($customCallback);

        self::assertIsCallable($result);
    }

    /**
     * @return void
     */
    #[Test]
    public function configureWillApplyTheDefaultRectorConfigurationAndCustomizationCallback(): void
    {
        $customizeWasCalled = false;
        $callback = RectorConfig::configure(static function (RectorConfigInterface $rectorConfig) use (
            &$customizeWasCalled
        ): void {
            $customizeWasCalled = true;
            $rectorConfig->parallel(42);
        });

        $callback(new RectorConfigInterface());

        self::assertTrue($customizeWasCalled);
        self::assertSame([getcwd()], SimpleParameterProvider::provideArrayParameter(Option::PATHS));
        self::assertSame(
            [
                getcwd() . '/.dev-tools',
                getcwd() . '/resources',
                getcwd() . '/vendor',
                getcwd() . '/tmp',
                RemoveUselessReturnTagRector::class,
                RemoveUselessParamTagRector::class,
            ],
            SimpleParameterProvider::provideArrayParameter(Option::SKIP),
        );
        self::assertSame(
            getcwd() . '/tmp/cache/rector',
            SimpleParameterProvider::provideStringParameter(Option::CACHE_DIR)
        );
        self::assertSame(['php'], SimpleParameterProvider::provideArrayParameter(Option::FILE_EXTENSIONS));
        self::assertTrue(SimpleParameterProvider::provideBoolParameter(Option::AUTO_IMPORT_NAMES));
        self::assertTrue(SimpleParameterProvider::provideBoolParameter(Option::AUTO_IMPORT_DOC_BLOCK_NAMES));
        self::assertTrue(SimpleParameterProvider::provideBoolParameter(Option::REMOVE_UNUSED_IMPORTS));
        self::assertTrue(SimpleParameterProvider::provideBoolParameter(Option::PARALLEL));
        self::assertSame(42, SimpleParameterProvider::provideIntParameter(Option::PARALLEL_JOB_TIMEOUT_IN_SECONDS));
        self::assertContains(
            AddMissingMethodPhpDocRector::class,
            SimpleParameterProvider::provideArrayParameter(Option::REGISTERED_RECTOR_RULES),
        );
        self::assertNotEmpty(SimpleParameterProvider::provideArrayParameter(Option::REGISTERED_RECTOR_SETS));
    }

    /**
     * @return void
     */
    public static function resetSimpleParameterProvider(): void
    {
        $reflectionProperty = new ReflectionProperty(SimpleParameterProvider::class, 'parameters');
        $reflectionProperty->setValue(null, []);
    }
}
