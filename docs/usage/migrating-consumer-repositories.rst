Migrating Consumer Repositories
===============================

Use this guide when an existing PHP package repository is adopting
``fast-forward/dev-tools`` for the first time. The goal is to introduce the
shared Composer scripts, workflow stubs, wiki setup, skills, and repository
defaults in a reviewable pull request.

This page complements :doc:`syncing-consumer-projects`; it focuses on the
migration path instead of the full command reference.

Preflight Checks
----------------

Before installing DevTools, confirm the repository has:

- a committed ``composer.json`` with the package name, PHP constraint, license,
  and autoload settings already reviewed;
- a clean working tree;
- a configured ``origin`` remote that points to the GitHub repository;
- agreement on whether the repository should publish GitHub Pages reports and a
  GitHub Wiki;
- a maintainer who can review workflow, Dependabot, and branch protection
  changes.

If the repository already has custom workflows or generated documentation, list
the files that must be preserved before running sync. The sync command protects
many existing files by default, but maintainers should still review the outcome
as infrastructure code.

Install DevTools
----------------

Install the package as a development dependency:

.. code-block:: bash

    composer require --dev fast-forward/dev-tools

Commit the Composer changes separately only if the repository prefers very small
review steps. For most migrations, it is reasonable to keep the Composer update
and generated sync changes in the same pull request so reviewers can understand
the complete automation model.

Run Sync Safely
---------------

Run the sync command from the repository root:

.. code-block:: bash

    composer dev-tools:sync

Then inspect the result before staging anything:

.. code-block:: bash

    git status --short
    git diff

Expected changes can include:

.. list-table::
   :header-rows: 1

   * - Area
     - Expected change
     - Review focus
   * - ``composer.json``
     - Adds shared ``dev-tools`` scripts and GrumPHP defaults.
     - Keep existing package metadata, scripts, and project-specific settings.
   * - ``.github/workflows/*.yml``
     - Adds thin workflow stubs that call reusable Fast Forward workflows.
     - Compare with any existing CI, release, docs, or Pages workflows.
   * - ``.editorconfig``
     - Adds the shared editor defaults when missing.
     - Confirm indentation and line-ending expectations match the project.
   * - ``.github/dependabot.yml``
     - Adds the shared Dependabot template when missing.
     - Confirm update cadence and ecosystem coverage.
   * - ``.github/wiki``
     - Adds the wiki submodule when missing.
     - Confirm the wiki repository exists and branch protection is compatible.
   * - ``.agents/skills``
     - Links packaged skills for issue, PR, tests, docs, README, and style work.
     - Preserve existing custom skills and non-symlink directories.
   * - ``.agents/agents``
     - Links packaged project agents for issue, PR, docs, README, and AGENTS
       maintenance work.
     - Preserve existing custom agents and non-symlink directories.
   * - ``LICENSE``
     - Adds the packaged license file when missing.
     - Confirm it matches the package metadata.
   * - ``.gitignore`` and ``.gitattributes``
     - Adds repository defaults when missing.
     - Keep existing project-specific ignores and export rules.

Handling Existing Custom Files
------------------------------

When a target file already exists, treat the migration as a comparison exercise:

1. keep the existing file in place;
2. compare it with the corresponding packaged file or workflow stub;
3. copy only the entries that make sense for the repository;
4. leave a pull request note explaining any intentional divergence.

This is especially important for custom CI workflows, release automation,
repository-specific ``.gitignore`` rules, and existing Dependabot policies.
Shared defaults are meant to reduce repeated maintenance, not erase local
constraints.

Branch Protection and Bot Commits
---------------------------------

Consumer repositories commonly protect ``main``. That is compatible with the
Fast Forward model:

- pull requests receive generated previews and bot updates on the PR branch;
- report previews are published under PR-specific Pages paths;
- wiki previews use branches such as ``pr-123`` before merge;
- post-merge jobs publish final reports and wiki content from ``main``.

If branch protection blocks bot commits to pull request branches, either adjust
the repository policy to allow workflow updates on PR branches or document that
maintainers must refresh generated pointers manually before merge.

Suggested Pull Request Rollout
------------------------------

Use one migration pull request per consumer repository:

1. install ``fast-forward/dev-tools``;
2. run ``composer dev-tools:sync``;
3. review and adapt generated files;
4. run the smallest relevant local verification command;
5. open the pull request and let GitHub Actions create previews;
6. verify report and wiki preview links before merge;
7. merge through the protected ``main`` branch.

After merge, confirm that the final reports and wiki pages reflect the merged
revision. If they do not, inspect the workflow logs before making follow-up
changes.

Related References
------------------

- :doc:`syncing-consumer-projects`
- :doc:`syncing-packaged-agents`
- :doc:`syncing-packaged-skills`
- :doc:`github-actions`
