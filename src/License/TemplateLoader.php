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

final readonly class TemplateLoader
{
    private string $templatesPath;

    /**
     * @param string|null $templatesPath
     */
    public function __construct(?string $templatesPath = null)
    {
        $this->templatesPath = $templatesPath ?? __DIR__ . '/resources/templates';
    }

    /**
     * @param string $templateFilename
     *
     * @return string
     *
     * @throws RuntimeException
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
