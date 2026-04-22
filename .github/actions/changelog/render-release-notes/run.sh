#!/usr/bin/env bash
set -euo pipefail

mkdir -p "$(dirname "${INPUT_OUTPUT_FILE}")"
composer dev-tools changelog:show -- "${INPUT_VERSION}" --file="${INPUT_CHANGELOG_FILE}" > "${INPUT_OUTPUT_FILE}"
