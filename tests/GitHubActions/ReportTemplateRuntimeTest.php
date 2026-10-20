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

namespace FastForward\DevTools\Tests\GitHubActions;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\json_decode;
use function Safe\mkdir;
use function Safe\realpath;

#[CoversNothing]
final class ReportTemplateRuntimeTest extends TestCase
{
    private const string PROJECT_ROOT = __DIR__ . '/../..';

    private string $workspace;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir() . '/report-template-' . bin2hex(random_bytes(4));
        mkdir($this->workspace);
        $this->workspace = realpath($this->workspace);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        new Filesystem()
            ->remove($this->workspace);
    }

    /**
     * @return void
     */
    #[Test]
    public function reportTemplateWillFallBackToTheIsolatedRuntimeDependency(): void
    {
        $process = $this->resolveTemplate();
        $process->mustRun();

        $metadata = json_decode($process->getOutput(), true);

        self::assertSame(
            realpath(self::PROJECT_ROOT . '/vendor/fast-forward/phpdoc-bootstrap-template'),
            $metadata['DEV_TOOLS_REPORTS_TEMPLATE'],
        );
        self::assertStringContainsString(
            'DEV_TOOLS_REPORTS_TEMPLATE=' . $metadata['DEV_TOOLS_REPORTS_TEMPLATE'],
            file_get_contents($this->workspace . '/env'),
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function reportTemplateWillUseConsumerPathAndMatchingConsumerVersionTogether(): void
    {
        $template = $this->createConsumerTemplate('v2.4.0');
        $process = $this->resolveTemplate();
        $process->mustRun();

        $metadata = json_decode($process->getOutput(), true);

        self::assertSame($template, $metadata['DEV_TOOLS_REPORTS_TEMPLATE']);
        self::assertSame('v2.4.0', $metadata['DEV_TOOLS_REPORTS_TEMPLATE_VERSION']);
        self::assertSame('consumer-reference', $metadata['DEV_TOOLS_REPORTS_TEMPLATE_REFERENCE']);
    }

    /**
     * @return void
     */
    #[Test]
    public function reportTemplateWillRejectAnOldSelectedConsumerTemplate(): void
    {
        $this->createConsumerTemplate('v2.0.0');
        $process = $this->resolveTemplate();
        $process->run();

        self::assertFalse($process->isSuccessful());
        self::assertStringContainsString(
            'selected documentation template must be installed at version 2.1.0',
            $process->getErrorOutput()
        );
        self::assertFileDoesNotExist($this->workspace . '/env');
    }

    /**
     * @param string $version
     *
     * @return string
     */
    private function createConsumerTemplate(string $version): string
    {
        $template = $this->workspace . '/vendor/fast-forward/phpdoc-bootstrap-template';
        mkdir($template, 0o777, true);
        mkdir($this->workspace . '/vendor/composer');
        file_put_contents(
            $this->workspace . '/vendor/autoload.php',
            '<?php $loader = new \\Composer\\Autoload\\ClassLoader(__DIR__); $loader->register(); return $loader;',
        );
        file_put_contents(
            $this->workspace . '/vendor/composer/installed.php',
            '<?php return ' . var_export([
                'root' => [],
                'versions' => [
                    'fast-forward/phpdoc-bootstrap-template' => [
                        'pretty_version' => $version,
                        'reference' => 'consumer-reference',
                        'install_path' => $template,
                    ],
                ],
            ], true) . ';',
        );

        return $template;
    }

    /**
     * @return Process
     */
    private function resolveTemplate(): Process
    {
        return new Process([
            \PHP_BINARY,
            self::PROJECT_ROOT . '/.github/actions/php/setup-composer/resolve-report-template.php',
        ], $this->workspace, [
            'DEV_TOOLS_AUTOLOAD' => realpath(self::PROJECT_ROOT . '/vendor/autoload.php'),
            'GITHUB_ENV' => $this->workspace . '/env',
        ]);
    }
}
