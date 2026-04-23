#!/usr/bin/env bash
set -euo pipefail

if composer dev-tools changelog:check -- --file="${INPUT_CHANGELOG_FILE}" --against="origin/${INPUT_BASE_REF}" >/dev/null 2>&1; then
    echo "created=false" >> "$GITHUB_OUTPUT"
    exit 0
fi

git fetch --no-tags --depth=1 origin "+refs/heads/${INPUT_HEAD_REF}:refs/remotes/origin/${INPUT_HEAD_REF}"
git switch -C "${INPUT_HEAD_REF}" "refs/remotes/origin/${INPUT_HEAD_REF}"
git config user.name "github-actions[bot]"
git config user.email "41898282+github-actions[bot]@users.noreply.github.com"

entry_message="$(php -r 'require "vendor/autoload.php"; $resolver = new \FastForward\DevTools\Changelog\DependabotChangelogEntryMessageResolver(); echo $resolver->resolve(getenv("INPUT_PULL_REQUEST_TITLE") ?: "", (int) (getenv("INPUT_PULL_REQUEST_NUMBER") ?: 0));')"

composer dev-tools changelog:entry -- --type=changed --file="${INPUT_CHANGELOG_FILE}" "${entry_message}"
git add "${INPUT_CHANGELOG_FILE}"
git commit -m "Add changelog entry for Dependabot PR #${INPUT_PULL_REQUEST_NUMBER}"
git push origin "HEAD:${INPUT_HEAD_REF}"

{
    echo "created=true"
    printf 'message=%s\n' "${entry_message}"
} >> "$GITHUB_OUTPUT"
