#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "$0")/dev-tools-runtime-lib.sh"

resolve_dev_tools_runtime

needs_fallback='false'

if runtime_requires_workflow_fallback; then
    needs_fallback='true'
fi

{
    printf 'source=%s\n' "${DEV_TOOLS_RUNTIME_SOURCE}"
    printf 'needs-fallback=%s\n' "${needs_fallback}"
    printf 'binary=%s\n' "${DEV_TOOLS_RUNTIME_BINARY}"
    printf 'autoload=%s\n' "${DEV_TOOLS_RUNTIME_AUTOLOAD}"
    printf 'source-directory=%s\n' "${DEV_TOOLS_SOURCE_DIRECTORY}"
} >> "${GITHUB_OUTPUT}"
