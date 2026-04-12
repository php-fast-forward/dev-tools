<?php

declare(strict_types=1);

function changelogSkillProjectRoot(?string $candidate = null): string
{
    $path = $candidate ?? getcwd();

    if (false === $path || '' === $path) {
        throw new RuntimeException('Unable to determine the current working directory.');
    }

    if (is_file($path)) {
        $path = dirname($path);
    }

    $current = realpath($path) ?: $path;

    while (true) {
        if (is_file($current . '/composer.json') || is_dir($current . '/.git')) {
            return $current;
        }

        $parent = dirname($current);

        if ($parent === $current) {
            throw new RuntimeException('Unable to locate the project root from ' . $path . '.');
        }

        $current = $parent;
    }
}

function changelogSkillRequireAutoload(string $projectRoot): void
{
    $autoload = $projectRoot . '/vendor/autoload.php';

    if (is_file($autoload)) {
        require_once $autoload;
    }
}

/**
 * @param list<string> $command
 * @param string $cwd
 */
function changelogSkillRun(array $command, string $cwd): string
{
    $escapedCommand = implode(' ', array_map(static fn(string $part): string => escapeshellarg($part), $command));

    $process = proc_open($escapedCommand, [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $cwd,);

    if (! is_resource($process)) {
        throw new RuntimeException('Unable to execute command: ' . $escapedCommand);
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if (0 !== $exitCode) {
        throw new RuntimeException(trim($stderr) ?: ('Command failed: ' . $escapedCommand));
    }

    return trim((string) $stdout);
}

function changelogSkillNormalizeVersion(string $value): string
{
    return ltrim(trim($value, "[] \t\n\r\0\x0B"), 'v');
}

function changelogSkillIsReleaseTag(string $tag): bool
{
    return 1 === preg_match('/^v?\d+\.\d+\.\d+(?:[-.][A-Za-z0-9.-]+)?$/', $tag);
}

/**
 * @param string $projectRoot
 *
 * @return array{
 *   changelog_exists: bool,
 *   changelog_has_content: bool,
 *   unreleased_present: bool,
 *   documented_versions: list<string>,
 *   latest_documented_version: string|null
 * }
 */
function changelogSkillReadChangelogState(string $projectRoot): array
{
    $changelogPath = $projectRoot . '/CHANGELOG.md';

    if (! is_file($changelogPath)) {
        return [
            'changelog_exists' => false,
            'changelog_has_content' => false,
            'unreleased_present' => false,
            'documented_versions' => [],
            'latest_documented_version' => null,
        ];
    }

    $contents = trim((string) file_get_contents($changelogPath));
    $documentedVersions = [];
    $unreleasedPresent = false;

    preg_match_all(
        '/^##\s+\[?(Unreleased|v?\d+\.\d+\.\d+(?:[-.][A-Za-z0-9.-]+)?)\]?(?:\s+-\s+[^\r\n]+)?$/m',
        $contents,
        $matches,
    );

    foreach ($matches[1] as $heading) {
        if ('Unreleased' === $heading) {
            $unreleasedPresent = true;
            continue;
        }

        $documentedVersions[] = changelogSkillNormalizeVersion($heading);
    }

    return [
        'changelog_exists' => true,
        'changelog_has_content' => '' !== $contents,
        'unreleased_present' => $unreleasedPresent,
        'documented_versions' => array_values(array_unique($documentedVersions)),
        'latest_documented_version' => $documentedVersions[0] ?? null,
    ];
}

/**
 * @param string $projectRoot
 *
 * @return list<array{tag: string, version: string}>
 */
function changelogSkillReadTags(string $projectRoot): array
{
    $output = changelogSkillRun(['git', 'tag', '--sort=version:refname'], $projectRoot);

    if ('' === $output) {
        return [];
    }

    $tags = [];

    foreach (preg_split('/\R/', $output) ?: [] as $tag) {
        $tag = trim($tag);

        if ('' === $tag || ! changelogSkillIsReleaseTag($tag)) {
            continue;
        }

        $tags[] = [
            'tag' => $tag,
            'version' => changelogSkillNormalizeVersion($tag),
        ];
    }

    return $tags;
}

function changelogSkillEmitJson(array $payload): void
{
    echo json_encode($payload, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES) . \PHP_EOL;
}
