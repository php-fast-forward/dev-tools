#!/usr/bin/env bash
set -euo pipefail

git fetch origin '+refs/heads/pr-*:refs/remotes/origin/pr-*' || true

git for-each-ref --format='%(refname:short)' refs/remotes/origin/pr-* | while read -r remote_branch; do
    branch="${remote_branch#origin/}"
    pull_request_number="${branch#pr-}"

    if ! [[ "${pull_request_number}" =~ ^[0-9]+$ ]]; then
        echo "Skipping non-PR wiki preview branch ${branch}."
        continue
    fi

    state="$(gh pr view "${pull_request_number}" --repo "${GITHUB_REPOSITORY}" --json state --jq '.state' 2>/dev/null || echo UNKNOWN)"

    case "${state}" in
        CLOSED|MERGED)
            echo "Deleting wiki preview branch ${branch} for ${state} pull request #${pull_request_number}."
            git push origin --delete "${branch}" || true
            ;;
        OPEN)
            echo "Keeping wiki preview branch ${branch} for open pull request #${pull_request_number}."
            ;;
        *)
            echo "Could not resolve pull request #${pull_request_number} for wiki preview branch ${branch}. Keeping it."
            ;;
    esac
done
