Installation
============

FastForward DevTools is installed as a development dependency and exposed
through Composer as both a plugin and a local binary.

Requirements
------------

- PHP 8.3 or newer.
- Composer 2.
- A project with PSR-4 autoload definitions in ``composer.json``.
- Git, if you want ``dev-tools:sync`` to create the repository wiki submodule.

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
5. ``dev-tools:sync`` updates ``extra.grumphp.config-default-path`` and copies
   missing automation assets such as workflow stubs, ``.editorconfig``, and
   ``.github/dependabot.yml``.
6. If ``.github/wiki`` is missing, ``dev-tools:sync`` adds it as a Git
   submodule that points to the repository wiki.
7. ``dev-tools:sync`` runs ``gitignore`` to merge canonical ignore rules into
   the consumer project.
8. ``dev-tools:sync`` runs ``skills`` to create or repair packaged skill links
   inside ``.agents/skills``.

First commands to try
---------------------

After installation, these are the most useful sanity checks:

.. code-block:: bash

   composer dev-tools skills
   composer dev-tools tests
   composer dev-tools docs
   composer dev-tools

If Composer argument forwarding becomes awkward, call the binary directly:

.. code-block:: bash

   vendor/bin/dev-tools tests --filter=SyncCommandTest

If you want to verify the packaged skills on their own, run:

.. code-block:: bash

   vendor/bin/dev-tools skills

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
   The ``skills`` command creates ``.agents/skills`` when needed, but it does
   not overwrite an existing non-symlink directory inside that tree.
