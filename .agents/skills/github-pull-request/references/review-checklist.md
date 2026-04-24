# Review Checklist

Use this checklist before reporting completion.

## Required Checks

- The selected issue was ready and actionable.
- The branch contains only issue-related work.
- The branch was created from the correct base branch.
- Relevant tests were run, or any failure was reported clearly.
- `composer dev-tools` was run before the PR unless the user explicitly constrained the work.
- If `composer dev-tools` reported auto-fixable issues, `composer dev-tools:fix` was used or explicitly considered before finalizing the branch.
- README, docs, wiki, reports, or sync outputs were updated when the change touched them.
- The PR title follows repository conventions.
- The PR body includes a clear summary, concrete testing notes, and `Closes #123` style closing text.
- The published PR body was read back from GitHub and does not contain literal
  escaped Markdown control characters such as `\n` where real newlines were
  intended.
- No duplicate PR was created for the same branch.
- The final user summary explains what changed, what was verified, and what remains.

## Stop Conditions

Stop and report instead of pushing through when:

- verification fails for reasons you cannot safely resolve
- the issue requires clarification before a correct implementation is possible
- the repository already contains conflicting unfinished work for the same scope
- opening the PR would publish knowingly broken or incomplete behavior
