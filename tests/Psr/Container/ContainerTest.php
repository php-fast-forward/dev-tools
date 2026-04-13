<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\Psr\Container;

use FastForward\DevTools\Psr\Container\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionProperty;

use function Safe\file_put_contents;
use function Safe\tempnam;
use function Safe\unlink;

#[CoversClass(Container::class)]
final class ContainerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->setStaticContainer(null);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->setStaticContainer(null);

        foreach ($this->temporaryFiles as $temporaryFile) {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function getWillDelegateToTheBootedContainer(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('service')
            ->willReturn('value')
            ->shouldBeCalledOnce();

        $this->setStaticContainer($container->reveal());

        self::assertSame('value', Container::get('service'));
    }

    /**
     * @return void
     */
    #[Test]
    public function hasWillDelegateToTheBootedContainer(): void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('service')
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $this->setStaticContainer($container->reveal());

        self::assertTrue(Container::has('service'));
    }

    /**
     * @return void
     */
    #[Test]
    public function bootWillCreateAndCacheAConfiguredPhpDiContainer(): void
    {
        $configurationFile = tempnam(sys_get_temp_dir(), 'container-config-');
        $this->temporaryFiles[] = $configurationFile;

        file_put_contents($configurationFile, <<<'PHP'
            <?php

            return [
                'service' => 'value',
            ];
            PHP);

        /** @var ContainerInterface $container */
        $container = $this->invokePrivateBoot($configurationFile);
        /** @var ContainerInterface $cachedContainer */
        $cachedContainer = $this->invokePrivateBoot($configurationFile);

        self::assertTrue($container->has('service'));
        self::assertSame('value', $container->get('service'));
        self::assertSame($container, $cachedContainer);
    }

    /**
     * @param string $configurationFile
     *
     * @return object
     */
    private function invokePrivateBoot(string $configurationFile): object
    {
        $reflectionMethod = new ReflectionMethod(Container::class, 'boot');

        return $reflectionMethod->invoke(null, $configurationFile);
    }

    /**
     * @param ContainerInterface|null $container
     *
     * @return void
     */
    private function setStaticContainer(?ContainerInterface $container): void
    {
        $property = new ReflectionProperty(Container::class, 'container');
        $property->setValue(null, $container);
    }
}
