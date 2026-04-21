changelog commands
==================

Author, validate, infer, promote, and render managed Keep a Changelog
sections with the local changelog command set.

Description
-----------

Fast Forward DevTools ships a focused changelog surface built around the local
``CHANGELOG.md`` file:

- ``changelog:entry`` adds one categorized entry to ``Unreleased`` or to an
  explicit release section;
- ``changelog:check`` verifies that a branch or repository state contains a
  meaningful ``Unreleased`` change;
- ``changelog:next-version`` infers the next semantic version from the current
  ``Unreleased`` categories;
- ``changelog:promote`` publishes ``Unreleased`` into a dated release section;
- ``changelog:show`` renders one published section body for GitHub release
  notes or other automation.

These commands intentionally work on the minimum Keep a Changelog 1.1.0
structure managed by this repository and by synchronized Fast Forward consumer
repositories.

Usage
-----

.. code-block:: bash

   composer changelog:entry "Add changelog automation for release workflows (#28)"
   composer changelog:entry --type=fixed --release=1.2.0 --date=2026-04-19 "Preserve published release sections during backfill (#28)"

   composer changelog:check
   composer changelog:check --against=origin/main
   composer changelog:check --format=json

   composer changelog:next-version

   composer changelog:promote 1.3.0
   composer changelog:promote 1.3.0 --date=2026-04-19

   composer changelog:show 1.3.0

Options
-------

``changelog:entry``
   Supports:

   - ``--type=<category>`` for ``added``, ``changed``, ``deprecated``,
     ``removed``, ``fixed``, or ``security``;
   - ``--release=<version>`` to target a published section instead of
     ``Unreleased``;
   - ``--date=<YYYY-MM-DD>`` when a published section should carry a release
     date immediately;
   - ``--file=<path>`` to work with a changelog file other than
     ``CHANGELOG.md``.

``changelog:check``
   Supports:

   - ``--against=<git-ref>`` to compare the current changelog against a base
     branch or commit;
   - ``--file=<path>`` to validate another changelog path;
   - ``--format=<text|json>`` to switch between normal terminal output and a
     structured machine-readable payload.

``changelog:next-version``
   Supports:

   - ``--current-version=<semver>`` when the current published version should
     be supplied explicitly instead of inferred from the changelog;
   - ``--file=<path>`` to inspect another changelog path.

``changelog:promote``
   Supports:

   - the required version argument;
   - ``--date=<YYYY-MM-DD>`` to control the published release date;
   - ``--file=<path>`` to promote another changelog path.

``changelog:show``
   Supports:

   - the required version argument;
   - ``--file=<path>`` to render another changelog path.

Behavior
--------

- ``changelog:entry`` creates the managed changelog structure automatically
  when the selected file does not exist yet;
- ``changelog:check`` returns a failing exit code when ``Unreleased`` is empty
  or does not differ from the selected baseline ref;
- ``changelog:check --format=json`` emits a deterministic JSON payload with
  ``status``, ``message``, and ``context`` keys for automation consumers;
- ``changelog:next-version`` uses ``Removed`` or ``Deprecated`` entries for a
  major bump, ``Added`` or ``Changed`` entries for a minor bump, and otherwise
  falls back to a patch bump;
- ``changelog:promote`` restores an empty ``Unreleased`` section after
  publishing the requested version;
- ``changelog:show`` prints only the release body so workflows can feed it
  directly into GitHub release notes.

Workflow Integration
--------------------

The packaged changelog workflow consumes these commands in two places:

- pull-request validation runs ``changelog:check`` against the base branch to
  require a meaningful changelog update;
- manual release preparation uses ``changelog:next-version`` and
  ``changelog:promote`` to create a release pull request, then
  ``changelog:show`` to publish the merged section as GitHub release notes.

See :doc:`../usage/github-actions` and :doc:`../internals/release-publishing`
for the workflow-level behavior around those commands.
