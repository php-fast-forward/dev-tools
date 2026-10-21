#!/usr/bin/env bash
set -euo pipefail

base_url="${INPUT_BASE_URL}"
title="${INPUT_TITLE}"
attempts="${INPUT_ATTEMPTS}"
delay="${INPUT_DELAY}"

if [[ ! "$attempts" =~ ^([1-9]|[12][0-9]|30)$ ]] || [[ ! "$delay" =~ ^([1-9]|[12][0-9]|30)$ ]]; then
    echo '::error::Deployment check attempts and delay must be integers between 1 and 30.'
    exit 1
fi

# Share one bounded budget across every URL, including transfers and waits.
deadline=$((SECONDS + 180))

check_url() {
    local url="$1" label="$2" attempt remaining transfer_timeout wait_time
    local http_status='000' curl_status=0

    for ((attempt = 1; attempt <= attempts; attempt++)); do
        remaining=$((deadline - SECONDS))
        if ((remaining <= 0)); then
            echo '::error::Deployment health checks exceeded the 180-second total budget.'
            return 1
        fi
        transfer_timeout=$((remaining < 10 ? remaining : 10))
        echo "Checking ${label} (attempt ${attempt}/${attempts})"

        curl_status=0
        http_status="$(curl --silent --output /dev/null --location --connect-timeout 5 --max-time "$transfer_timeout" --write-out '%{http_code}' "$url" 2>/dev/null)" || curl_status=$?

        if ((SECONDS >= deadline)); then
            echo '::error::Deployment health checks exceeded the 180-second total budget.'
            return 1
        fi
        if ((curl_status == 0)) && [[ "$http_status" =~ ^[23][0-9][0-9]$ ]]; then
            echo "${label} is reachable (${http_status})."
            return 0
        fi

        # Never print curl stderr or malformed response values (which may contain secrets).
        [[ "$http_status" =~ ^[0-9]{3}$ ]] || http_status='000'
        echo "Attempt ${attempt} failed (HTTP ${http_status}, curl exit ${curl_status})."
        if ((attempt < attempts)); then
            remaining=$((deadline - SECONDS))
            wait_time=$((remaining < delay ? remaining : delay))
            if ((wait_time > 0)); then
                sleep "$wait_time"
            fi
        fi
    done

    echo "::error title=${title}::${label} is not reachable. Last HTTP status: ${http_status}; curl exit: ${curl_status}."
    return 1
}

while IFS='|' read -r relative_path label; do
    if [ -z "${label}" ]; then
        continue
    fi

    check_url "${base_url}${relative_path}" "${label}"
done <<EOF_CHECKS
${INPUT_CHECKS}
EOF_CHECKS
