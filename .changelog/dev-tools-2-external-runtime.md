---
category: changed
type: major
---

Runs DevTools as a standalone CLI through an isolated CPX installation, without
requiring consumers to add DevTools to their Composer development dependencies.
Removes Composer plugin command registration, automatic install/update sync and
the skills/agents symlink commands. Exports namespaced fast-forward-* skills in
resources/skills for independent LLM Skills installation; repository metadata,
workflow defaults and Git hooks retain explicit dev-tools:sync. Documents the
version 2 migration and gradual transfer of responsibilities to released owners.
