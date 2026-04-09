# Issue Selection

Use this guide when the user asks to work through issues and does not name a single issue.

## Default Ordering

Pick the next issue using this order:

1. Explicit user-selected issue
2. Ready issue in the active milestone
3. Priority-labelled ready issue
4. Oldest ready open issue

## Ready vs Skip

Treat an issue as ready when:

- the problem and expected outcome are both clear
- acceptance criteria are actionable
- no external blocker is called out
- the scope fits a single focused PR

Skip or stop when:

- the issue is blocked by another open issue
- the reporter requested more information and the missing information still matters
- the issue is marked `duplicate` or `wontfix`
- the issue would require product or architecture decisions that are not documented
- the issue is too large for a single safe PR and should first be split

## Queue Processing Rule

Even when the user asks for multiple issues, treat the work as a sequence of isolated issue runs:

- one branch per issue
- one PR per issue
- finish the current PR handoff before starting the next issue
- stop if the repository is left dirty or the last issue needs human follow-up

## If the Issue Is Underspecified

- If the issue can be made actionable with minimal inference, proceed and state the assumptions in the final summary.
- If the issue lacks implementable acceptance criteria, stop and ask for clarification rather than inventing large requirements.
