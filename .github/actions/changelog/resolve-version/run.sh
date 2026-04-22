#!/usr/bin/env bash
set -euo pipefail

if [ -n "${INPUT_VERSION}" ]; then
    version="${INPUT_VERSION}"
    source="input"
else
    version="$(composer dev-tools changelog:next-version -- --file="${INPUT_CHANGELOG_FILE}")"
    source="inferred"
fi

echo "value=${version}" >> "${GITHUB_OUTPUT}"
echo "source=${source}" >> "${GITHUB_OUTPUT}"
