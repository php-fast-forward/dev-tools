Review Contract
===============

Every rigorous review produced from this skill MUST follow this contract:

1. Findings first.
2. Findings ordered by severity.
3. Each finding anchored to a repository file reference whenever possible.
4. Open questions or assumptions after the findings, not before them.
5. A short summary only after the findings, and only when it adds value.

Expected output shape:

- Findings
  - ``High`` / ``Medium`` / ``Low`` severity labels when helpful.
  - One issue per bullet or short paragraph.
  - Explain the concrete risk or regression, not just the changed code.
  - Include the most relevant file path for each finding.
- Open Questions / Assumptions
  - Use only when uncertainty affects confidence in the findings.
- Summary
  - Briefly describe the net result after the findings.

Required review themes:

- Bugs and regressions.
- Missing or weak tests.
- Missing or stale documentation.
- Generated-output drift.
- Workflow, CI, or release automation risk.
- Consumer-sync or packaged-asset side effects.
- Workflow/action validation evidence for changes under ``.github/workflows``,
  ``.github/actions``, or ``resources/github-actions``.

If no issues are found:

- State that clearly.
- Mention the scope that was reviewed.
- Call out any remaining test or verification gaps, including workflow
  behavior that could not be exercised locally or through a temporary
  validation run.
