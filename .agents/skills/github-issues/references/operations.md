# Issue Operations

Use `gh api` for write operations in Fast Forward repositories.

## Create Issue

```bash
gh api repos/{owner}/{repo}/issues \
  -X POST \
  -f title="Issue title" \
  -f body="$(cat /path/to/issue-body.md)" \
  --jq '{number, html_url}'
```

Add metadata flags only when needed:

```bash
-f type="Feature"
-f labels[]="documentation"
-f assignees[]="username"
-f milestone=1
```

## Update Issue

```bash
gh api repos/{owner}/{repo}/issues/{number} \
  -X PATCH \
  -f title="Updated title" \
  -f body="$(cat /path/to/issue-body.md)" \
  --jq '{number, html_url}'
```

Only include the fields that should change.

## Add Comment

```bash
gh api repos/{owner}/{repo}/issues/{number}/comments \
  -X POST \
  -f body="Comment body" \
  --jq '{html_url}'
```

## Close Issue

```bash
gh api repos/{owner}/{repo}/issues/{number} \
  -X PATCH \
  -f state=closed \
  --jq '{number, html_url, state}'
```

## Mutation Rules

- Restate the target issue before mutating it.
- Prefer full body replacement only when the body is being intentionally rewritten.
- Use comments for incremental updates that should preserve the issue description.
