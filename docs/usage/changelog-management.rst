Changelog Management
====================

FastForward DevTools now bootstraps and enforces
`Keep a Changelog <https://keepachangelog.com/en/1.1.0/>`_ workflows using
``phly/keep-a-changelog``.

Bootstrap Once
--------------

Run the bootstrap command when a repository does not yet have changelog assets:

.. code-block:: bash

   composer dev-tools changelog:init

The command creates:

- ``.keep-a-changelog.ini`` with local provider defaults;
- ``CHANGELOG.md`` generated from local release tags when the file is missing;
- an ``Unreleased`` section when the changelog exists but no longer tracks
  pending work.

Validate Pull Requests
----------------------

Use the validation command locally or in CI to ensure the ``Unreleased``
section contains a real note:

.. code-block:: bash

   composer dev-tools changelog:check
   vendor/bin/dev-tools changelog:check --against=origin/main

When ``--against`` is provided, the command compares the current
``Unreleased`` entries with the baseline reference and fails when no new entry
was added.

Use the Upstream Tooling
------------------------

FastForward DevTools keeps the official ``keep-a-changelog`` binary available
for entry creation and release promotion:

.. code-block:: bash

   keep-a-changelog entry:added "Document changelog automation"
   keep-a-changelog unreleased:promote 1.5.0
   keep-a-changelog version:release 1.5.0 --provider-token="$GH_TOKEN"

The synchronized Composer scripts expose the most common flows:

- ``composer dev-tools:changelog:init``
- ``composer dev-tools:changelog:check``
- ``composer dev-tools:changelog:promote -- 1.5.0``
- ``composer dev-tools:changelog:release -- 1.5.0 --provider-token=...``

Reusable Workflows
------------------

The sync command now copies three reusable workflow stubs into consumer
repositories:

- ``changelog-bump.yml`` bootstraps ``CHANGELOG.md`` and local config on
  ``main``;
- ``require-changelog.yml`` blocks pull requests without a meaningful
  ``Unreleased`` entry;
- ``release.yml`` promotes ``Unreleased`` notes to the released version and
  updates GitHub release notes from ``CHANGELOG.md``.
