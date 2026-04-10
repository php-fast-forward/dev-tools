Consumer Automation
===================

FastForward DevTools plays two roles at once:

- producer: this repository ships reusable workflow templates and default
  configuration files;
- consumer helper: the ``dev-tools:sync`` command copies those assets and links
  packaged skills into other Fast Forward libraries.

Reusable Workflows Versus Consumer Stubs
----------------------------------------

.. list-table::
   :header-rows: 1

   * - Location
     - Role
   * - ``.github/workflows/*.yml``
     - Reusable workflows implemented in this repository.
   * - ``resources/github-actions/*.yml``
     - Small consumer stubs that call the reusable workflows through
       ``workflow_call``.
   * - ``.github/dependabot.yml``
     - This repository's own Dependabot configuration.
   * - ``resources/dependabot.yml``
     - Template copied into consumer repositories.
   * - ``.agents/skills/*``
     - Packaged agent skills linked into consumer repositories by the
       ``skills`` command.
   * - ``.github/wiki``
     - Generated Markdown API documentation locally and wiki submodule content
       in consumer repositories.

How GitHub Pages Publishing Works
---------------------------------

- ``.github/workflows/reports.yml`` runs ``composer dev-tools reports``.
- The workflow uploads ``public/`` as the Pages artifact.
- On the ``main`` branch, GitHub Pages serves the generated site.

How Wiki Publishing Works
-------------------------

- ``.github/workflows/wiki.yml`` runs
  ``composer dev-tools wiki -- --target=.github/wiki``.
- The workflow commits the wiki submodule contents.
- The parent repository then commits the updated submodule pointer.

Producer Impact
---------------

Any change to ``resources/github-actions``, ``resources/dependabot.yml``,
``.agents/skills``, ``.github/workflows``, or
``FastForward\DevTools\Command\SyncCommand`` changes the default onboarding
story for every consumer library.
