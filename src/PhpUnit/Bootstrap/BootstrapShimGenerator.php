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

namespace FastForward\DevTools\PhpUnit\Bootstrap;

use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Path\DevToolsPathResolver;
use Symfony\Component\Filesystem\Path;

/**
 * Generates deterministic PHPUnit bootstrap shims for working-directory test runs.
 *
 * The generated shim MUST load the consumer bootstrap first and MAY then load
 * the active DevTools package autoloader so packaged PHPUnit configuration can
 * still resolve DevTools-owned extension classes during global installations.
 */
final readonly class BootstrapShimGenerator
{
    /**
     * @param FilesystemInterface $filesystem the filesystem used to persist generated shims
     */
    public function __construct(
        private FilesystemInterface $filesystem,
    ) {}

    /**
     * Writes a deterministic bootstrap shim under the resolved cache directory.
     *
     * @param string $projectBootstrap the consumer-project bootstrap path
     * @param string $cacheDirectory the cache directory where the shim SHOULD be stored
     *
     * @return string the generated bootstrap shim path
     */
    public function generate(string $projectBootstrap, string $cacheDirectory): string
    {
        $bootstrapShim = Path::join($cacheDirectory, 'bootstrap.php');

        $this->filesystem->dumpFile($bootstrapShim, $this->render($projectBootstrap));

        return $bootstrapShim;
    }

    /**
     * Renders the bootstrap shim contents.
     *
     * @param string $projectBootstrap the consumer-project bootstrap path
     *
     * @return string the bootstrap shim source code
     */
    private function render(string $projectBootstrap): string
    {
        $devToolsAutoload = DevToolsPathResolver::getPackagePath('vendor/autoload.php');

        return <<<PHP
            <?php

            declare(strict_types=1);

            require_once {$this->export($projectBootstrap)};

            if (file_exists({$this->export($devToolsAutoload)})) {
                require_once {$this->export($devToolsAutoload)};
            }
            PHP;
    }

    /**
     * Escapes a runtime string as a valid PHP string literal.
     *
     * @param string $value the value to render as PHP code
     *
     * @return string the exported PHP literal
     */
    private function export(string $value): string
    {
        return var_export($value, true);
    }
}
