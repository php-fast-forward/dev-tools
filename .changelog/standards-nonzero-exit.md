---
category: fixed
type: patch
---

Fail standards checks for every nonzero subprocess queue status, retaining the original exit code in failure diagnostics instead of reporting invalid invocations or missing executables as successful.
