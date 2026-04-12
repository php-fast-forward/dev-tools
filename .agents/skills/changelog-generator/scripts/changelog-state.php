#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$projectRoot = changelogSkillProjectRoot($argv[1] ?? null);
changelogSkillRequireAutoload($projectRoot);

$changelogState = changelogSkillReadChangelogState($projectRoot);
$tags = changelogSkillReadTags($projectRoot);
$documentedVersions = array_flip($changelogState['documented_versions']);
$undocumentedTags = [];
$releaseRanges = [];

foreach ($tags as $index => $tagInfo) {
    if (isset($documentedVersions[$tagInfo['version']])) {
        continue;
    }

    $undocumentedTags[] = $tagInfo;
    $releaseRanges[] = [
        'version' => $tagInfo['version'],
        'from_tag' => $tags[$index - 1]['tag'] ?? null,
        'to_tag' => $tagInfo['tag'],
    ];
}

$latestTag = [] === $tags ? null : $tags[array_key_last($tags)];
$latestDocumentedTag = null;

if (null !== $changelogState['latest_documented_version']) {
    foreach ($tags as $tagInfo) {
        if ($tagInfo['version'] === $changelogState['latest_documented_version']) {
            $latestDocumentedTag = $tagInfo;
        }
    }
}

changelogSkillEmitJson([
    'project_root' => $projectRoot,
    'changelog_exists' => $changelogState['changelog_exists'],
    'changelog_has_content' => $changelogState['changelog_has_content'],
    'unreleased_present' => $changelogState['unreleased_present'],
    'documented_versions' => $changelogState['documented_versions'],
    'latest_documented_version' => $changelogState['latest_documented_version'],
    'latest_documented_tag' => $latestDocumentedTag['tag'] ?? null,
    'all_tags' => $tags,
    'undocumented_tags' => $undocumentedTags,
    'release_ranges' => $releaseRanges,
    'suggested_unreleased_base' => $latestTag['tag'] ?? null,
    'needs_bootstrap' => ! $changelogState['changelog_exists'] || ! $changelogState['changelog_has_content'],
    'needs_backfill' => [] !== $undocumentedTags,
]);
