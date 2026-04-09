# Context Resolution

Resolve the target repository and issue before performing any write.

## Repository Resolution

- If the user provides an `owner/repo`, URL, or issue link, use that directly.
- If the request is about the current checkout, derive the repository from the local git remote.
- If local git context is ambiguous, stop and ask for the repository instead of guessing.

## Authentication

Before write operations, ensure GitHub authentication is available.

Useful check:

```bash
gh auth status
```

## Existing Issue Resolution

For updates or comments:

- use the explicit issue number when provided
- otherwise search or inspect the repository issue list before mutating anything

For creates:

- check whether an obvious duplicate already exists when the prompt strongly suggests current tracked work
- if the repository already has a canonical issue for the request, update or comment on it instead of creating another one

## Output Requirement

After any write, return:

- issue number
- issue URL
- short summary of what changed
