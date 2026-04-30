#!/usr/bin/env bash
set -euo pipefail

repository="${1:?Repository path is required.}"
conflict_path="${2:?Conflict path is required.}"
stage="${3:-2}"

entry="$(
    git -C "${repository}" ls-files -u -- "${conflict_path}" |
        awk -v stage="${stage}" '$3 == stage { print $1 " " $2; exit }'
)"

if [ -z "${entry}" ]; then
    printf 'No unmerged stage %s entry was found for %s.\n' "${stage}" "${conflict_path}" >&2

    exit 1
fi

mode="${entry%% *}"
object_id="${entry#* }"

if [ "${mode}" != '160000' ]; then
    printf 'Path %s is not a gitlink conflict (mode %s).\n' "${conflict_path}" "${mode}" >&2

    exit 1
fi

git -C "${repository}" update-index --cacheinfo "${mode},${object_id},${conflict_path}"
