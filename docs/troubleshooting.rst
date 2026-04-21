Troubleshooting
===============

Use this page when ``fast-forward/dev-tools`` fails locally or in GitHub
Actions. Each entry starts with the visible symptom, then lists likely causes
and safe recovery steps.

Composer Install or Authentication Failures
-------------------------------------------

Scope: local and CI.

Symptoms:

- ``composer install`` cannot download a private package;
- GitHub Actions reports an authentication or rate-limit error;
- Composer asks for credentials in a non-interactive job.

Likely causes:

- the repository is missing ``COMPOSER_AUTH`` or a GitHub token secret;
- the token does not have access to a private dependency;
- Composer is trying to prompt in CI.

Recovery:

.. code-block:: bash

    composer diagnose
    composer install -vvv

In CI, check that the workflow receives the expected secrets and that private
repositories are available to the token used by Composer. Prefer repository or
organization secrets over hard-coded credentials.

Missing ``vendor/`` or Lock-File Mismatch
-----------------------------------------

Scope: local and CI.

Symptoms:

- ``composer dev-tools`` fails because a binary is missing;
- CI installs a dependency set that differs from the local machine;
- Composer reports that ``composer.lock`` is not compatible with
  ``composer.json``.

Likely causes:

- ``vendor/`` was deleted or was created with a different PHP version;
- ``composer.json`` changed without updating ``composer.lock``;
- the local PHP version does not match the repository constraint.

Recovery:

.. code-block:: bash

    php -v
    composer validate
    composer install

If Composer reports a lock mismatch, update the lock file intentionally and
review the dependency diff:

.. code-block:: bash

    composer update --lock
    git diff composer.lock

Branch Protection or Bot Commit Blocks
--------------------------------------

Scope: GitHub Actions.

Symptoms:

- a wiki or reports preview job cannot push generated updates;
- GitHub rejects a bot commit with a protected-branch error;
- a pull request is mergeable locally but blocked after the preview job runs.

Likely causes:

- ``main`` is protected and the workflow tried to push there directly;
- the workflow token cannot update pull request branches;
- required status checks were changed after the pull request opened.

Recovery:

- confirm that preview jobs write to pull request branches or PR-specific
  preview locations;
- keep final publishing jobs tied to merges into ``main``;
- allow workflow-generated commits on pull request branches when the repository
  relies on generated wiki pointers;
- rerun the workflow after branch protection settings are corrected.

``.github/wiki`` Submodule Pointer Conflicts
--------------------------------------------

Scope: local and CI.

Symptoms:

- Git reports a conflict on ``.github/wiki``;
- a pull request contains an unexpected submodule pointer change;
- the wiki preview branch exists, but the parent repository points elsewhere.

Likely causes:

- ``main`` moved while the pull request had a generated wiki pointer;
- the preview workflow produced a newer wiki commit;
- the local submodule is stale.

Recovery for pull request ``123``:

.. code-block:: bash

    git fetch origin main
    git rebase origin/main
    git -C .github/wiki fetch origin master pr-123
    git -C .github/wiki switch --detach origin/pr-123
    git add .github/wiki
    git rebase --continue
    git push --force-with-lease

If the preview branch no longer exists, regenerate the wiki preview before
choosing a pointer.

No-TTY Process Errors
---------------------

Scope: local scripts and CI.

Symptoms:

- a process fails with ``/dev/tty`` errors;
- a command works in an interactive terminal but fails in GitHub Actions;
- Composer or a wrapped tool waits for input forever.

Likely causes:

- a command expects an interactive terminal;
- a tool is prompting for confirmation;
- CI is missing required environment variables.

Recovery:

.. code-block:: bash

    composer dev-tools -- --no-interaction

When calling lower-level tools directly, use their non-interactive flags and
provide required values through environment variables or workflow inputs.

GitHub Actions Error Annotations
--------------------------------

Scope: reusable workflows and CI runs executed on GitHub Actions.

Behavior:

- command failures logged through DevTools are also emitted as native GitHub
  Actions error annotations;
- when a command includes ``file`` and ``line`` context, the annotation is tied
  to that source location in the workflow UI;
- orchestrated commands may also appear inside collapsible workflow groups to
  separate nested subprocess logs.

Coverage Threshold Failures
---------------------------

Scope: local and CI.

Symptoms:

- PHPUnit passes tests but fails the job because coverage is too low;
- a new class or branch is missing coverage metadata;
- coverage reports differ between local and CI runs.

Likely causes:

- new behavior was added without tests;
- Xdebug or PCOV is not enabled consistently;
- coverage configuration changed in ``phpunit.xml``.

Recovery:

.. code-block:: bash

    composer dev-tools tests
    composer dev-tools tests -- --coverage=.dev-tools/coverage

Add focused tests for the changed behavior before lowering thresholds. If local
coverage differs from CI, compare PHP, Xdebug or PCOV, and PHPUnit versions.

PHPDoc or Template Generation Issues
------------------------------------

Scope: local and CI.

Symptoms:

- ``composer dev-tools docs`` fails during API documentation generation;
- generated docs are missing classes or links;
- PHPDoc validation fails after a source change.

Likely causes:

- a class is missing required PHPDoc metadata;
- a docblock references a renamed class or method;
- templates or generated output from an older run are stale.

Recovery:

.. code-block:: bash

    composer dev-tools phpdoc
    composer dev-tools docs

Fix source PHPDoc first, then regenerate docs. Avoid editing generated API
output directly; it should reflect source files and templates.

Reports or Wiki Preview Cleanup Issues
--------------------------------------

Scope: GitHub Actions.

Symptoms:

- closed pull requests still show old report preview links;
- a wiki preview branch remains after merge or close;
- a PR comment points to a missing preview.

Likely causes:

- the cleanup workflow did not run on the pull request close event;
- the workflow token lacks permission to update Pages or the wiki repository;
- a preview was removed after the comment was posted.
- a reports preview cleanup run skipped a preview because the pull request
  number could not be resolved.
- the wiki publish validation detected that remote ``master`` does not match
  the preview branch SHA.

Recovery:

- rerun the cleanup workflow for the closed pull request when available;
- remove stale preview directories or branches only after confirming the pull
  request is closed or merged;
- use the scheduled wiki cleanup workflow to remove leftover ``pr-<number>``
  branches for pull requests that are already closed;
- use the scheduled reports cleanup workflow to remove leftover
  ``previews/pr-<number>/`` directories for pull requests that are already
  closed;
- keep the wiki preview branch until the publish validation log shows matching
  expected and actual SHAs;
- check the reports and wiki workflow logs before deleting artifacts manually.

Related References
------------------

- :doc:`usage/github-actions`
- :doc:`usage/documentation-workflows`
- :doc:`usage/syncing-consumer-projects`
- :doc:`running/index`
