#!/usr/bin/env bash
set -euo pipefail

title="$(jq -r '.pull_request.title // ""' "${GITHUB_EVENT_PATH}")"
body="$(jq -r '.pull_request.body // ""' "${GITHUB_EVENT_PATH}")"
pull_request_number="$(jq -r '.pull_request.number // ""' "${GITHUB_EVENT_PATH}")"
issue_number="$(
    printf '%s %s\n' "${title}" "${body}" \
        | sed -nE 's/.*(closes|fixes|resolves|addresses)[[:space:]]+#([[:digit:]]+).*/\2/Ip' \
        | head -1
)"

if [ -z "${issue_number}" ]; then
    echo "No linked issue was found in the pull request title or body."

    exit 0
fi

labels="$(gh issue view "${issue_number}" --repo "${GITHUB_REPOSITORY}" --json labels --jq '.labels[].name' 2>/dev/null || true)"

if [ -z "${labels}" ]; then
    echo "Issue #${issue_number} has no labels to copy."

    exit 0
fi

while IFS= read -r label; do
    if [ -n "${label}" ]; then
        gh pr edit "${pull_request_number}" --repo "${GITHUB_REPOSITORY}" --add-label "${label}" 2>/dev/null || true
    fi
done <<EOF
${labels}
EOF
