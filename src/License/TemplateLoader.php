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

namespace FastForward\DevTools\License;

use RuntimeException;

use function Safe\file_get_contents;

/**
 * Loads license template files from the filesystem.
 *
 * This class reads license template files from a configured templates directory.
 * The default directory is the packaged resources/licenses folder.
 */
final readonly class TemplateLoader implements TemplateLoaderInterface
{
    private string $templatesPath;

    /**
     * Creates a new TemplateLoader instance.
     *
     * @param string|null $templatesPath Optional custom path to the templates directory
     */
    public function __construct(?string $templatesPath = null)
    {
        $this->templatesPath = $templatesPath ?? \dirname(__DIR__, 2) . '/resources/licenses';
    }

    /**
     * Loads a license template file by its filename.
     *
     * @param string $templateFilename The filename of the template to load (e.g., "mit.txt")
     *
     * @return string The template content
     *
     * @throws RuntimeException if the template file is not found
     */
    public function load(string $templateFilename): string
    {
        $templatePath = $this->templatesPath . '/' . $templateFilename;

        if (! file_exists($templatePath)) {
            throw new RuntimeException(\sprintf(
                'License template "%s" not found in "%s"',
                $templateFilename,
                $this->templatesPath
            ));
        }

        return file_get_contents($templatePath);
    }
}
