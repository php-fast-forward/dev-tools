<?php

declare(strict_types=1);

/**
 * Resolves the selected documentation template consistently with the docs command.
 *
 * @copyright 2026 Fast Forward
 * @license https://opensource.org/licenses/MIT MIT License
 */

use Composer\InstalledVersions;
use FastForward\DevTools\Path\DevToolsPathResolver;

require getenv('DEV_TOOLS_AUTOLOAD');

if (is_file('vendor/autoload.php') && realpath('vendor/autoload.php') !== realpath(getenv('DEV_TOOLS_AUTOLOAD'))) {
    require 'vendor/autoload.php';
}

$package = 'fast-forward/phpdoc-bootstrap-template';
$template = realpath(DevToolsPathResolver::getPreferredVendorPath($package));
$selected = null;

foreach (InstalledVersions::getAllRawData() as $installation) {
    $metadata = $installation['versions'][$package] ?? [];
    $path = $metadata['install_path'] ?? null;
    if (is_string($path) && realpath($path) === $template) {
        $selected = $metadata;
        break;
    }
}

$version = $selected['pretty_version'] ?? null;
if (!is_string($template) || !is_string($version) || version_compare(ltrim($version, 'v'), '2.1.0', '<')) {
    throw new RuntimeException('The selected documentation template must be installed at version 2.1.0 or newer.');
}

$environment = [
    'DEV_TOOLS_REPORTS_TEMPLATE' => $template,
    'DEV_TOOLS_REPORTS_TEMPLATE_VERSION' => $version,
    'DEV_TOOLS_REPORTS_TEMPLATE_REFERENCE' => $selected['reference'] ?? '',
];
$lines = [];
foreach ($environment as $key => $value) {
    if (!is_string($value) || preg_match('/[\x00-\x1f\x7f]/', $value)) {
        throw new RuntimeException('Invalid documentation template metadata: ' . $key);
    }
    $lines[] = $key . '=' . $value;
}
if (file_put_contents(getenv('GITHUB_ENV'), implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND) === false) {
    throw new RuntimeException('Cannot export selected documentation template metadata.');
}
echo json_encode($environment, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . PHP_EOL;
