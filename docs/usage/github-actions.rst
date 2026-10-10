GitHub Actions Workflows
========================

FastForward DevTools provides a set of reusable GitHub Actions workflows that automate testing, documentation generation, and wiki synchronization. These workflows are synchronized into consumer repositories via the ``dev-tools:sync`` command.

Workflow Layers
---------------

The automation model separates shared implementations from consumer triggers:

*   **Local composite and JavaScript actions** in ``.github/actions/`` inside
    this repository. These contain the reusable implementation details for PHP
    setup, project-board transitions, GitHub Pages handling, changelog
    publishing, wiki branch management, and related helpers.
*   **Reusable workflows** in ``.github/workflows/`` inside this repository.
    These are the canonical automation entrypoints consumed by Fast Forward
    repositories and wrappers.
*   **Consumer wrappers** copied from ``resources/github-actions/*.yml`` into
    the consumer repository's ``.github/workflows/`` directory by
    ``dev-tools:sync``. These wrappers keep triggers and repository-specific
    defaults local while delegating implementation to the reusable workflows in
    ``php-fast-forward/dev-tools``.
*   **Workflow action source checkout** inside the reusable workflows when they
    need local action implementations from ``.github/actions/``. The reusable
    workflow checks out the upstream ``php-fast-forward/dev-tools`` source into
    a dedicated ``.dev-tools-actions`` workspace path. Jobs that use the shared
    PHP bootstrap can then prefer the consumer-local ``vendor/bin/dev-tools``
    when it exists and otherwise install a deterministic fallback runtime from
    that workflow-source checkout.

Wrapper Workflows
-----------------

Consumer repositories usually keep thin wrapper workflows in
``.github/workflows/`` that call the reusable workflows published by
``php-fast-forward/dev-tools``.

Example of an inherited workflow:

.. code-block:: yaml

    name: "Fast Forward Reports"
    uses: php-fast-forward/dev-tools/.github/workflows/reports.yml@main
    secrets: inherit

This approach ensures that all libraries in the ecosystem benefit from infrastructure updates without requiring manual changes to every repository.

First-party wrappers intentionally follow the reviewed ``@main`` workflow
contract so centrally deployed fixes reach consumers. This is an explicit
shared-infrastructure update policy, distinct from pinning third-party
actions. A workflow SHA alone would not freeze the existing action-source
checkout, which also follows DevTools ``main``; a fully immutable migration
would need to version both surfaces together.

The packaged wrappers currently include:

*   ``tests.yml``
*   ``reports.yml``
*   ``review.yml``
*   ``changelog.yml``
*   ``auto-resolve-conflicts.yml``
*   ``wiki.yml`` for pull-request wiki previews
*   ``wiki-maintenance.yml`` for merged-publication and cleanup work
*   ``auto-assign.yml``
*   ``label-sync.yml``

The optional ``resources/github-actions-optional/test-statuses.yml`` template
is kept outside this synchronized directory. It requires an explicit copy and
repository-specific verification; ``dev-tools:sync`` does not install it.

For the protected-branch-safe preview and publish model, see
:doc:`../advanced/branch-protection-and-bot-commits`.

The standard reusable workflows support workflow-only consumers without a
local ``fast-forward/dev-tools`` Composer dependency. The shared
``setup-composer`` action prefers the
consumer ``vendor/bin/dev-tools`` when it exists and otherwise exposes a
``dev-tools`` wrapper backed by the checked-out ``.dev-tools-actions`` source.

For Pages configured to use GitHub Actions, the optional
``reports-native.yml`` workflow requires consumer-local DevTools and a template
version of at least 2.1.0. Follow the `native Pages operator guide
<https://github.com/php-fast-forward/dev-tools/blob/main/resources/github-actions-optional/reports-native.md>`_
before adopting it. The legacy ``reports.yml`` workflow remains unchanged.

Dependabot Required Test Statuses
---------------------------------

The optional standalone ``test-statuses.yml`` workflow supplies commit-status
aliases for consumers whose branch protection requires unqualified names such
as ``Run Tests (8.3)``. The current first-party rollout targets twelve audited
consumer repositories with PHP 8.3, 8.4 and 8.5; this template does not infer an
arbitrary consumer's matrix or protection policy. Consumers protecting the
native qualified GitHub Actions checks do not need these aliases.

To opt in, copy
``resources/github-actions-optional/test-statuses.yml`` from a reviewed
DevTools checkout or installed package into the consumer repository as
``.github/workflows/test-statuses.yml``. Review these contracts before merging
the copied file:

* The ``workflow_run.workflows`` name and the PHP metadata check must match the
  source workflow name, currently ``Fast Forward Test Suite``.
* The source workflow path must match the API path check, currently
  ``.github/workflows/tests.yml``.
* The caller job name must match all job-name checks. The template expects
  ``tests / Run Tests (<version>)`` and control jobs prefixed with ``tests /``.
* ``EXPECTED_PHP_VERSIONS`` must list every expected version as JSON strings,
  currently ``["8.3","8.4","8.5"]``. Align those versions and the resulting
  ``Run Tests (<version>)`` contexts with branch protection.

A different caller prefix or matrix needs a reviewed adjustment to the copied
template. An additional observed test version, missing expected version,
ambiguous job or invalid attempt makes final publication fail closed; the
publisher must not silently mirror only a subset of the matrix.

After the file reaches the default branch, same-repository Dependabot ``push``
runs trigger it through ``workflow_run`` requested, in-progress and completed
events. Active attempts receive pending statuses, including reruns; terminal
attempts receive the actual per-version outcomes. A delayed start event reads
the current Run API state instead of overwriting completed results with pending.

The publisher does not check out source, install dependencies, retrieve
artifacts or caches, or run caller code. Its own job alone receives
``actions: read`` and ``statuses: write``. It verifies the source
repository, SHA, workflow ID/path/name, actor, event and attempt through
the Run API and rechecks that snapshot before writing. It validates the
configured matrix before publishing terminal results. Partial retries retain
completed results for unaffected versions and select the newest attempt for
rerun versions. A full rerun that fails or is cancelled before the matrix runs
must not reuse earlier successes: verified failed control jobs or a failed
source attempt produce failure statuses for blocked versions. Stale events and
superseded runs stop publication.

The target is the source push's verified ``head_sha``, never the
publisher's ``github.sha``, which points to the default branch.
Pull-request runs are deliberately excluded because they can test a
different merge commit; Dependabot's push run supplies this bridge.
Fork pull requests are outside this workflow's scope.

This bridge becomes active only after its file is merged into the default
branch; merely adding the template to a pull request does not activate its
lifecycle events. Verify a real Dependabot push and its required contexts after
deployment. API reads and status writes are separate operations: scheduling,
API availability and a state transition between requests prevent an atomic or
instantaneous update of every context.

Ordinary Required Test Statuses
-------------------------------

For ordinary opt-in runs, the reusable test workflow has a separate
checkout-free pending publisher after PHP version resolution. It verifies the
current run attempt and complete matrix, then marks all configured contexts
pending before the test matrix can start. Opt-out and Dependabot skips still
permit tests to run; Dependabot uses the optional lifecycle bridge described
above when its protection policy needs aliases.

If pending publication fails, the matrix is blocked and the final publisher
does not run. The final publisher requires successful pending publication and
an actual success or failure matrix outcome; skipped or cancelled matrices
cannot cause it to republish older successful jobs. Final results come from
GitHub job metadata for that exact run, selecting the newest attempt for each
version and requiring it to be completed. A failed-only retry can retain results from
unaffected versions, while incomplete or ambiguous newest results stop
publication.

Full reruns reexecute the ordered pending publisher. Rerunning only an
individual successful job may retain its successful ancestors and therefore
does not guarantee another pending publication. There is also a scheduling and
PHP-resolution gap before the pending job runs. A failed API read before its
first POST leaves existing statuses unchanged, and later API failures may leave
only some contexts updated. Commit-status mirroring does not provide an atomic
replacement of earlier results. Prefer native qualified checks when migrating
a repository's protection policy. See
:doc:`../advanced/branch-protection-and-bot-commits` for the permission ceiling
and bot-authored commit flow.

Fast Forward Reports
--------------------

The ``reports.yml`` workflow is responsible for generating technical documentation and quality reports.

**Triggers:**
*   Push to ``main``.
*   Pull Request (opened, synchronized, reopened, closed).
*   Manual trigger (workflow_dispatch).

**Behavior:**
*   **Main Branch**: Runs all checks and deploys the final reports to the root of the ``gh-pages`` branch.
    *   Runs a post-deploy health check against the published reports index and coverage URLs with retry/backoff to account for Pages propagation.
    *   Resolves the workflow PHP version from ``composer.lock`` or ``composer.json`` before installing dependencies.
    *   Removes ``.dev-tools/cache`` from the publish directory before deployment so repository-local tool caches never leak into GitHub Pages output.
    *   Appends a run summary with the published docs, coverage, and metrics URLs plus deployment verification status.
*   **Pull Requests**:
    *   Generates a **Preview** of the documentation, coverage, and metrics.
    *   Deploys the preview to ``gh-pages`` under ``previews/pr-{number}/``.
    *   Verifies the preview index and coverage URLs after deployment before posting preview links.
    *   Posts a **Sticky Comment** on the PR with links to the live preview, coverage report, and metrics site.
    *   Appends a run summary with preview URLs and verification status.
    *   Groups nested command output into collapsible GitHub Actions log sections so docs, tests, and metrics are easier to inspect independently.
    *   **Cleanup**: When a PR is closed, the workflow automatically removes the preview directory from the ``gh-pages`` branch to keep the repository clean.
    *   **Concurrency**: New pushes to the same PR cancel older in-progress preview runs without affecting other PRs.
*   **Scheduled Cleanup**: A scheduled/manual cleanup removes stale ``previews/pr-{number}/`` directories for already closed pull requests.
*   **Run Summary**: Closed-preview cleanup and orphan cleanup runs append a deterministic summary of the path or counts that were removed.

Fast Forward Wiki
-----------------

Wiki automation is split into two reusable workflows and two consumer
wrappers:

*   ``wiki.yml`` calls the reusable ``wiki-preview.yml`` workflow and is
    responsible only for pull-request preview updates.
*   ``wiki-maintenance.yml`` calls the reusable
    ``wiki-maintenance.yml`` workflow and is responsible for merged
    publication, closed-preview deletion, and scheduled cleanup.

**Behavior:**
*   **Submodule Management**: Both wiki workflows work against the
    ``.github/wiki`` submodule that points to the actual GitHub Wiki
    repository.
*   **Pull Request Preview**: ``wiki.yml`` updates a dedicated preview branch
    in the wiki repository named ``pr-{number}``.
*   **Preview Generation**: The preview workflow resolves the PHP version,
    installs dependencies, exposes the shared ``dev-tools`` runtime, runs
    ``dev-tools wiki -- --target=.github/wiki``,
    commits the generated Markdown into the wiki submodule, and then updates
    the parent repository's submodule pointer when needed.
*   **Preview Summary**: The preview workflow appends the preview branch name
    and whether the parent repository submodule pointer changed.
*   **Merged Publication**: ``wiki-maintenance.yml`` promotes the matching
    ``pr-{number}`` preview branch to ``master`` after a pull request is merged
    into ``main`` and validates the resulting remote SHA.
*   **Cleanup**: ``wiki-maintenance.yml`` deletes preview branches for closed
    pull requests and also performs scheduled cleanup for stale
    ``pr-{number}`` branches.
*   **Maintenance Summaries**: Publish and cleanup runs append the affected
    branch names or cleanup counts to the run summary.

.. note::
   See :doc:`../configuration/repository-setup` for mandatory initial setup required for the Wiki workflow to function.
   See :doc:`../advanced/branch-protection-and-bot-commits` for branch
   protection, bot commit, and submodule pointer conflict guidance.

Fast Forward Tests
------------------

The ``tests.yml`` workflow provides standard Continuous Integration.

*   Runs PHPUnit tests across the supported PHP matrix.
*   Resolves the minimum supported PHP minor version from ``composer.lock`` or ``composer.json`` and builds the test matrix from that floor upward.
*   Runs dependency health as a separate required job.
*   Defaults the dependency-health threshold to ``--max-outdated=-1`` so
    outdated packages stay visible in CI without failing the workflow on count
    alone.
*   Surfaces logged command failures as native GitHub Actions error annotations,
    including file and line metadata when the command provides them.
*   Writes a compact run summary with the resolved PHP-version source, test
    matrix, effective coverage threshold, and dependency-health threshold.
*   Uses PR-scoped concurrency so newer pushes cancel older in-progress runs for the same pull request.

Fast Forward Changelog
----------------------

The ``changelog.yml`` workflow validates pull-request changelog updates and
automates the release-preparation flow for repositories that use the local
changelog commands. Consumer repositories typically expose it through the thin
wrapper in ``resources/github-actions/changelog.yml``.

**Triggers:**
*   Pull Request (opened, synchronized, reopened).
*   Pull Request Target (closed) for merged release-preparation pull requests.
*   Manual trigger (workflow_dispatch).

**Behavior:**
*   **Pull Requests**:
    *   Resolves the workflow PHP version from ``composer.lock`` or
        ``composer.json`` before installing dependencies.
    *   Uses ``fetch-depth: 0`` so the base branch reference can be compared
        safely.
    *   Fetches the base branch changelog reference.
    *   For same-repository Dependabot pull requests, creates and pushes a
        minimal ``Unreleased`` changelog entry derived from the pull request
        title when the branch has not added one yet.
    *   Runs ``dev-tools changelog:check -- --against=<base-ref>`` against the base ref.
    *   Fails when a normal non-release branch does not add a meaningful ``Unreleased`` change.
    *   Skips the validation job for pull requests whose head branch matches the configured ``release-branch-prefix``, because release-preparation branches intentionally leave ``Unreleased`` empty after promotion.
    *   Publishes the aggregate changelog check for every active pull request.
        Direct workflow invocation uses ``Changelog Validation``; reusable
        workflow consumers typically expose it namespaced as
        ``changelog / Changelog Validation``. This is the branch-protection-safe
        context to require: it fails when normal changelog validation fails and
        succeeds for release-preparation branches where validation is
        intentionally skipped.
    *   Appends a run summary with the compared base ref and changelog file.
*   **Manual Release Preparation**:
    *   Checks out the repository default branch with full history.
    *   Resolves the next version from ``Unreleased`` unless a version input is provided.
    *   Promotes ``Unreleased`` into the selected version with the current UTC release date.
    *   Writes a release-notes preview file to ``.dev-tools/release-notes.md`` with
        ``dev-tools changelog:show -- <version>``.
    *   Opens or updates a release-preparation pull request instead of committing directly to ``main``.
    *   Dispatches ``tests.yml`` for the release branch with required-status
        mirroring enabled, because release branches are written by the workflow
        token and do not receive ordinary ``pull_request`` test runs
        automatically.
    *   Appends a run summary with the resolved version, version source, and pull-request URL.
    *   Requires repository Actions permissions that allow the workflow token
        to create pull requests and dispatch workflows.
*   **Merged Release Pull Requests**:
    *   Detects merged branches that match the configured release branch prefix.
    *   Renders the released changelog section with ``dev-tools changelog:show -- <version>``.
    *   Creates or updates the Git tag and GitHub release with the rendered changelog section as the release body.
    *   Appends a run summary with the published tag and release URL.
    *   Does **not** run for ordinary feature or fix pull requests merged into ``main``.

**Inputs:**
*   ``changelog-file``: managed changelog path, default ``CHANGELOG.md``.
*   ``version``: optional explicit version for manual release preparation.
*   ``release-branch-prefix``: release branch prefix, default ``release/v``.

**Repository prerequisites:**
*   Go to **Settings** > **Actions** > **General**.
*   Under **Workflow permissions**, enable **Read and write permissions**.
*   Enable **Allow GitHub Actions to create and approve pull requests**.
*   If either control is disabled or grayed out, the repository is likely constrained by organization-level policy or missing admin permission. In that case, an organization or repository admin must unlock the setting before manual release preparation can open a release pull request.
*   In branch protection, require the changelog workflow's aggregate context:

    - Direct workflow invocation: ``Changelog Validation``
    - Reusable workflow wrapper invocation: ``changelog / Changelog Validation``

    Use this instead of the internal ``changelog / Validate PR Changelog`` job.
    The internal job is intentionally skipped for release-preparation branches,
    while the aggregate context stays stable for normal and release pull
    requests.

.. note::
   Branch protection is not what blocks the release-preparation workflow from opening a pull request. Branch protection affects the merge of the ``release/v...`` pull request later in the flow. The gray or disabled workflow-permission controls come from repository permissions or organization policy.

Fast Forward Predictable Conflict Resolution
--------------------------------------------

The ``auto-resolve-conflicts.yml`` workflow scans pull requests after ``main``
moves and after pull request updates. It only attempts an automatic merge when
the conflicted file list is limited to:

*   ``.github/wiki``;
*   ``CHANGELOG.md``.

For ``.github/wiki`` conflicts, the workflow keeps the pull request side of the
submodule pointer because that pointer references the preview wiki branch that
belongs to the pull request. For ``CHANGELOG.md`` conflicts, it treats the base
branch changelog as authoritative and merges branch-only ``Unreleased`` entries
back into the current top-level ``Unreleased`` section. That keeps entries in
the right place even when a release was promoted while the pull request was
waiting.

If any other file is conflicted, or if GitHub reports a forked pull request
branch, the workflow stops and leaves the branch for manual resolution. Each
run writes a summary explaining which pull requests were resolved, skipped, or
left untouched.

Fast Forward Rigorous Review
----------------------------

The ``review.yml`` workflow standardizes how Fast Forward repositories request
high-signal pull-request review when a branch is no longer a draft.

**Triggers:**
*   Pull Request Target (ready_for_review).
*   Manual trigger (workflow_dispatch).

**Behavior:**
*   Resolves the target pull request from the lifecycle event or manual input.
*   Inspects the changed file list and infers high-signal review surfaces such
    as workflows, local actions, packaged skills, packaged agents, docs,
    changelog, wiki output, and source or test changes.
*   Writes a GitHub Actions step summary that points maintainers to the
    packaged ``review-guardian`` project agent and ``pull-request-review``
    skill.
*   Posts or updates a sticky pull-request comment with a deterministic review
    brief, sample changed files, and a ready-to-run prompt for the dedicated
    review agent.
*   Runs only when a pull request actually becomes ready for review, not on
    every draft update.

**Inputs:**
*   ``pull-request-number``: required only for manual dispatch.

**Repository permissions:**
*   ``contents: read``
*   ``pull-requests: write``

This workflow does not replace human review. It standardizes the review intake
moment and keeps the same review capability manually invokable for an already
open pull request.

Maintenance Workflows
---------------------

*   **Project Board Automation**:
    *   Supports both direct repository use and ``workflow_call`` wrappers in
        consumer repositories.
    *   Uses ``github.repository_owner`` to determine the owning organization
        or user.
    *   For ``php-fast-forward`` repositories, defaults to the first
        organization Project V2 when no explicit project number is provided.
    *   Consumer repositories SHOULD either pass ``project`` in the wrapper
        workflow or define ``PROJECT`` as a repository variable.
    *   The reusable workflows consistently resolve the board as
        ``inputs.project || vars.PROJECT || ''``. In practice that means:
        wrapper input wins, then the repository variable, and finally the
        php-fast-forward organization default when the reusable workflow
        supports it.
    *   Adds new issues to the project and moves them into ``Backlog``.
    *   Adds linked pull requests to the same project, mirrors milestone plus inferable project metadata, and keeps draft PRs in ``In progress`` until they are explicitly ready for review.
    *   Promotes approved pull requests into ``Ready to Merge``.
    *   Moves merged pull requests and linked issues into ``Merged``.
    *   Moves all currently ``Merged`` work into ``Release Prepared`` when ``changelog.yml`` opens or updates a release-preparation pull request.
    *   Promotes ``Release Prepared`` work into ``Released`` when the
        release-preparation pull request is merged and the GitHub release is
        published, and also promotes any remaining ``Merged`` items as a
        release-publication safety net when the preparation transition did not
        run.
    *   Uses the built-in workflow token for project updates.
*   **Label Sync**: Synchronizes repository labels with ecosystem standards.

Transient Failure Retry
-----------------------

This repository also keeps a local ``retry-transient-failures.yml`` workflow
that watches completed workflow runs and decides whether a failed run looks
like a transient GitHub-side infrastructure problem rather than a logic bug in
the workflow itself.

**Behavior:**
*   Runs only after one of the repository's core workflows finishes with a
    failure.
*   Inspects failed job logs for transient GitHub-side signatures such as
    checkout or fetch HTTP 500 failures, Git transport RPC errors, and related
    internal-server-error patterns.
*   Requests a rerun of failed jobs only when every failed job matches those
    transient signatures.
*   Stops after one rerun attempt, so repeated failures still surface clearly
    to maintainers.
*   Appends a deterministic summary describing whether a rerun was requested or
    skipped.

**Non-goals:**
*   It does not retry PHPUnit failures, lint failures, changelog validation,
    or other logic or quality-signal regressions.
*   It does not introduce unbounded rerun loops.
