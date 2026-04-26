Common Workflows
================

Most day-to-day work falls into one of the flows below.

.. list-table::
   :header-rows: 1

   * - Goal
     - Command
     - What happens
   * - Check everything before a pull request
     - ``composer dev-tools``
     - Runs ``refactor``, ``docheader``, ``code-style``, and ``reports`` in
       order.
   * - Auto-fix what can be changed safely
     - ``composer dev-tools:fix``
     - Passes ``--fix`` through the supported subcommands.
   * - Refresh only test results
     - ``composer tests``
     - Runs PHPUnit with the resolved ``phpunit.xml``.
   * - Refresh only the documentation site
     - ``composer docs``
     - Runs phpDocumentor using PSR-4 namespaces and the ``docs/`` guide.
   * - Record a notable pull-request change
     - ``composer changelog:entry``
     - Appends one categorized entry to ``Unreleased`` or to a selected
       published version section.
   * - Refresh packaged agent skills only
     - ``composer skills``
     - Creates or repairs symlinks in ``.agents/skills``.
   * - Refresh packaged project agents only
     - ``composer agents``
     - Creates or repairs symlinks in ``.agents/agents``.
   * - Trigger a rigorous review pass for a ready pull request
     - Mark the pull request ready for review or run ``review.yml`` manually
     - Posts a deterministic review brief that points maintainers to the
       ``review-guardian`` agent and ``pull-request-review`` skill.
   * - Prepare a release from the current changelog
     - ``composer changelog:next-version`` then ``composer changelog:promote``
     - Infers the next semantic version, publishes ``Unreleased``, and leaves
       the released section ready for GitHub release automation.
   * - Publish local automation defaults into a consumer repository
     - ``composer dev-tools:sync``
     - Updates scripts, copies missing automation assets, and refreshes
       packaged skills and packaged agents.
   * - Regenerate wiki pages
     - ``composer wiki``
     - Builds Markdown API pages in ``.github/wiki``.

Choose the Right Entry Point
----------------------------

Use ``composer ...`` when you want the same command vocabulary across
repositories.

Use ``vendor/bin/dev-tools ...`` when you need to pass several options and want
to avoid Composer's ``--`` forwarding rules.

A Safe Beginner Routine
-----------------------

1. Run ``composer tests``.
2. Run ``composer changelog:entry`` when the branch introduces a notable
   user-facing or automation-facing change.
3. Run ``composer skills`` if you changed packaged consumer skills.
4. Run ``composer agents`` if you changed packaged project-agent prompts.
5. Run ``composer docs`` if you changed guides or public APIs.
6. Run ``composer dev-tools:fix`` when you want automated help.
7. Run ``composer dev-tools`` before pushing.

.. tip::

   The ``standards`` command does not stop after the first failure. It attempts
   every stage and returns a failing exit code when at least one stage failed.
