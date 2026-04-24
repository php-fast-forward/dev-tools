Surface Priorities
==================

When a pull request is ready for review, inspect the highest-risk surfaces
first.

Highest priority
----------------

- ``src/`` when public behavior, orchestration, dependency injection, or
  compatibility may have changed.
- ``tests/`` when behavior changed but coverage may be missing or weakened.
- ``.github/workflows/`` and ``.github/actions/`` when permissions, triggers,
  release behavior, or CI guarantees may have changed.
- ``resources/github-actions/`` when a repository-facing workflow wrapper can
  drift from the reusable workflow implementation.
- ``.agents/skills/`` and ``.agents/agents/`` when packaged prompts or
  consumer-synchronized capabilities changed.

Additional Fast Forward review surfaces
--------------------------------------

- ``README.md`` and ``docs/`` for onboarding, command, or workflow drift.
- ``CHANGELOG.md`` for notable user-facing or automation-facing changes.
- ``.github/wiki`` when generated wiki output is touched. In this repository,
  the wiki preview and wiki maintenance workflows can legitimately move the
  submodule pointer as workflow-managed state, so confirm whether the change
  matches that automation before flagging it as unrelated drift.
- ``resources/`` when synchronized templates or packaged defaults changed.

Typical review questions
------------------------

- Did the implementation change behavior without matching tests?
- Did the workflow wrapper stay aligned with the reusable workflow?
- Did workflow or action changes include executable validation evidence, not
  only YAML review?
- If a workflow pushes bot-authored commits, does it dispatch or mirror
  required checks for the new commit?
- Are permissions, local action checkouts, and event-specific inputs valid for
  reusable workflows and packaged consumer wrappers?
- Did packaged assets change without explaining the consumer impact?
- Did docs, README, changelog, or generated outputs remain consistent with the
  implementation?
- Did the pull request alter release automation, sync flows, or CI permissions
  in a risky way?
