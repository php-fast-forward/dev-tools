Installing Packaged Skills
==========================

DevTools exports owned procedural packages under
``resources/skills/fast-forward-*``. LLM Skills owns their installation,
updates and provenance. Installing instructions does not install the CLI.

Use the supported LLM Skills integration (Composer package ``llm/skills``),
then configure ``skills.json``. When DevTools is only an external tool, use
a pinned GitHub source after verifying that release exists:

.. code-block:: json

   {
     "target": ".agents/skills",
     "aliases": [],
     "discovery": false,
     "vendor-sources": false,
     "sources": [
       {
         "from": "github",
         "package": "php-fast-forward/dev-tools",
         "ref": "v2.0.0",
         "skills": ["fast-forward-phpunit-tests", "fast-forward-phpdoc-code-style"]
       }
     ]
   }

The full tag above is the version 2 migration target, not a claim of published
availability. Prefer an immutable verified release commit when adopting it.
Select only the skills needed by the project.

.. code-block:: bash

   composer skills:show
   composer skills:update --dry-run
   composer skills:update

For a focused GitHub-source update, LLM Skills also accepts the source package:

.. code-block:: bash

   composer skills:update php-fast-forward/dev-tools --dry-run
   composer skills:update php-fast-forward/dev-tools

A positional update explicitly trusts that operation; it is not proof that the
same source is enabled for unattended default synchronization. Review preview,
source configuration and provenance together. Keep LLM Skills' own metadata
and lock state; DevTools must not manufacture substitute records.

Composer Trusted donors
-----------------------

For libraries actually installed through Composer, trust their resolved skill
payload rather than adding a second GitHub owner for the same skill:

.. code-block:: json

   {
     "dependencies": {
       "composer": {
         "trusted": ["fast-forward/config", "fast-forward/container"],
         "trusted-replace": true
       }
     }
   }

This is an example for projects that really install those packages. Trust does
not install a missing library or fetch a GitHub replacement. A missing donor
payload must fail review until a compatible donor or explicit source migration
is selected. ``trusted-replace`` permits a trusted package's canonical skill
replacement; review ownership changes and remove the prior source explicitly.

Self development and extraction
-------------------------------

The DevTools checkout uses a ``dir`` source for its own ``resources/skills``.
Consumers use GitHub when DevTools is absent from their Composer graph.
As code style, quality or another domain moves out, retain the
``fast-forward-<responsibility>`` skill name, publish its new owner first,
then transfer the source in one reviewed consumer change. Keep one owner per
skill. Do not leave duplicated DevTools and donor sources for the same name.

Version 2 removes ``skills`` and ``agents`` from the DevTools command surface.
Inventory old links and preserve edited/local directories before migrating.
LLM Skills does not synchronize workflow files, Git hooks, metadata or generic
project-agent prompts. See :doc:`dev-tools-2-migration`.
