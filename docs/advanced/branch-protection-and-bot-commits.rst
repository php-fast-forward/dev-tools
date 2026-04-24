Branch Protection and Bot Commits
=================================

Fast Forward repositories keep generated documentation out of protected
branches until the related pull request is merged. This keeps ``main`` stable,
lets maintainers review generated output before it becomes canonical, and
avoids requiring automation to bypass branch protection for normal preview
updates.

The model has two publishing lanes:

- pull requests publish isolated previews;
- merges into ``main`` publish the final wiki and reports.

For wiki automation, those lanes are now split across separate reusable
workflows:

- ``.github/workflows/wiki-preview.yml`` handles preview generation for pull
  requests and is triggered through ``.github/workflows/wiki.yml``;
- ``.github/workflows/wiki-maintenance.yml`` handles post-merge promotion,
  closed-pull-request cleanup, and scheduled orphan cleanup and is triggered
  through ``.github/workflows/wiki-maintenance-entry.yml``.

Wiki Preview Lifecycle
----------------------

The wiki workflow writes generated Markdown to the GitHub wiki repository, but
it does not publish pull request content directly to the wiki ``master`` branch.
Instead, each pull request receives a dedicated wiki branch.

For pull request ``123`` the workflow:

1. runs ``composer dev-tools wiki -- --target=.github/wiki``;
2. commits the generated wiki content to the wiki branch ``pr-123``;
3. updates the parent repository submodule pointer at ``.github/wiki``;
4. commits that pointer update back to the pull request branch.

The parent repository commit is important because reviewers can see exactly
which generated wiki revision belongs to the pull request. The bot commit SHOULD
stay on the pull request branch because protected ``main`` branches usually
reject direct commits.

After the pull request is merged into ``main``, the maintenance workflow copies
the content from the wiki preview branch, such as ``pr-123``, to the wiki
``master`` branch. That makes the reviewed wiki content live only after the
source code merge is complete. The maintenance lane validates that remote
``master`` points to the expected preview commit before it deletes the
``pr-123`` branch because the preview branch is the last rollback source for
that generated content.

If the pull request is closed without merge, the maintenance workflow deletes
the matching wiki preview branch without promoting it to ``master``. A
scheduled cleanup lane also scans existing ``pr-<number>`` wiki branches and
removes branches whose pull requests are already closed.

Reports Preview Lifecycle
-------------------------

Reports use the same review-before-publish idea, but the output is served from
GitHub Pages instead of the wiki repository.

On pull requests, the reports workflow:

- generates docs, coverage, and report assets from the pull request code;
- publishes them under ``previews/pr-<number>/`` in the Pages branch;
- comments on the pull request with preview links when possible;
- cancels older in-progress preview runs when a newer push updates the same
  pull request;
- keeps workflow artifacts available as a fallback when Pages publishing or the
  comment update is unavailable.

On ``main``, the reports workflow publishes the final site at the root of the
Pages branch. Pull request previews use PR-specific directories, so multiple
open pull requests can have independent previews without overwriting each other.

When a pull request is closed, the workflow SHOULD remove its preview directory.
This prevents stale documentation and coverage reports from looking like active
review artifacts. A scheduled cleanup also scans ``previews/pr-<number>/``
directories and removes preview directories whose pull requests are already
closed.

Branch Protection Interactions
------------------------------

Protected branches usually block direct pushes, require status checks, and may
require signed commits or linear history. The preview workflows are designed to
respect those rules:

- bot commits update the pull request branch, not ``main``;
- final wiki publication runs after the merge into ``main``;
- final reports publication runs from the already-accepted ``main`` revision;
- preview branches such as ``pr-123`` are temporary review targets.

If a repository restricts bot pushes to pull request branches, maintainers
should either allow the workflow token to update PR branches or require authors
to refresh generated pointers manually. The preferred path is to allow bot
updates on PR branches while keeping ``main`` protected.

Required test checks must still report for workflow-managed pointer commits.
The tests workflow therefore triggers on every pull request update without
top-level path filters. This ensures GitHub always creates the required
``Run Tests`` matrix checks for ordinary pull request updates.

Workflow-managed ``.github/wiki`` pointer commits need one extra step. GitHub
does not start another ``pull_request`` or ``push`` workflow run for commits
pushed with the built-in workflow token. After the wiki preview workflow commits
a parent-repository pointer update, it explicitly dispatches ``tests.yml`` for
the pull request head branch so the newest bot-authored commit receives the
required ``Run Tests`` matrix checks. Because manually dispatched workflow check
runs are not always treated as pull-request required checks, that dispatched
test run also mirrors the matrix result into commit statuses named
``Run Tests (8.3)``, ``Run Tests (8.4)``, and ``Run Tests (8.5)``. Test workflow
concurrency cancels older in-progress runs for the same pull request so the
newest commit owns the required check contexts.

The predictable-conflict workflow MAY also refresh pull request branches when
the only conflicts are ``.github/wiki`` pointer drift and/or ``CHANGELOG.md``
``Unreleased`` drift. It keeps pull request wiki preview pointers on the branch
side and replays branch-only changelog entries into the current base
``Unreleased`` section, which avoids placing new entries under a freshly
published release after ``main`` moved.

At a high level, the workflows need permission to read repository contents,
write generated preview commits, update pull request comments, and publish Pages
content. Keep those permissions scoped to the workflow jobs that actually need
them.

Workflow Permission Scope
-------------------------

The reusable workflows default to read-only repository access and grant write
permissions at the job level when generated content must be pushed or pull
requests must be updated.

``tests.yml`` needs ``contents: read`` because it checks out code, installs
dependencies, and runs PHPUnit. It also declares ``statuses: write`` so
workflow-dispatched test runs can mirror required matrix contexts onto
bot-authored wiki pointer commits.

``reports.yml`` keeps ``contents: write`` on jobs that publish or clean
``gh-pages`` content. The pull request preview comment runs as a separate job
with ``pull-requests: write`` because it posts or updates the sticky preview
comment. Scheduled preview cleanup uses ``pull-requests: read`` so it can
distinguish open pull requests from closed or merged ones before deleting
``previews/pr-<number>`` directories.

``wiki.yml`` is now preview-only, so its called workflow keeps
``contents: write`` for wiki preview commits and parent-repository submodule
pointer updates, ``actions: write`` to dispatch ``tests.yml`` after bot-authored
pointer commits, and ``pull-requests: read`` to inspect pull request metadata
safely.

``wiki-maintenance-entry.yml`` and ``wiki-maintenance.yml`` keep
``contents: write`` for wiki publication and cleanup tasks, and
``pull-requests: read`` on jobs that need to distinguish merged, closed, and
open pull requests before publishing or deleting preview branches.

The label synchronization workflow declares ``issues: read`` to copy labels from
the linked issue and ``pull-requests: write`` to apply those labels to the pull
request. The project-board automation workflow keeps ``issues: write`` and
``pull-requests: write`` because assignment and board-state changes are write
operations on both event types, and it also declares
``repository-projects: write`` so linked issues and pull requests can be added
to the configured GitHub Project and moved through the delivery pipeline.

To enable reusable project automation in consumer repositories, pass
``project`` through the thin ``workflow_call`` wrapper or configure the
repository variable ``PROJECT`` so the workflow can resolve the target GitHub
Project without hardcoding repository-specific board identifiers into the
packaged workflow itself. The workflow derives the owner from
``github.repository_owner`` and uses the built-in workflow token for the actual
project mutations. Inside ``php-fast-forward`` repositories, the reusable
workflow MAY fall back to the first organization Project V2 when no explicit
project number is provided. The reusable project-board helpers now live under
``.github/actions/project-board/*``, which keeps project-state transitions
shared across issue, pull-request, review, changelog, and release workflows.

Resolving ``.github/wiki`` Pointer Conflicts
--------------------------------------------

Submodule pointer conflicts happen when ``main`` and the pull request point to
different generated wiki commits. The predictable-conflict workflow can resolve
this automatically when the conflict scope is limited to ``.github/wiki`` and
``CHANGELOG.md``. When resolving it manually, rebase the pull request and choose
the preview wiki commit that belongs to the pull request.

For pull request ``123``:

.. code-block:: bash

    git fetch origin main
    git rebase origin/main
    git -C .github/wiki fetch origin master pr-123
    git -C .github/wiki switch --detach origin/pr-123
    git add .github/wiki
    git rebase --continue
    git push --force-with-lease

If the conflict appears after the preview job has already produced a newer bot
commit, prefer the latest ``pr-123`` wiki commit. If the wiki branch no longer
exists because the pull request was closed or merged, rerun the wiki workflow or
regenerate the wiki locally before updating the pointer.

Operational Checklist
---------------------

- Keep source changes and generated pointer updates in the same pull request.
- Review PR preview links before merging documentation-heavy changes.
- Merge only through the protected ``main`` flow.
- Let the post-merge wiki job publish to ``master``.
- Treat wiki preview and wiki maintenance as separate automation concerns when
  debugging workflow failures.
- Let closed pull requests and scheduled cleanup remove wiki preview branches.
- Let closed pull requests and scheduled cleanup remove report preview
  directories.

See :doc:`consumer-automation` for how reusable workflows and consumer stubs fit
into this model, and :doc:`../usage/github-actions` for the workflow summary.
