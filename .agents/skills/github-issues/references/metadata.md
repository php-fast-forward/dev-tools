# Metadata Guidance

Use this reference when the issue needs labels, types, assignees, milestones,
project assignment, project field values, or issue relationships.

## General Rule

- Reuse only metadata that already exists in the target repository or
  organization.
- Prefer the maximum useful metadata that can be inferred safely from scope and
  repository context.
- Leave a field unset when the fit is weak, ambiguous, or unsupported by the
  available token.
- Never create new labels, issue types, projects, project field options, or
  milestones as part of normal issue drafting or publication.

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

Select the closest existing type for the issue scope. Do not downgrade to a
label-only strategy when a fitting issue type exists.

## Labels

Use labels for secondary categorization, not as a replacement for issue type when a type exists.

Examples that may still be useful:

- `documentation`
- `high-priority`
- `help wanted`
- `question`

Choose labels only from the existing repository label set. Add them when they
materially improve categorization, not as a reflex.

Discovery example:

```bash
gh label list --repo {owner}/{repo} --limit 200
```

## Milestones and Assignees

- Set a milestone only when the repository is actively using milestone-based planning.
- Assign users only when the request or repository workflow clearly calls for it.

Milestone discovery example:

```bash
gh api repos/{owner}/{repo}/milestones
```

If there are no milestones, or none clearly fit the scope, leave the issue
without one.

## Projects

When the repository or organization uses Projects, prefer adding the issue to
the most appropriate existing project instead of leaving project assignment
empty by default.

Project discovery example:

```bash
gh api graphql \
  -f query='{ organization(login: "ORG") { projectsV2(first: 20) { nodes { id title number closed } } } }'
```

If project access is unavailable, first try to refresh GitHub CLI scopes:

```bash
gh auth refresh -h github.com -s read:project -s project
```

Warn the user that this command MAY open a browser and ask them to type a
verification code before project access is granted.

If no existing repository or organization project clearly fits, omit the
project assignment instead of guessing. When the repository and organization do
not appear to have any usable project at all, you MAY suggest copying the Fast
Forward template project as a starting point:

```text
https://github.com/orgs/php-fast-forward/projects/2
```

Treat that suggestion as optional guidance for the user, not as a project you
create automatically.

## Project Fields

When a selected project exposes fields whose values can be inferred with a high
degree of confidence, attempt to populate them instead of leaving them blank by
default. Common examples include `Status`, `Priority`, `Size`, and
`Iteration`, but the same rule applies to any existing project field that has a
clear fit for the issue.

Common guidance:

- `Status`: pick the nearest lifecycle state, often `Backlog` or `Ready` for a
  newly created issue.
- `Priority`: prefer the lowest confident priority instead of inflating urgency.
- `Size`: choose a rough estimate only when the issue scope supports it.
- `Iteration`: for newly created issues, prefer the current active iteration
  when the issue is intended to enter the current cycle. For backfill on older
  issues that are already closed, try to infer the most appropriate completed
  iteration from the closing pull request timeline before leaving it empty.
- Other fields: populate them only when the issue scope, repository context, or
  linked pull-request history makes the right value genuinely clear.

Backfill guidance:

- Support backfill for older issues that are missing project metadata.
- Only backfill fields that are currently unset or obviously incomplete.
- Do not overwrite existing project-field values during a backfill pass unless
  the user explicitly asks for correction.
- When inferring `Iteration` from a closing pull request, prefer a conservative
  heuristic tied to the PR merge date or the issue closing date and reuse only
  iterations that already exist on the target project.

Do not invent new field options, and do not force a value when no safe choice
is evident.

## Related Issues

When a newly drafted issue appears materially related to an existing open issue,
record that relationship instead of leaving the issues disconnected.

Examples of useful relationships:

- dependency or blocker
- follow-up or split from a larger tracked task
- scope overlap that reviewers should see together

Avoid speculative or noisy links when the relationship is weak.

## Title Guidance

- Keep the title specific and actionable.
- Reuse the title drafted by `github-issues` unless the GitHub context reveals a better repository-specific wording.
- Avoid redundant prefixes like `[Bug]` when issue types already communicate that information.
