# Metadata Guidance

Use this reference when the issue needs labels, types, assignees, or milestones.

## Issue Types

Prefer issue types over labels for the primary category when the organization supports them.

Common examples:

- `Bug`
- `Feature`
- `Task`

Discovery example:

```bash
gh api graphql \
  -f query='{ organization(login: "ORG") { issueTypes(first: 20) { nodes { name } } } }' \
  --jq '.data.organization.issueTypes.nodes[].name'
```

## Labels

Use labels for secondary categorization, not as a replacement for issue type when a type exists.

Examples that may still be useful:

- `documentation`
- `high-priority`
- `help wanted`
- `question`

## Milestones and Assignees

- Set a milestone only when the repository is actively using milestone-based planning.
- Assign users only when the request or repository workflow clearly calls for it.

## Title Guidance

- Keep the title specific and actionable.
- Reuse the title drafted by `github-issues` unless the GitHub context reveals a better repository-specific wording.
- Avoid redundant prefixes like `[Bug]` when issue types already communicate that information.
