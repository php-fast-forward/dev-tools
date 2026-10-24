# Issue Templates

Copy the template that matches the classified issue type, then adapt it to the prompt and repository context.

## Feature Template

```markdown
## Problem
[Explain the current limitation and why it matters]

## Proposal
[Describe the intended capability at a conceptual level]

## Goals
- [Outcome 1]
- [Outcome 2]

## Expected Behavior
[Describe how a user or maintainer will experience the new capability]

## Implementation Strategy
[Outline a practical approach without locking the implementer into incidental details]

## Requirements
- [Testable functional requirement]
- [CLI, output, or compatibility requirement]

## Non-goals
- [Explicitly out of scope]

## Benefits
[Explain the impact on DX, maintainability, consistency, or ecosystem fit]

## Acceptance Criteria

### Functional Criteria
- [ ] [Behavior that must work]
- [ ] [Behavior that must remain compatible]

### Architectural / Isolation Criteria
[Paste the relevant block from references/architectural-criteria.md]
```

## Bug Template

```markdown
## Problem
[Summarize the failure and its impact]

## Current Behavior
[Describe the broken behavior]

## Expected Behavior
[Describe the correct behavior]

## Failure Surface
[State affected command, workflow, files, or conditions]

## Proposal
[Describe the correction at a high level]

## Implementation Strategy
[Suggest a repair strategy and the likely boundaries of the fix]

## Non-goals
- [State unrelated cleanup or redesign that is out of scope]

## Acceptance Criteria

### Functional Criteria
- [ ] [Failure no longer happens]
- [ ] [Known supported behavior still works]

### Regression Criteria
- [ ] [Add or update coverage for the reproduced failure]

### Architectural / Isolation Criteria
[Paste the relevant block from references/architectural-criteria.md]
```

## Task / Refactor Template

```markdown
## Objective
[Describe the structural or maintenance goal]

## Current Limitation
[Explain why the current structure is hard to maintain or extend]

## Proposed Work
[Describe the intended reshaping of the code, workflow, or content]

## Scope
- [In-scope item]
- [In-scope item]

## Non-goals
- [Out-of-scope item]

## Acceptance Criteria

### Delivery Criteria
- [ ] [Concrete structural outcome]
- [ ] [Required verification or updated artifact]

### Architectural / Isolation Criteria
[Paste the relevant block from references/architectural-criteria.md]
```

## Documentation-Only Variant

If the issue is strictly about documentation or content, keep the main template sections but replace the code-isolation block with the documentation/content block from [references/architectural-criteria.md](architectural-criteria.md).
