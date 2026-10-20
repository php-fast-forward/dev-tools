Architecture and Command Lifecycle
==================================

CPX installs the chosen DevTools runtime separately. ``bin/dev-tools`` loads
its own autoloader and resolves ``Console\DevTools`` through the shared
container. ``DevToolsCommandLoader`` lazily discovers Symfony commands.
Commands read consumer metadata, local overrides, source and artifacts from
the working directory, while tool binaries and packaged defaults come from
the DevTools installation. ``standards`` orchestrates the default pipeline.

There is no Composer plugin or automatic install/update hook.
``dev-tools:sync`` explicitly manages repository metadata, workflow callers,
defaults and Git hooks. LLM Skills owns skill installation and provenance;
the old skill/agent link synchronizer is removed.

DevTools may require extracted config, container or other runtime libraries.
Those libraries run DevTools through CPX for development, avoiding a reverse
Composer dependency. Separate resolution does not guarantee incompatible PHP
APIs can coexist in one process. Preserve actual consumer test dependencies
and validate the test/runtime boundary.

Keep process policy centralized in ``ProcessQueue`` configurators. See
:doc:`../usage/dev-tools-2-migration` for staged extraction and ownership.
