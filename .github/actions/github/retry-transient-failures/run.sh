#!/usr/bin/env bash
set -euo pipefail

run_id="${INPUT_RUN_ID}"
run_attempt="${INPUT_RUN_ATTEMPT}"
workflow_name="${INPUT_WORKFLOW_NAME}"
max_run_attempts="${INPUT_MAX_RUN_ATTEMPTS:-2}"

if [ -z "${GH_TOKEN:-}" ]; then
    echo "GH_TOKEN is required." >&2

    exit 1
fi

failed_jobs_csv=""
matched_jobs_csv=""
uninspectable_jobs_csv=""

csv_append() {
    local current="$1"
    local value="$2"

    if [ -z "${current}" ]; then
        printf '%s' "${value}"

        return
    fi

    printf '%s,%s' "${current}" "${value}"
}

csv_to_summary_list() {
    local csv="$1"
    local rendered=()
    local item=""

    if [ -z "${csv}" ]; then
        return
    fi

    IFS=',' read -r -a rendered <<< "${csv}"

    for item in "${rendered[@]}"; do
        printf '`%s`' "${item}"

        if [ "${item}" != "${rendered[${#rendered[@]}-1]}" ]; then
            printf ', '
        fi
    done
}

build_summary() {
    local status="$1"
    local lines=(
        "## Transient Failure Retry Summary"
        ""
        "- Workflow: \`${workflow_name}\`"
        "- Run ID: \`${run_id}\`"
        "- Run attempt: \`${run_attempt}\`"
        "- Retry status: \`${status}\`"
    )

    if [ -n "${failed_jobs_csv}" ]; then
        lines+=("- Failed jobs inspected: $(csv_to_summary_list "${failed_jobs_csv}")")
    fi

    if [ -n "${matched_jobs_csv}" ]; then
        lines+=("- Jobs with transient GitHub failure signatures: $(csv_to_summary_list "${matched_jobs_csv}")")
    fi

    if [ -n "${uninspectable_jobs_csv}" ]; then
        lines+=("- Failed jobs with unreadable logs: $(csv_to_summary_list "${uninspectable_jobs_csv}")")
    fi

    case "${status}" in
        rerun-requested)
            lines+=("- Action: Requested a rerun of failed jobs because every inspectable failed job matched transient GitHub-side error signatures.")
            ;;
        skipped-run-attempt-limit)
            lines+=("- Action: Skipped rerun because the workflow already reached the configured retry limit.")
            ;;
        skipped-no-failed-jobs)
            lines+=("- Action: Skipped rerun because the workflow reported failure without failed jobs to inspect.")
            ;;
        skipped-no-transient-match)
            lines+=("- Action: Skipped rerun because at least one failed job did not match the transient GitHub-side signatures.")
            ;;
        skipped-uninspectable-logs)
            lines+=("- Action: Skipped rerun because at least one failed job log could not be downloaded through the GitHub Actions API.")
            ;;
    esac

    printf '%s\n' "${lines[@]}"
}

write_summary_output() {
    local summary="$1"
    local delimiter="SUMMARY_$(date +%s%N)"

    {
        printf 'summary<<%s\n' "${delimiter}"
        printf '%s\n' "${summary}"
        printf '%s\n' "${delimiter}"
    } >> "${GITHUB_OUTPUT}"
}

write_status_and_summary() {
    local status="$1"
    local summary

    summary="$(build_summary "${status}")"

    printf 'status=%s\n' "${status}" >> "${GITHUB_OUTPUT}"
    write_summary_output "${summary}"
}

log_matches_transient_signature() {
    local log_file="$1"

    grep -Eiq \
        "RPC failed; HTTP 5[0-9][0-9]|expected flush after ref listing|expected 'packfile'|remote:[[:space:]]+Internal Server Error|requested URL returned error:[[:space:]]*5[0-9][0-9]|fatal:[[:space:]]+unable to access 'https://github\\.com/.*': The requested URL returned error:[[:space:]]*5[0-9][0-9]" \
        "${log_file}"
}

download_job_logs() {
    local job_id="$1"
    local output_file="$2"

    curl \
        -sS -L \
        -H "Accept: application/vnd.github+json" \
        -H "Authorization: Bearer ${GH_TOKEN}" \
        -H "X-GitHub-Api-Version: 2022-11-28" \
        -o "${output_file}" \
        -w '%{http_code}' \
        "https://api.github.com/repos/${GITHUB_REPOSITORY}/actions/jobs/${job_id}/logs"
}

if [ "${run_attempt}" -ge "${max_run_attempts}" ]; then
    write_status_and_summary "skipped-run-attempt-limit"

    exit 0
fi

jobs_json="$(gh api "repos/${GITHUB_REPOSITORY}/actions/runs/${run_id}/jobs?per_page=100")"
failed_jobs_json="$(jq -c '.jobs[] | select(.conclusion == "failure")' <<< "${jobs_json}")"

if [ -z "${failed_jobs_json}" ]; then
    write_status_and_summary "skipped-no-failed-jobs"

    exit 0
fi

while IFS= read -r failed_job; do
    [ -n "${failed_job}" ] || continue

    job_id="$(jq -r '.id' <<< "${failed_job}")"
    job_name="$(jq -r '.name' <<< "${failed_job}")"
    failed_jobs_csv="$(csv_append "${failed_jobs_csv}" "${job_name}")"

    temporary_log_file="$(mktemp)"

    log_status_code="$(download_job_logs "${job_id}" "${temporary_log_file}")"

    if [ "${log_status_code}" != "200" ]; then
        uninspectable_jobs_csv="$(csv_append "${uninspectable_jobs_csv}" "${job_name} (${log_status_code})")"
        rm -f "${temporary_log_file}"
        write_status_and_summary "skipped-uninspectable-logs"

        exit 0
    fi

    if ! log_matches_transient_signature "${temporary_log_file}"; then
        rm -f "${temporary_log_file}"
        write_status_and_summary "skipped-no-transient-match"

        exit 0
    fi

    matched_jobs_csv="$(csv_append "${matched_jobs_csv}" "${job_name}")"
    rm -f "${temporary_log_file}"
done <<< "${failed_jobs_json}"

gh api -X POST "repos/${GITHUB_REPOSITORY}/actions/runs/${run_id}/rerun-failed-jobs" >/dev/null

write_status_and_summary "rerun-requested"
