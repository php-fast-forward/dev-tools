#!/usr/bin/env bash
set -euo pipefail

release_tag="v${INPUT_VERSION}"

if gh release view "${release_tag}" --repo "${GITHUB_REPOSITORY}" >/dev/null 2>&1; then
    gh release edit "${release_tag}" \
        --repo "${GITHUB_REPOSITORY}" \
        --title "${release_tag}" \
        --notes-file "${INPUT_NOTES_FILE}"
else
    gh release create "${release_tag}" \
        --repo "${GITHUB_REPOSITORY}" \
        --target "${INPUT_TARGET}" \
        --title "${release_tag}" \
        --notes-file "${INPUT_NOTES_FILE}"
fi
