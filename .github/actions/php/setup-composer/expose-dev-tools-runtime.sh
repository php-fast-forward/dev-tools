#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "$0")/dev-tools-runtime-lib.sh"

resolve_dev_tools_runtime
ensure_resolved_runtime_is_available

runtime_directory="${RUNNER_TEMP:-${TMPDIR:-/tmp}}/dev-tools-runtime/bin"
wrapper_path="${runtime_directory}/dev-tools"

mkdir -p "${runtime_directory}"

{
    printf '#!/usr/bin/env bash\n'
    printf 'set -euo pipefail\n'
    printf 'exec %q "$@"\n' "${DEV_TOOLS_RUNTIME_BINARY}"
} > "${wrapper_path}"

chmod +x "${wrapper_path}"

{
    printf 'DEV_TOOLS_BINARY=%s\n' "${DEV_TOOLS_RUNTIME_BINARY}"
    printf 'DEV_TOOLS_AUTOLOAD=%s\n' "${DEV_TOOLS_RUNTIME_AUTOLOAD}"
    printf 'DEV_TOOLS_AUTO_RESOLVE_AUTOLOAD=%s\n' "${DEV_TOOLS_RUNTIME_AUTOLOAD}"
    printf 'DEV_TOOLS_RUNTIME_SOURCE=%s\n' "${DEV_TOOLS_RUNTIME_SOURCE}"
} >> "${GITHUB_ENV}"

printf '%s\n' "${runtime_directory}" >> "${GITHUB_PATH}"

{
    printf 'binary=%s\n' "${DEV_TOOLS_RUNTIME_BINARY}"
    printf 'autoload=%s\n' "${DEV_TOOLS_RUNTIME_AUTOLOAD}"
    printf 'source=%s\n' "${DEV_TOOLS_RUNTIME_SOURCE}"
    printf 'command=%s\n' "${wrapper_path}"
} >> "${GITHUB_OUTPUT}"
