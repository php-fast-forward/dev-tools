Workflow And Action Validation
==============================

Workflow, local-action, and packaged-wrapper changes MUST receive an explicit
validation strategy during review. These surfaces often fail only after merge,
after a workflow-dispatched run, or after a bot-authored commit exercises a
path that normal pull-request CI does not trigger.

When To Apply
-------------

Use this checklist whenever a pull request touches any of these surfaces:

- ``.github/workflows/**``
- ``.github/actions/**``
- ``resources/github-actions/**``
- workflow-related docs that describe permissions, dispatching, branch
  protection, sync behavior, generated previews, or release automation

Required Questions
------------------

Ask these questions before deciding the workflow surface is safe:

- Does the workflow or action push commits with ``GITHUB_TOKEN``?
- If it pushes bot-authored commits, do required checks run, get dispatched, or
  get mirrored for the new commit?
- Are the required permissions declared in both the reusable workflow and any
  packaged consumer wrapper?
- Are local composite action paths available from the repository and ref used
  by the workflow?
- Does the changed automation behave correctly for same-repository pull
  requests, forked pull requests, ``push``, ``workflow_dispatch``, and
  ``workflow_call`` where those events are supported?
- Does the workflow rely on files from ``main`` that might not exist in a
  consumer repository or in the installed DevTools package version?
- Does the workflow update generated state such as ``.github/wiki``, Pages
  previews, release notes, changelog entries, labels, or project metadata?
- Does the workflow need queueing, cancellation, or required-check mirroring so
  branch protection sees the latest commit state?

Validation Strategies
---------------------

Prefer deterministic local validation when it can cover the changed behavior:

- run ``bash -n`` for shell scripts;
- parse changed YAML files or run ``actionlint`` when available;
- use fake ``gh`` and ``git`` wrappers to exercise action scripts without
  calling GitHub;
- create temporary Git repositories to validate merge, rebase, conflict,
  branch, tag, or submodule behavior;
- run changed PHP helper scripts directly against synthetic fixtures;
- verify packaged wrapper inputs, permissions, and reusable workflow paths stay
  aligned with the canonical workflow.

When local simulation cannot cover the behavior, call out the gap and prefer a
temporary validation branch or pull request. Close that validation PR after
recording the evidence in the real PR or issue. Do not require noisy temporary
PRs for every workflow change when a deterministic local harness covers the
risk.

Bot-Authored Commit Rule
------------------------

Any workflow that pushes commits with ``GITHUB_TOKEN`` MUST be reviewed for
required-check side effects. GitHub usually does not trigger another normal
``pull_request`` workflow run for commits pushed by the built-in workflow
token. If branch protection requires checks on the latest commit, the workflow
SHOULD dispatch the required workflow or publish matching commit statuses for
that bot-authored commit.

Review Output Expectations
--------------------------

The review MUST record one of the following:

- validation evidence, including commands, harnesses, or GitHub runs used;
- a concrete finding when validation reveals a bug or missing permission;
- a residual-risk note when the behavior cannot be exercised before merge.

If the pull request body already contains sufficient validation evidence,
reference it and verify that it covers the changed automation path. If the body
does not, call out the missing validation in the review.
