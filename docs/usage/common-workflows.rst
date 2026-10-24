Common Workflows
================

Most day-to-day work falls into one of the flows below.

.. list-table::
   :header-rows: 1

   * - Goal
     - Command
     - What happens
   * - Check everything before a pull request
     - ``cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" standards``
     - Runs ``refactor``, ``phpdoc``, ``code-style``, and ``reports`` in
       order.
   * - Auto-fix what can be changed safely
     - ``cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" standards --fix``
     - Passes ``--fix`` through the supported subcommands.
   * - Refresh only test results
     - ``cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" tests``
     - Runs PHPUnit with the resolved ``phpunit.xml``.
   * - Refresh only the documentation site
     - ``cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" docs``
     - Runs phpDocumentor using PSR-4 namespaces and the ``docs/`` guide.
   * - Record a notable pull-request change
     - ``cpx --skip-local fast-forward/changelog add "Describes the change." --no-interaction``
     - Creates one independent change fragment using the external CPX runtime.
   * - Refresh procedural skills
     - ``composer skills:update --dry-run`` then ``composer skills:update``
     - LLM Skills previews and installs the configured skill sources.
   * - Trigger a rigorous review pass for a ready pull request
     - Mark the pull request ready for review or run ``review.yml`` manually
     - Posts a deterministic review brief that points maintainers to the
       ``review-guardian`` agent and ``fast-forward-pull-request-review`` skill.
   * - Inspect the next release
     - ``cpx --skip-local fast-forward/changelog status --source=tags --no-interaction``
     - Plans from pending fragments; the managed version PR consolidates them
       for review. Merging the reviewed managed version PR automatically
       publishes its tag and GitHub Release at the approved commit.
   * - Publish local automation defaults into a consumer repository
     - ``cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" dev-tools:sync``
     - Updates metadata, scripts and managed repository assets explicitly.
   * - Regenerate wiki pages
     - ``cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" wiki``
     - Builds Markdown API pages in ``.github/wiki`` by default; normal sync
       explicitly uses ``.dev-tools/wiki-generated`` without a submodule.

Choose the Right Entry Point
----------------------------

Use pinned CPX commands for consumers. ``php bin/dev-tools`` runs the checked-out
DevTools source. Install instructions independently with LLM Skills.

A Safe Beginner Routine
-----------------------

1. Run ``cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" tests``.
2. Run ``cpx --skip-local fast-forward/changelog add`` with a description when the branch
   introduces a notable change (requires CPX and PHP 8.5); then run
   ``cpx --skip-local fast-forward/changelog check``.
3. Preview LLM Skills updates when instruction sources change.
5. Run ``cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" docs`` if you changed guides or public APIs.
6. Run ``cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" standards --fix`` when you want automated help.
7. Run ``cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" standards`` before pushing.

.. tip::

   The ``standards`` command does not stop after the first failure. It attempts
   every stage and returns a failing exit code when at least one stage failed.
