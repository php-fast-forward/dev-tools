#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($argc < 3 || $argc > 4) {
    fwrite(\STDERR, "Usage: php diff-inventory.php <from-ref> <to-ref> [repo-path]\n");
    exit(1);
}

$fromRef = $argv[1];
$toRef = $argv[2];
$projectRoot = changelogSkillProjectRoot($argv[3] ?? null);
changelogSkillRequireAutoload($projectRoot);

$nameStatusOutput = changelogSkillRun(['git', 'diff', '--name-status', $fromRef, $toRef], $projectRoot);
$numStatOutput = changelogSkillRun(['git', 'diff', '--numstat', $fromRef, $toRef], $projectRoot);

$lineCounts = [];

foreach (preg_split('/\R/', $numStatOutput) ?: [] as $line) {
    if ('' === trim($line)) {
        continue;
    }

    $parts = preg_split('/\t+/', trim($line));

    if (! is_array($parts) || count($parts) < 3) {
        continue;
    }

    $path = $parts[2];
    $lineCounts[$path] = [
        'added_lines' => is_numeric($parts[0]) ? (int) $parts[0] : null,
        'deleted_lines' => is_numeric($parts[1]) ? (int) $parts[1] : null,
    ];
}

$files = [];

foreach (preg_split('/\R/', $nameStatusOutput) ?: [] as $line) {
    if ('' === trim($line)) {
        continue;
    }

    $parts = preg_split('/\t+/', trim($line));

    if (! is_array($parts) || count($parts) < 2) {
        continue;
    }

    $status = $parts[0];
    $oldPath = null;
    $path = $parts[1];

    if (str_starts_with($status, 'R') || str_starts_with($status, 'C')) {
        $oldPath = $parts[1] ?? null;
        $path = $parts[2] ?? $path;
    }

    $signals = [];
    $priority = 0;

    foreach ([
        ['composer.json', 'dependency-surface', 100],
        ['README.md', 'top-level-doc', 80],
        ['CHANGELOG.md', 'changelog', 80],
        ['src/Command/', 'command-surface', 95],
        ['src/', 'php-surface', 70],
        ['bin/', 'cli-entrypoint', 90],
        ['docs/', 'documentation', 60],
        ['.github/workflows/', 'workflow', 85],
        ['resources/github-actions/', 'workflow-template', 85],
        ['tests/', 'tests', 20],
    ] as [$prefix, $signal, $score]) {
        if ($path === $prefix || str_starts_with($path, $prefix)) {
            $signals[] = $signal;
            $priority = max($priority, $score);
        }
    }

    if ([] === $signals) {
        $signals[] = 'supporting-file';
        $priority = 10;
    }

    $files[] = [
        'path' => $path,
        'old_path' => $oldPath,
        'status' => $status,
        'signals' => $signals,
        'priority' => $priority,
        'added_lines' => $lineCounts[$path]['added_lines'] ?? null,
        'deleted_lines' => $lineCounts[$path]['deleted_lines'] ?? null,
    ];
}

usort(
    $files,
    static function (array $left, array $right): int {
        $priorityComparison = $right['priority'] <=> $left['priority'];

        if (0 !== $priorityComparison) {
            return $priorityComparison;
        }

        return $left['path'] <=> $right['path'];
    },
);

changelogSkillEmitJson([
    'project_root' => $projectRoot,
    'range' => [
        'from' => $fromRef,
        'to' => $toRef,
    ],
    'counts' => [
        'files' => count($files),
        'added' => count(array_filter($files, static fn(array $file): bool => str_starts_with($file['status'], 'A'))),
        'modified' => count(
            array_filter($files, static fn(array $file): bool => str_starts_with($file['status'], 'M'))
        ),
        'deleted' => count(array_filter($files, static fn(array $file): bool => str_starts_with($file['status'], 'D'))),
        'renamed' => count(array_filter($files, static fn(array $file): bool => str_starts_with($file['status'], 'R'))),
    ],
    'priority_paths' => array_values(array_map(
        static fn(array $file): string => $file['path'],
        array_slice($files, 0, 15),
    )),
    'files' => $files,
]);
