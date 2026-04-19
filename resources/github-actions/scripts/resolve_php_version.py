#!/usr/bin/env python3

from __future__ import annotations

import argparse
import json
import os
import re
import sys
from pathlib import Path

SUPPORTED_MINORS = ["8.3", "8.4", "8.5"]
DEFAULT_PHP_VERSION = "8.3"


def version_to_tuple(version: str) -> tuple[int, int]:
    major, minor = version.split(".")
    return int(major), int(minor)


def normalize_minor(version: str) -> str | None:
    match = re.match(r"^\s*v?(8)\.(\d+)(?:\.\d+)?(?:\.\*)?\s*$", version)

    if match is None:
        return None

    return f"{match.group(1)}.{match.group(2)}"


def next_supported_minor(version: str) -> str | None:
    if version not in SUPPORTED_MINORS:
        return None

    index = SUPPORTED_MINORS.index(version) + 1

    if index >= len(SUPPORTED_MINORS):
        major, minor = version_to_tuple(version)
        return f"{major}.{minor + 1}"

    return SUPPORTED_MINORS[index]


def infer_clause_lower_bound(clause: str) -> str | None:
    tokens = re.findall(r"(\^|~|>=|>|<=|<|==|=)?\s*v?(8\.\d+(?:\.\d+)?(?:\.\*)?)", clause)
    lower_bounds: list[str] = []

    for operator, version in tokens:
        normalized = normalize_minor(version)

        if normalized is None:
            continue

        if operator in ("", "=", "==", "^", "~", ">="):
            lower_bounds.append(normalized)
            continue

        if operator == ">":
            next_minor = next_supported_minor(normalized)

            if next_minor is not None:
                lower_bounds.append(next_minor)

    if not lower_bounds:
        return None

    return max(lower_bounds, key=version_to_tuple)


def infer_minimum_supported_minor(requirement: str) -> str | None:
    clauses = [clause.strip() for clause in requirement.split("||")]
    lower_bounds = [
        clause_lower_bound
        for clause in clauses
        if (clause_lower_bound := infer_clause_lower_bound(clause)) is not None
    ]

    if not lower_bounds:
        return None

    return min(lower_bounds, key=version_to_tuple)


def resolve_from_lock(composer_lock: Path) -> tuple[str | None, str | None]:
    if not composer_lock.exists():
        return None, None

    try:
        payload = json.loads(composer_lock.read_text())
    except json.JSONDecodeError:
        return None, "composer.lock exists but could not be parsed"

    platform_overrides = payload.get("platform-overrides") or {}
    platform_php = platform_overrides.get("php")

    if isinstance(platform_php, str):
        resolved = normalize_minor(platform_php)

        if resolved is not None:
            return resolved, "composer.lock platform-overrides.php"

        return None, "composer.lock platform-overrides.php is not a supported PHP version"

    return None, None


def resolve_from_json(composer_json: Path) -> tuple[str | None, str | None]:
    if not composer_json.exists():
        return None, "composer.json does not exist"

    try:
        payload = json.loads(composer_json.read_text())
    except json.JSONDecodeError:
        return None, "composer.json could not be parsed"

    config_platform_php = (((payload.get("config") or {}).get("platform") or {}).get("php"))

    if isinstance(config_platform_php, str):
        resolved = normalize_minor(config_platform_php)

        if resolved is not None:
            return resolved, "composer.json config.platform.php"

        return None, "composer.json config.platform.php is not a supported PHP version"

    require_php = ((payload.get("require") or {}).get("php"))

    if isinstance(require_php, str):
        resolved = infer_minimum_supported_minor(require_php)

        if resolved is not None:
            return resolved, "composer.json require.php"

        return None, "composer.json require.php could not be resolved safely"

    return None, None


def resolve_php_version(composer_json: Path, composer_lock: Path) -> tuple[str, str, str | None]:
    resolved, source = resolve_from_lock(composer_lock)

    if resolved is None:
        resolved, source = resolve_from_json(composer_json)

    if resolved is None:
        return DEFAULT_PHP_VERSION, "fallback", "No reliable PHP version source was found. Falling back to 8.3."

    if resolved not in SUPPORTED_MINORS:
        return DEFAULT_PHP_VERSION, "fallback", (
            f"Resolved PHP version {resolved} from {source} is outside the supported CI policy. Falling back to 8.3."
        )

    return resolved, source or "fallback", None


def write_output(name: str, value: str) -> None:
    github_output = os.environ.get("GITHUB_OUTPUT")

    if github_output:
        with open(github_output, "a", encoding="utf-8") as handle:
            handle.write(f"{name}={value}\n")


def main() -> int:
    parser = argparse.ArgumentParser(description="Resolve the PHP version used by Fast Forward workflows.")
    parser.add_argument("--composer-json", default="composer.json")
    parser.add_argument("--composer-lock", default="composer.lock")
    args = parser.parse_args()

    resolved_version, source, warning = resolve_php_version(
        Path(args.composer_json),
        Path(args.composer_lock),
    )

    matrix_versions = [version for version in SUPPORTED_MINORS if version_to_tuple(version) >= version_to_tuple(resolved_version)]
    matrix = json.dumps({"php-version": matrix_versions}, separators=(",", ":"))

    print(f"Resolved PHP version source: {source}")
    print(f"Resolved PHP version: {resolved_version}")
    print(f"Resolved PHP test matrix: {matrix_versions}")

    if warning:
        print(f"Warning: {warning}")

    write_output("php-version", resolved_version)
    write_output("php-version-source", source)
    write_output("test-matrix", matrix)
    write_output("warning", warning or "")

    return 0


if __name__ == "__main__":
    sys.exit(main())
