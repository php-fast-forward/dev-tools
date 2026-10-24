#!/usr/bin/env bash

workspace_is_dev_tools_repository() {
    [ -f composer.json ] || return 1
    php -r '$manifest = json_decode(file_get_contents("composer.json"), true, 512, JSON_THROW_ON_ERROR); exit(($manifest["name"] ?? null) === "fast-forward/dev-tools" ? 0 : 1);'
}

resolve_dev_tools_runtime() {
    DEV_TOOLS_RUNTIME_SOURCE='cpx'
    DEV_TOOLS_RUNTIME_BINARY=''
    DEV_TOOLS_RUNTIME_AUTOLOAD=''

    if workspace_is_dev_tools_repository; then
        DEV_TOOLS_RUNTIME_SOURCE='local'
        DEV_TOOLS_RUNTIME_BINARY="$(pwd)/bin/dev-tools"
        DEV_TOOLS_RUNTIME_AUTOLOAD="$(pwd)/vendor/autoload.php"
    fi
}

resolve_cpx_runtime() {
    local metadata paths version
    version="${INPUT_DEV_TOOLS_VERSION:-2.0.0}"
    if [[ ! "${version}" =~ ^[0-9]+\.[0-9]+\.[0-9]+([-+][A-Za-z0-9.+-]+)?$ ]]; then
        echo 'dev-tools-version must be an exact published version, such as 2.0.0.' >&2
        return 1
    fi
    metadata="$(cpx --skip-local "fast-forward/dev-tools:${version}" runtime --json --no-interaction --no-ansi)"
    paths="$(printf '%s' "${metadata}" | php -r '
        $runtime = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
        foreach (["binary", "autoload"] as $key) {
            $path = $runtime[$key] ?? null;
            if (!is_string($path) || !str_starts_with($path, "/") || preg_match("/[\\x00-\\x1f\\x7f]/", $path)) {
                throw new RuntimeException("Invalid DevTools runtime path: " . $key);
            }
            echo $path, PHP_EOL;
        }
    ')"
    DEV_TOOLS_RUNTIME_BINARY="${paths%%$'\n'*}"
    DEV_TOOLS_RUNTIME_AUTOLOAD="${paths#*$'\n'}"
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
