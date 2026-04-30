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

namespace FastForward\DevTools\Tests\GrumPhp;

use FastForward\DevTools\GrumPhp\ManagedConfigPathSynchronizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ManagedConfigPathSynchronizer::class)]
final class ManagedConfigPathSynchronizerTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function synchronizeWillRemoveManagedVendorConfigDefaultPaths(): void
    {
        $synchronizer = new ManagedConfigPathSynchronizer();

        self::assertSame(
            [],
            $synchronizer->synchronize(
                [
                    'grumphp' => [
                        'config-default-path' => 'vendor/fast-forward/dev-tools/grumphp.yml',
                    ],
                ],
                '/app',
                '/Users/example/.composer/vendor/fast-forward/dev-tools/grumphp.yml'
            ),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function synchronizeWillRemoveCurrentManagedRelativePathsOutsideTheProject(): void
    {
        $synchronizer = new ManagedConfigPathSynchronizer();

        self::assertSame(
            [],
            $synchronizer->synchronize(
                [
                    'grumphp' => [
                        'config-default-path' => '../global/dev-tools/grumphp.yml',
                    ],
                ],
                '/app',
                '/global/dev-tools/grumphp.yml'
            ),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function synchronizeWillPreserveConsumerOwnedConfigDefaultPaths(): void
    {
        $synchronizer = new ManagedConfigPathSynchronizer();

        self::assertSame(
            [
                'grumphp' => [
                    'config-default-path' => 'tools/grumphp.yml',
                ],
            ],
            $synchronizer->synchronize(
                [
                    'grumphp' => [
                        'config-default-path' => 'tools/grumphp.yml',
                    ],
                ],
                '/app',
                '/Users/example/.composer/vendor/fast-forward/dev-tools/grumphp.yml'
            ),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function synchronizeWillPreserveNonArrayLegacyConsumerConfiguration(): void
    {
        $synchronizer = new ManagedConfigPathSynchronizer();

        self::assertSame(
            [
                'grumphp' => 'consumer-managed',
            ],
            $synchronizer->synchronize(
                [
                    'grumphp' => 'consumer-managed',
                ],
                '/app',
                '/Users/example/.composer/vendor/fast-forward/dev-tools/grumphp.yml'
            ),
        );
    }
}
