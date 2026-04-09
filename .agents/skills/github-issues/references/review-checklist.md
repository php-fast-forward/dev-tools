# Review Checklist

Use this checklist before finalizing issue content or mutating GitHub.

## Draft Quality Checks

- The title is specific, action-oriented, and easy to scan.
- The issue clearly separates the problem from the proposed solution.
- The issue type matches the prompt: feature, bug, or task.
- Acceptance criteria are objective and testable.
- The correct base quality block was used: code-change or documentation/content.
- Repository-specific terms, commands, directories, or artifacts are named precisely.
- Non-goals are present when the request could easily expand in scope.
- Tests, docs, README, wiki, sync, or reports are called out when the change obviously affects them.
- Assumptions are minimal and explicitly stated when inference was necessary.
- The final output contains no meta commentary about prompting or the drafting process.

## GitHub Write Checks

- The repository is correct.
- Authentication is available for the intended write.
- The target issue number is correct for updates, comments, or closure.
- The title and body are final or intentionally partial.
- Metadata changes are explicit and necessary.
- A duplicate issue is not being created accidentally.
- The final response will include the issue number and URL.

## Stop Conditions

Reject and rewrite the draft, or stop before mutating GitHub, if any of these are true:

- A maintainer could not tell what "done" looks like.
- The issue asks for a redesign when the prompt only justifies a focused change.
- The issue mixes several unrelated initiatives into one scope.
- The issue mandates implementation details that are not required for correctness.
- The issue contains generic filler such as "improve things" or "make it better".
- The repository cannot be resolved safely.
- The target issue is ambiguous.
- The title or body is still obviously placeholder content.
- The requested mutation conflicts with existing tracked work.
