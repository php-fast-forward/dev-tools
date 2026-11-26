---
category: added
type: minor
---

Adds versioned project capability profiles, interactive setup and tooling status
without installing additional tools. Standards and reports honor explicit
tests, quality, docs and metrics selections, reject invalid profiles and fail
when no checks are selected or a required selected tool is unavailable. Projects
without dev-tools.json retain the full legacy pipeline. This establishes the
selection contract for future optional runtimes; the bundled distribution is
unchanged.
