#!/usr/bin/env bash
set -euo pipefail

# Isolate nested Git commands from caller-specific repository environment such as hooks.
unset GIT_DIR GIT_WORK_TREE GIT_INDEX_FILE GIT_PREFIX GIT_INTERNAL_SUPER_PREFIX GIT_COMMON_DIR

target="${INPUT_TARGET:-.github/wiki}"
publish_branch="${INPUT_PUBLISH_BRANCH:-master}"
commit_message="${INPUT_COMMIT_MESSAGE:-Refresh wiki docs after merged release}"

git -C "${target}" fetch origin "${publish_branch}"

if ! git -C "${target}" switch -C "${publish_branch}" --track "origin/${publish_branch}" >/dev/null 2>&1; then
    git -C "${target}" switch "${publish_branch}" >/dev/null 2>&1
fi

git -C "${target}" reset --hard "origin/${publish_branch}"
git -C "${target}" clean -fd

dev-tools wiki --target="${target}"

if [ -z "$(git -C "${target}" status --porcelain)" ]; then
    pointer_changed="false"

    if ! git diff --quiet -- "${target}"; then
        pointer_changed="true"
    fi

    {
        echo "published=false"
        echo "pointer-changed=${pointer_changed}"
        echo "publish-sha=$(git -C "${target}" rev-parse HEAD)"
    } >> "${GITHUB_OUTPUT}"

    exit 0
fi

git -C "${target}" config user.name "${GIT_AUTHOR_NAME:-github-actions[bot]}"
git -C "${target}" config user.email "${GIT_AUTHOR_EMAIL:-41898282+github-actions[bot]@users.noreply.github.com}"
git -C "${target}" add -A
git -C "${target}" commit -m "${commit_message}"
git -C "${target}" push origin "HEAD:${publish_branch}"

pointer_changed="false"

if ! git diff --quiet -- "${target}"; then
    pointer_changed="true"
fi

{
    echo "published=true"
    echo "pointer-changed=${pointer_changed}"
    echo "publish-sha=$(git -C "${target}" rev-parse HEAD)"
} >> "${GITHUB_OUTPUT}"
