---
category: fixed
type: patch
---

Keep resource and Git hook synchronization dry-run/check modes read-only when destinations are missing, reporting planned creations and failing checks without creating directories, copying files, rendering installed hooks or changing permissions.
