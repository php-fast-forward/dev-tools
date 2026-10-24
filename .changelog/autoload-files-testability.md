---
category: fixed
type: patch
---

Detect PHP sources declared in Composer `autoload.files` so function-only packages run tests and coverage reporting instead of silently skipping them, without changing API documentation discovery.
