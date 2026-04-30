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

namespace FastForward\DevTools\Tests\PhpUnit\Bootstrap;

use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Path\DevToolsPathResolver;
use FastForward\DevTools\PhpUnit\Bootstrap\BootstrapShimGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

#[CoversClass(BootstrapShimGenerator::class)]
#[UsesClass(DevToolsPathResolver::class)]
final class BootstrapShimGeneratorTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $filesystem;

    private BootstrapShimGenerator $generator;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->generator = new BootstrapShimGenerator($this->filesystem->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function generateWillWriteBootstrapShimWithProjectAndDevToolsAutoloaders(): void
    {
        $projectBootstrap = '/repo/vendor/autoload.php';
        $cacheDirectory = '/repo/.dev-tools/cache/phpunit';
        $generatedBootstrapPath = '/repo/.dev-tools/cache/phpunit/bootstrap.php';
        $devToolsAutoload = DevToolsPathResolver::getPackagePath('vendor/autoload.php');
        $exportedProjectBootstrap = var_export($projectBootstrap, true);
        $exportedDevToolsAutoload = var_export($devToolsAutoload, true);
        $expectedContent = <<<PHP
            <?php

            declare(strict_types=1);

            require_once {$exportedProjectBootstrap};

            if (file_exists({$exportedDevToolsAutoload})) {
                require_once {$exportedDevToolsAutoload};
            }
            PHP;

        $this->filesystem->dumpFile($generatedBootstrapPath, $expectedContent)
            ->shouldBeCalledOnce();

        self::assertSame($generatedBootstrapPath, $this->generator->generate($projectBootstrap, $cacheDirectory));
    }
}
