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
     - Runs ``refactor``, ``phpdoc``, ``code-style``, and ``reports`` in
       order.
   * - Auto-fix what can be changed safely
     - ``composer dev-tools:fix``
     - Passes ``--fix`` through the supported subcommands.
   * - Refresh only test results
     - ``composer dev-tools tests``
     - Runs PHPUnit with the resolved ``phpunit.xml``.
   * - Bootstrap or repair changelog automation
     - ``composer dev-tools changelog:init``
     - Creates ``.keep-a-changelog.ini`` and missing ``CHANGELOG.md`` assets.
   * - Verify a pull request updated the changelog
     - ``composer dev-tools changelog:check``
     - Fails when the ``Unreleased`` section does not contain a meaningful
       note.
   * - Refresh only the documentation site
     - ``composer dev-tools docs``
     - Runs phpDocumentor using PSR-4 namespaces and the ``docs/`` guide.
   * - Refresh packaged agent skills only
     - ``composer dev-tools skills``
     - Creates or repairs symlinks in ``.agents/skills``.
   * - Publish local automation defaults into a consumer repository
     - ``composer dev-tools:sync``
     - Updates scripts, copies missing automation assets, and refreshes
       packaged skills.
   * - Regenerate wiki pages
     - ``composer dev-tools wiki``
     - Builds Markdown API pages in ``.github/wiki``.

Choose the Right Entry Point
----------------------------

Use ``composer dev-tools ...`` when you want the same command vocabulary across
repositories.

Use ``vendor/bin/dev-tools ...`` when you need to pass several options and want
to avoid Composer's ``--`` forwarding rules.

A Safe Beginner Routine
-----------------------

1. Run ``composer dev-tools tests``.
2. Run ``composer dev-tools changelog:check`` before opening a pull request.
3. Run ``composer dev-tools skills`` if you changed packaged consumer skills.
4. Run ``composer dev-tools docs`` if you changed guides or public APIs.
5. Run ``composer dev-tools:fix`` when you want automated help.
6. Run ``composer dev-tools`` before pushing.

.. tip::

   The ``standards`` command does not stop after the first failure. It attempts
   every stage and returns a failing exit code when at least one stage failed.
