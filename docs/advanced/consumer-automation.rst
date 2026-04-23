Consumer Automation
===================

FastForward DevTools plays two roles at once:

- producer: this repository ships reusable workflow templates and default
  configuration files;
- consumer helper: the ``dev-tools:sync`` command copies those assets and links
  packaged skills and packaged project agents into other Fast Forward
  libraries.

Reusable Workflows Versus Consumer Stubs
----------------------------------------

This repository now separates reusable workflow entrypoints from the local
composite and script actions that implement them. Consumer repositories still
receive thin wrappers from ``resources/github-actions/*.yml``, but the reusable
implementation in this repository is increasingly composed from local actions in
``.github/actions/``.

.. list-table::
   :header-rows: 1

   * - Location
     - Role
   * - ``.github/workflows/*.yml``
     - Reusable workflows and entry workflows implemented in this repository.
   * - ``resources/github-actions/*.yml``
     - Small consumer stubs that call the reusable workflows through
       ``workflow_call``.
   * - ``.github/actions/php/*``
     - Shared PHP helpers such as workflow PHP-version resolution and Composer
       setup.
   * - ``.github/actions/changelog/*``
     - Changelog-specific building blocks for release version resolution,
       release-notes rendering, and GitHub release publication.
   * - ``.github/actions/github-pages/*``
     - Shared GitHub Pages helpers for restoring previews, removing closed pull
       request previews, cleaning orphaned previews, and verifying published
       URLs.
   * - ``.github/actions/project-board/*``
     - Reusable project-automation actions for resolving the target board,
       inferring review status, syncing linked metadata, and transitioning
       items through repository delivery states.
   * - ``.github/actions/review/*``
     - Reusable helpers that render deterministic rigorous-review briefs for
       pull requests that just became ready for review.
   * - ``.github/actions/summary/*``
     - Shared helpers that append deterministic Markdown outcomes to
       ``GITHUB_STEP_SUMMARY`` after workflows already know their final URLs,
       refs, versions, or verification results.
   * - ``.github/actions/review/*``
     - Reusable helpers that render deterministic rigorous-review briefs for
       pull requests that just became ready for review.
   * - ``.github/actions/summary/*``
     - Shared helpers that append deterministic Markdown outcomes to
       ``GITHUB_STEP_SUMMARY`` after workflows already know their final URLs,
       refs, versions, or verification results.
   * - ``.github/actions/wiki/*``
     - Wiki-specific helpers for preparing preview branches, promoting preview
       content to ``master``, validating publication, and cleaning stale
       preview branches.
   * - ``.github/dependabot.yml``
     - This repository's own Dependabot configuration.
   * - ``resources/dependabot.yml``
     - Template copied into consumer repositories.
   * - ``.agents/skills/*``
     - Packaged agent skills linked into consumer repositories by the
       ``skills`` command.
   * - ``.agents/agents/*``
     - Packaged project-agent prompts linked into consumer repositories by
       the ``agents`` command.
   * - ``.github/wiki``
     - Generated Markdown API documentation locally and wiki submodule content
       in consumer repositories.

How GitHub Pages Publishing Works
---------------------------------

- ``.github/workflows/reports.yml`` runs ``composer dev-tools reports``.
- The workflow delegates repeated GitHub Pages tasks to
  ``.github/actions/github-pages/*`` instead of keeping that shell logic inline.
- Pull requests publish previews under ``previews/pr-<number>/``.
- On the ``main`` branch, GitHub Pages serves the generated site from the root
  of the Pages branch.

How Wiki Publishing Works
-------------------------

Wiki automation now has two distinct entry workflows:

- ``.github/workflows/wiki.yml`` handles pull-request preview generation by
  calling ``.github/workflows/wiki-preview.yml``.
- ``.github/workflows/wiki-maintenance-entry.yml`` handles post-merge
  publication, closed-pull-request cleanup, and scheduled orphan cleanup by
  calling ``.github/workflows/wiki-maintenance.yml``.

Within those reusable workflows, the lower-level branch preparation, publish
validation, and cleanup steps live under ``.github/actions/wiki/*``.

In practice:

- pull requests publish generated wiki content to branches such as ``pr-123``;
- the parent repository then commits the updated submodule pointer to the pull
  request branch;
- after merge, the maintenance workflow promotes the preview content to the
  wiki ``master`` branch;
- closed pull requests and the scheduled maintenance lane remove stale preview
  branches.

How Changelog and Project Automation Fit In
-------------------------------------------

- ``.github/workflows/changelog.yml`` now composes its release flow from
  ``.github/actions/changelog/*`` and ``.github/actions/project-board/*``.
- The changelog workflow validates pull-request changelog entries, prepares
  release branches, publishes GitHub releases from merged release branches, and
  can transition the configured GitHub Project item state alongside those
  lifecycle events.
- ``.github/workflows/review.yml`` composes its ready-for-review intake from
  ``.github/actions/review/*`` and posts a deterministic brief that points
  maintainers to the packaged ``review-guardian`` agent and
  ``pull-request-review`` skill.
- ``tests.yml``, ``reports.yml``, wiki preview and maintenance flows, and
  ``changelog.yml`` now delegate final run summaries to
  ``.github/actions/summary/*`` after their final-state information is known.
- Project-board automation is no longer just an inline workflow concern; it is
  a reusable local action group shared by issue, pull-request, review, and
  release automation.

See :doc:`branch-protection-and-bot-commits` for the branch protection model,
bot commit behavior, and ``.github/wiki`` conflict resolution steps.

Producer Impact
---------------

Any change to ``resources/github-actions``, ``resources/dependabot.yml``,
``.agents/skills``, ``.agents/agents``, ``.github/workflows``, or
``FastForward\DevTools\Console\Command\SyncCommand`` changes the default onboarding
story for every consumer library.
