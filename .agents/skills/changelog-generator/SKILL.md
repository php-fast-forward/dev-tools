---
name: changelog-generator
description: Compatibility entrypoint redirecting legacy DevTools changelog tasks to the externally maintained Fast Forward Changelog skill.
---

# Changelog Generator compatibility entrypoint

Use [fast-forward-changelog](../fast-forward-changelog/SKILL.md), distributed
from the verified upstream release. It owns fragment authoring, history
maintenance, version preparation and authorized publication.

DevTools no longer implements the `changelog:*` commands or an `Unreleased`
editor. Use `dev-tools changelog -- <command> <arguments>` with PHP 8.5 and CPX,
or execute the selected external runtime directly. Read that runtime's help;
the removed aliases are not semantic equivalents of the new commands.

Keep publication authority separate from preparation and preserve consumer
credentials, runners, paths and publication settings during adoption.
