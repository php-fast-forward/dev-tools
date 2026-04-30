#!/usr/bin/env bash
set -euo pipefail

# Isolate nested Git commands from caller-specific repository environment such as hooks.
unset GIT_DIR GIT_WORK_TREE GIT_INDEX_FILE GIT_PREFIX GIT_INTERNAL_SUPER_PREFIX GIT_COMMON_DIR

target="${INPUT_TARGET:-.github/wiki}"
preview_branch="${INPUT_PREVIEW_BRANCH:?The preview branch input is required.}"
commit_message="${INPUT_COMMIT_MESSAGE:-Refresh wiki preview}"

git -C "${target}" fetch origin

if git -C "${target}" ls-remote --exit-code --heads origin "${preview_branch}" >/dev/null 2>&1; then
    git -C "${target}" switch -C "${preview_branch}" --track "origin/${preview_branch}"
    git -C "${target}" reset --hard "origin/${preview_branch}"
else
    git -C "${target}" switch --orphan "${preview_branch}"
    git -C "${target}" rm -rf . >/dev/null 2>&1 || true
    find "${target}" -mindepth 1 -maxdepth 1 ! -name '.git' -exec rm -rf {} +
fi

git -C "${target}" clean -fd

dev-tools wiki --target="${target}"

published="false"

if [ -n "$(git -C "${target}" status --porcelain)" ]; then
    git -C "${target}" config user.name "${GIT_AUTHOR_NAME:-github-actions[bot]}"
    git -C "${target}" config user.email "${GIT_AUTHOR_EMAIL:-41898282+github-actions[bot]@users.noreply.github.com}"
    git -C "${target}" add -A
    git -C "${target}" commit -m "${commit_message}"
    git -C "${target}" push --force-with-lease origin "HEAD:${preview_branch}"
    published="true"
fi

pointer_changed="false"

if ! git diff --quiet -- "${target}"; then
    pointer_changed="true"
fi

preview_sha="$(git -C "${target}" rev-parse HEAD 2>/dev/null || true)"

{
    echo "published=${published}"
    echo "pointer-changed=${pointer_changed}"
    echo "preview-sha=${preview_sha}"
} >> "${GITHUB_OUTPUT}"
