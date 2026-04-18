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
- Pull requests publish previews under ``previews/pr-<number>/``.
- On the ``main`` branch, GitHub Pages serves the generated site from the root
  of the Pages branch.

How Wiki Publishing Works
-------------------------

- ``.github/workflows/wiki.yml`` runs
  ``composer dev-tools wiki -- --target=.github/wiki``.
- Pull requests publish generated wiki content to branches such as
  ``pr-123``.
- The parent repository then commits the updated submodule pointer to the pull
  request branch.
- After merge, the preview content is promoted to the wiki ``master`` branch.

See :doc:`branch-protection-and-bot-commits` for the branch protection model,
bot commit behavior, and ``.github/wiki`` conflict resolution steps.

Producer Impact
---------------

Any change to ``resources/github-actions``, ``resources/dependabot.yml``,
``.agents/skills``, ``.github/workflows``, or
``FastForward\DevTools\Console\Command\SyncCommand`` changes the default onboarding
story for every consumer library.
