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

namespace FastForward\DevTools;

use FastForward\DevTools\Console\DevTools;
use Symfony\Component\Console\Input\ArgvInput;

$projectVendorAutoload = \dirname(__DIR__, 4) . '/vendor/autoload.php';
$pluginVendorAutoload = \dirname(__DIR__) . '/vendor/autoload.php';

require_once file_exists($projectVendorAutoload) ? $projectVendorAutoload : $pluginVendorAutoload;

DevTools::create()->run(new ArgvInput([...$argv, '--no-plugins']));
