<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\Distribution;

use Composer\Autoload\ClassLoader;
use DOMDocument;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function random_bytes;
use function Safe\file_get_contents;
use function Safe\json_decode;
use function Safe\realpath;

/**
 * Notification delivery MUST NOT duplicate PHPUnit shutdown output or reports.
 *
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[CoversNothing]
final class PhpUnitNotificationLifecycleTest extends TestCase
{
    /**
     * Provides successful and failing notification transports.
     *
     * @return iterable<string, array{bool}>
     */
    public static function notificationOutcomes(): iterable
    {
        yield 'notification succeeds' => [false];
        yield 'notification throws' => [true];
    }

    /**
     * Verifies that notification delivery preserves the PHPUnit reporting lifecycle.
     *
     * @param bool $throws whether notification transport throws after recording its invocation
     *
     * @return void
     */
    #[Test]
    #[DataProvider('notificationOutcomes')]
    public function notificationWillLeaveOneAgentReportAndOneValidJunitDocument(bool $throws): void
    {
        $filesystem = new Filesystem();
        $workspace = sys_get_temp_dir() . '/phpunit-notification-' . bin2hex(random_bytes(8));
        $packageRoot = realpath(\dirname(__DIR__, 2));
        $classLoaderFile = (new ReflectionClass(ClassLoader::class))->getFileName();
        self::assertIsString($classLoaderFile);
        $vendor = \dirname($classLoaderFile, 2);

        try {
            $filesystem->mkdir($workspace);
            $filesystem->dumpFile($workspace . '/bootstrap.php', <<<'PHP'
                <?php
                declare(strict_types=1);

                $vendor = getenv('FIXTURE_VENDOR');
                $packageRoot = getenv('FIXTURE_PACKAGE_ROOT');
                require_once $vendor . '/autoload.php';
                spl_autoload_register(static function (string $class) use ($packageRoot): void {
                    $prefix = 'FastForward\\DevTools\\';
                    if (str_starts_with($class, $prefix)) {
                        $path = $packageRoot . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                        if (!is_file($path)) {
                            throw new RuntimeException('Current source is missing: ' . $class);
                        }
                        require $path;
                    }
                }, true, true);
                require __DIR__ . '/LifecycleExtension.php';
                PHP);
            $filesystem->dumpFile($workspace . '/LifecycleExtension.php', <<<'PHP'
                <?php
                declare(strict_types=1);

                use FastForward\DevTools\PhpUnit\Event\EventTracer;
                use FastForward\DevTools\PhpUnit\Event\TestSuite\JoliNotifExecutionFinishedSubscriber;
                use Joli\JoliNotif\Notification;
                use Joli\JoliNotif\NotifierInterface;
                use PHPUnit\Runner\Extension\Extension;
                use PHPUnit\Runner\Extension\Facade;
                use PHPUnit\Runner\Extension\ParameterCollection;
                use PHPUnit\TextUI\Configuration\Configuration;

                final class LifecycleExtension implements Extension
                {
                    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
                    {
                        $source = (new ReflectionClass(JoliNotifExecutionFinishedSubscriber::class))->getFileName();
                        if ($source !== getenv('FIXTURE_PACKAGE_ROOT') . '/src/PhpUnit/Event/TestSuite/JoliNotifExecutionFinishedSubscriber.php') {
                            throw new RuntimeException('The notification subscriber was not loaded from the current checkout.');
                        }
                        $tracer = new EventTracer();
                        $notifier = new class implements NotifierInterface {
                            public function send(Notification $notification): bool
                            {
                                file_put_contents(__DIR__ . '/notifications.log', "sent\n", FILE_APPEND | LOCK_EX);
                                if ('1' === getenv('FIXTURE_NOTIFIER_THROWS')) {
                                    throw new RuntimeException('Synthetic notification transport failure.');
                                }
                                return true;
                            }
                        };
                        $facade->registerTracer($tracer);
                        $facade->registerSubscriber(new JoliNotifExecutionFinishedSubscriber($tracer, $notifier));
                    }
                }
                PHP);
            $filesystem->dumpFile($workspace . '/ConsumerTest.php', <<<'PHP'
                <?php
                declare(strict_types=1);

                use PHPUnit\Framework\Attributes\CoversNothing;
                use PHPUnit\Framework\Attributes\Test;
                use PHPUnit\Framework\TestCase;

                #[CoversNothing]
                final class ConsumerTest extends TestCase
                {
                    #[Test]
                    public function consumerWillPass(): void
                    {
                        self::assertTrue(true);
                    }
                }
                PHP);
            $filesystem->dumpFile($workspace . '/phpunit.xml', <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <phpunit bootstrap="bootstrap.php" requireCoverageMetadata="true" failOnWarning="true">
                    <extensions>
                        <bootstrap class="Ergebnis\PHPUnit\AgentReporter\Extension"/>
                        <bootstrap class="LifecycleExtension"/>
                    </extensions>
                    <logging>
                        <junit outputFile="junit.xml"/>
                    </logging>
                </phpunit>
                XML);
            $process = new Process([
                \PHP_BINARY,
                $vendor . '/phpunit/phpunit/phpunit',
                '--configuration',
                $workspace . '/phpunit.xml',
                '--no-progress',
                '--colors=never',
                $workspace . '/ConsumerTest.php',
            ], $workspace, [
                'FIXTURE_VENDOR' => $vendor,
                'FIXTURE_PACKAGE_ROOT' => $packageRoot,
                'FIXTURE_NOTIFIER_THROWS' => $throws ? '1' : '0',
                'AI_AGENT' => 'fast-forward/dev-tools',
                'INFECTION' => false,
                'XDEBUG_MODE' => 'off',
            ]);
            $process->setTimeout(15);
            $process->run();

            if ($throws) {
                self::assertNotSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
            } else {
                self::assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
            }
            self::assertFileExists($workspace . '/junit.xml');
            self::assertFileExists($workspace . '/notifications.log');
            $junit = file_get_contents($workspace . '/junit.xml');
            $output = $process->getOutput();
            $notifications = file_get_contents($workspace . '/notifications.log');
            $diagnostic = \sprintf(
                'Agent result keys: %d; XML declarations: %d; notifications: %d. Output: %s',
                substr_count($output, '"result"'),
                substr_count($junit, '<?xml'),
                substr_count($notifications, "sent\n"),
                $output,
            );

            self::assertSame(1, substr_count($output, '"result"'), $diagnostic);
            $report = json_decode($output, true, flags: \JSON_THROW_ON_ERROR);
            self::assertSame($throws ? 'failure' : 'success', $report['result']);
            self::assertSame(1, $report['summary']['tests']);
            self::assertSame(1, substr_count($junit, '<?xml'), $diagnostic);
            $document = new DOMDocument();
            self::assertTrue($document->loadXML($junit));
            self::assertSame('testsuites', $document->documentElement->tagName);
            self::assertSame(1, $document->getElementsByTagName('testcase')->length);
            self::assertSame("sent\n", $notifications);
        } finally {
            $filesystem->remove($workspace);
        }
    }
}
