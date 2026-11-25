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

After creation, apply project assignment, project field values, or issue links
through follow-up mutations when the repository context supports them.

Then verify the stored issue body:

```bash
gh issue view {number} --json number,body,url
```

The returned body should still contain the intended Markdown content. Treat the
write as failed if the stored body collapses into a temporary path such as
`@/tmp/...`, a placeholder token, or another obviously malformed result.

## Update Issue

```bash
gh api repos/{owner}/{repo}/issues/{number} \
  -X PATCH \
  -f title="Updated title" \
  -f body="$(cat /path/to/issue-body.md)" \
  --jq '{number, html_url}'
```

Only include the fields that should change.

Then verify the stored issue body:

```bash
gh issue view {number} --json number,body,url
```

The returned body should match the intended update. If the readback shows a
temporary file reference, placeholder path, or another malformed payload,
correct the issue before reporting success.

## Add Issue to an Existing Project

```bash
gh api graphql \
  -f query='mutation($project:ID!, $content:ID!) { addProjectV2ItemById(input: {projectId: $project, contentId: $content}) { item { id } } }' \
  -f project='PROJECT_ID' \
  -f content='ISSUE_NODE_ID'
```

## Set an Existing Project Field Value

For single-select fields such as `Status`, `Priority`, `Size`, or any other
existing single-select field with a safe inferred value:

```bash
gh api graphql \
  -f query='mutation($project:ID!, $item:ID!, $field:ID!, $option:String!) { updateProjectV2ItemFieldValue(input: {projectId: $project, itemId: $item, fieldId: $field, value: {singleSelectOptionId: $option}}) { projectV2Item { id } } }' \
  -f project='PROJECT_ID' \
  -f item='ITEM_ID' \
  -f field='FIELD_ID' \
  -f option='OPTION_ID'
```

The same principle applies to any other supported project field type: only
write values that can be inferred confidently from the issue scope, repository
workflow, or linked pull-request history.

## Add a Related-Issue Link

When the repository supports issue relationships, prefer the official GitHub
relationship mutation or connector path available in the environment. If that
surface is unavailable, explicitly mention the related issue in the body or an
issue comment rather than silently dropping the relationship.

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
- For new issues, prefer applying metadata immediately after creation so the
  issue lands in GitHub with a complete and reviewable state.
- After create or update writes, always re-read the issue body from GitHub
  before reporting success.
- For backfill passes, update only missing metadata by default and avoid
  rewriting fields that already carry intentional values.
