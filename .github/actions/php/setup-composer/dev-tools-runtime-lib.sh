#!/usr/bin/env bash

resolve_dev_tools_workspace_path() {
    local input_path="${1:-.}"

    if [[ "${input_path}" = /* ]]; then
        printf '%s\n' "${input_path}"

        return
    fi

    printf '%s/%s\n' "$(pwd)" "${input_path#./}"
}

workspace_is_dev_tools_repository() {
    local workspace_root="${1:?Workspace root is required}"
    local composer_json="${workspace_root}/composer.json"

    if [ ! -f "${composer_json}" ]; then
        return 1
    fi

    php -r '
        $composer = json_decode((string) file_get_contents($argv[1]), true);

        if (! is_array($composer)) {
            exit(1);
        }

        exit(($composer["name"] ?? null) === "fast-forward/dev-tools" ? 0 : 1);
    ' "${composer_json}"
}

resolve_dev_tools_runtime() {
    local source_directory_input="${INPUT_DEV_TOOLS_SOURCE_DIRECTORY:-.dev-tools-actions}"

    DEV_TOOLS_WORKSPACE_ROOT="$(pwd)"
    DEV_TOOLS_SOURCE_DIRECTORY="$(resolve_dev_tools_workspace_path "${source_directory_input}")"
    DEV_TOOLS_LOCAL_AUTOLOAD="${DEV_TOOLS_WORKSPACE_ROOT}/vendor/autoload.php"
    DEV_TOOLS_LOCAL_INSTALLED_BINARY="${DEV_TOOLS_WORKSPACE_ROOT}/vendor/bin/dev-tools"
    DEV_TOOLS_LOCAL_REPOSITORY_BINARY="${DEV_TOOLS_WORKSPACE_ROOT}/bin/dev-tools"

    if [ -x "${DEV_TOOLS_LOCAL_INSTALLED_BINARY}" ] && [ -f "${DEV_TOOLS_LOCAL_AUTOLOAD}" ]; then
        DEV_TOOLS_RUNTIME_SOURCE='local'
        DEV_TOOLS_RUNTIME_BINARY="${DEV_TOOLS_LOCAL_INSTALLED_BINARY}"
        DEV_TOOLS_RUNTIME_AUTOLOAD="${DEV_TOOLS_LOCAL_AUTOLOAD}"

        return 0
    fi

    if [ -x "${DEV_TOOLS_LOCAL_REPOSITORY_BINARY}" ] && [ -f "${DEV_TOOLS_LOCAL_AUTOLOAD}" ] && workspace_is_dev_tools_repository "${DEV_TOOLS_WORKSPACE_ROOT}"; then
        DEV_TOOLS_RUNTIME_SOURCE='local'
        DEV_TOOLS_RUNTIME_BINARY="${DEV_TOOLS_LOCAL_REPOSITORY_BINARY}"
        DEV_TOOLS_RUNTIME_AUTOLOAD="${DEV_TOOLS_LOCAL_AUTOLOAD}"

        return 0
    fi

    if [ ! -d "${DEV_TOOLS_SOURCE_DIRECTORY}" ]; then
        echo "The DevTools workflow source directory was not found: ${DEV_TOOLS_SOURCE_DIRECTORY}" >&2

        return 1
    fi

    if ! workspace_is_dev_tools_repository "${DEV_TOOLS_SOURCE_DIRECTORY}"; then
        echo "The DevTools workflow source directory does not point to the fast-forward/dev-tools package: ${DEV_TOOLS_SOURCE_DIRECTORY}" >&2
        echo "Checkout the full php-fast-forward/dev-tools source into ${source_directory_input} before using this action." >&2

        return 1
    fi

    DEV_TOOLS_RUNTIME_SOURCE='workflow'
    DEV_TOOLS_RUNTIME_BINARY="${DEV_TOOLS_SOURCE_DIRECTORY}/bin/dev-tools"
    DEV_TOOLS_RUNTIME_AUTOLOAD="${DEV_TOOLS_SOURCE_DIRECTORY}/vendor/autoload.php"
}

runtime_requires_workflow_fallback() {
    [ "${DEV_TOOLS_RUNTIME_SOURCE}" = 'workflow' ]
}

ensure_resolved_runtime_is_available() {
    if [ ! -x "${DEV_TOOLS_RUNTIME_BINARY}" ]; then
        echo "Resolved DevTools binary is not executable: ${DEV_TOOLS_RUNTIME_BINARY}" >&2

        return 1
    fi

    if [ ! -f "${DEV_TOOLS_RUNTIME_AUTOLOAD}" ]; then
        echo "Resolved DevTools autoload file was not found: ${DEV_TOOLS_RUNTIME_AUTOLOAD}" >&2

        return 1
    fi
}
