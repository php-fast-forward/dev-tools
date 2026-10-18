Release and Publishing Workflow
===============================

Fast Forward Changelog owns fragment validation, version planning, central
history and optional tag/Release publication. DevTools delegates to that external
package.

Version PR and authorization
----------------------------

1. Contributions add independent ``.changelog/*.md`` fragments and pass the
   external ``Fast Forward Changelog`` status plus repository quality gates.
2. Accepted contributions merge into ``main``. The external lifecycle prepares
   or regenerates the single managed ``changelog/version`` PR with title
   ``chore: update changelog``.
3. Review the exact new head, planned version, notes, comparison links and
   consumed fragment deletions. The Bot owns the branch; do not edit it manually
   or fake its transaction evidence. Let automation regenerate after base moves.
4. Revalidate reviews, unresolved threads, mergeability and checks before any
   separately authorized merge. Green checks do not grant merge authority.
5. Publication is disabled by ``publish: false`` during adoption. Merging a
   version PR does not create a tag or GitHub Release under this configuration.
   Enable publication only through a separately approved workflow change.

The caller selects the upstream ``@v0`` channel with the official README
configuration unchanged: ``main``, historical source ``tags``, feedback
``always``, release preparation enabled and publication disabled. The external
defaults provide the Ubuntu runner and GitHub token. Local Changelog execution
is fixed to 0.2.0 through CPX and PHP 8.5, without changing the consumer PHP 8.3
requirement. The workflow uses Docker on Linux with Bash and Git instead of the
consumer Composer environment. Read :doc:`../usage/github-actions` for caller
contracts and :doc:`../commands/changelog` for local commands. The Changelog
status and independent repository tests must be inspected on the exact version
PR head; successful changelog validation does not prove that other CI ran.

Verification before publication
-------------------------------

Run the project gate against the reviewed candidate:

.. code-block:: bash

   composer install
   composer dev-tools

When guides or generated output change, inspect reports and wiki artifacts as
well. Test history-writing Changelog commands only in disposable checkouts,
using the selected exact runtime and a canonical GitHub origin. Confirm that
consolidation preserves historical notes, dates and correct links; creates one
new comparison definition in section order; adds no ``Unreleased`` section;
and deletes only accepted fragments. Local PR-origin lookup requires explicit
``--resolve-pull-requests`` to match automation's enabled setting.

Before authorized publication, inspect the immutable integrated SHA, tag
availability, version PR ownership and approved central bytes again. Never move
or recreate an existing tag to repair notes. A new release needs an approved new
identity and verified target. The external publication protocol recomputes notes
and origin evidence; mismatches require regeneration and renewed review.

Canonical release notes
-----------------------

``CHANGELOG.md`` is the canonical history after its maintenance PR is approved
and integrated. Extract each version with the external ``notes`` command:

.. code-block:: bash

   cpx --skip-local fast-forward/changelog:0.2.0 notes <version> --no-interaction

A GitHub Release body contains that version's notes, preserving Markdown and
links. Verify reference-style links in isolation and include only definitions
needed by that body. Do not append authors, raw commits, automation metadata or
a second generated change list.

Independent publication surfaces
--------------------------------

Packagist discovers ``fast-forward/dev-tools`` versions from repository tags.
After authorized publication, confirm the exact version is available and
installable from a clean consumer project:

.. code-block:: bash

   composer show fast-forward/dev-tools --all

Reports and generated documentation publish to GitHub Pages from ``main``.
The wiki workflow publishes generated API pages to the separate Wiki repository
from ``main``. These workflows retain their own permissions and ownership rules;
their successful runs do not authorize a tag or Release. Inspect their resulting
output when the accepted change affects documentation or reporting.

After authorized publication, confirm the tag target, reread release notes,
verify Packagist installation and inspect relevant Pages/Wiki output. Report
prepared, merged and remotely synchronized states separately.
