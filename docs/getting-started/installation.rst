Installation
============

FastForward DevTools is installed as a development dependency and exposed
through Composer as both a plugin and a local binary.

Requirements
------------

- PHP 8.3 or newer.
- Composer 2.
- A project with PSR-4 autoload definitions in ``composer.json``.
- Git, for repository metadata and workflow synchronization.

Install with Composer
---------------------

.. code-block:: bash

   composer require --dev fast-forward/dev-tools

What happens after installation?
--------------------------------

When Composer finishes the install or update, the package performs the
following steps:

1. Composer loads ``FastForward\DevTools\Composer\Plugin``.
2. The plugin exposes commands through
   ``FastForward\DevTools\Composer\Capability\DevToolsCommandProvider``.
3. Composer triggers ``vendor/bin/dev-tools dev-tools:sync`` after install and
   update.
4. ``dev-tools:sync`` adds or refreshes the ``dev-tools`` and
   ``dev-tools:fix`` scripts in the consumer ``composer.json``.
5. ``dev-tools:sync`` removes deprecated DevTools-managed
   ``extra.grumphp.config-default-path`` entries, synchronizes funding
   metadata, copies automation assets such as workflow stubs,
   ``.editorconfig``, and ``.github/dependabot.yml``, and refreshes
   ``.gitignore``, ``.gitattributes``, the project license, and packaged Git
   hooks that prefer a project-local ``grumphp.yml`` override and otherwise
   use a project-relative reference to the active packaged DevTools
   ``grumphp.yml`` path.
6. In normal mode, ``dev-tools:sync`` generates local Markdown documentation
   in ``.dev-tools/wiki-generated`` without initializing a Wiki submodule.
7. ``dev-tools:sync`` runs ``gitignore`` to merge canonical ignore rules into
   the consumer project.
8. ``dev-tools:sync`` runs ``skills`` and ``agents`` to create or repair
   packaged links inside ``.agents/skills`` and ``.agents/agents`` when sync
   runs in normal mode rather than preview/check mode.

Wiki publication is separate from local synchronization. The synchronized
``wiki.yml`` wrapper calls ``wiki-direct.yml`` to generate documentation with
read-only permissions on pull requests and publish directly to the separate
Wiki repository from ``main``. Its isolated, pinned DevTools checkout provides
the renderer without changing the consumer's ``composer.json``,
``composer.lock``, or plugin allowlist.

The synchronized ``wiki-maintenance.yml`` is a manual-only migration notice.
Existing callers of the legacy reusable preview and maintenance workflows
remain supported until they migrate. The explicit ``wiki --init`` option also
remains available for legacy submodule users; normal sync does not use it.

First commands to try
---------------------

After installation, these are the most useful sanity checks:

.. code-block:: bash

   composer skills
   composer agents
   composer tests
   composer docs
   composer dev-tools

If Composer argument forwarding becomes awkward, call the binary directly:

.. code-block:: bash

   vendor/bin/dev-tools tests --filter=SyncCommandTest

If you want to verify the packaged skills or project agents on their own, run:

.. code-block:: bash

   vendor/bin/dev-tools skills
   vendor/bin/dev-tools agents

When manual sync is useful
--------------------------

If the package was installed with Composer plugins disabled, or if you want to
refresh consumer automation after upgrading this package, run:

.. code-block:: bash

   composer dev-tools:sync

Or call the binary explicitly:

.. code-block:: bash

   vendor/bin/dev-tools dev-tools:sync

.. important::

   The ``docs`` and ``reports`` commands require a ``docs/`` directory. If
   your package does not have one yet, create it before running those commands.
   The ``skills`` and ``agents`` commands create ``.agents/skills`` and
   ``.agents/agents`` when needed, but they do not overwrite existing
   non-symlink directories inside those trees.
