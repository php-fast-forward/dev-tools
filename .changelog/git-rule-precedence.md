---
category: fixed
type: patch
---

Preserve consumer rule ordering and overrides when synchronizing `.gitignore` and `.gitattributes`, including repeated patterns, export exceptions, and significant ignore-pattern whitespace. Add generated defaults before existing rules without reordering consumer exceptions.
