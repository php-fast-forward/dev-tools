#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "$0")/dev-tools-runtime-lib.sh"

resolve_dev_tools_runtime

{
    printf 'source=%s\n' "${DEV_TOOLS_RUNTIME_SOURCE}"
    printf 'binary=%s\n' "${DEV_TOOLS_RUNTIME_BINARY}"
    printf 'autoload=%s\n' "${DEV_TOOLS_RUNTIME_AUTOLOAD}"
} >> "${GITHUB_OUTPUT}"
