---
name: fast-forward-issues
description: Transform a short feature, enhancement, or bug description into a complete production-ready GitHub issue with structured sections, acceptance criteria, and architectural quality requirements. Use when Codex needs to draft or refine an issue from a brief prompt.
---

# Create GitHub Issue from Specification

This skill transforms a short feature description into a complete, production-ready GitHub issue written in clear, precise, and structured English following strict quality guidelines.

## When to Use This Skill

Use this skill when:
- User provides a short feature description and asks to create a GitHub issue
- User wants to transform a brief idea into a structured issue
- User asks to "create issue from description" or similar
- User provides a prompt describing a feature/enhancement/bug

## Input Processing

### Extract the Feature Description

The user will provide a short description. Extract and analyze:
- The core request (add, enhance, fix, improve)
- The subject/target of the request
- Any specific tools, libraries, or technologies mentioned
- The context or domain

### Infer Missing Details

Using engineering judgment, infer:
- Common CLI patterns for the described feature type
- Expected file locations and naming conventions
- Standard architectural patterns for the feature domain
- Typical acceptance criteria for similar features

## Output Generation

Generate a complete GitHub issue following this structure:

### 1. Title

- Clear, concise, descriptive
- Action-oriented phrasing (Add, Enhance, Introduce, Fix)
- Reflects intent and scope

### 2. Description

#### Problem
- Explain current limitation or gap
- Focus on real-world impact (DX, CI, maintenance, consistency)
- Avoid fluff or generic statements

#### Proposal
- Describe solution at conceptual level
- Avoid jumping into implementation
- Clearly state what will be introduced or changed

#### Goals
- Define success criteria
- Focus on outcomes, not implementation

#### Expected Behavior
- Describe feature from user perspective
- Include CLI examples when relevant

#### Implementation Strategy
- Suggest practical approach
- Mention tools, libraries, patterns when useful
- Keep flexible, not prescriptive

#### Requirements
- Define strict functional requirements
- Must be testable and objective
- Include CLI behavior, outputs, determinism, CI compatibility

#### Non-goals
- Explicitly state what is out of scope
- Prevent feature creep

#### Benefits
- Explain why feature matters
- Focus on developer experience, maintainability, consistency

#### Additional Context (optional)
- Relevant background, ecosystem alignment, rationale

### 3. Acceptance Criteria

#### Functional Criteria
- What must work
- What must not break
- Output expectations
- Error handling expectations

#### Architectural/Isolation Criteria (MANDATORY)

Always include this section enforcing architectural quality:

```
### Architectural / Isolation Criteria

- **MUST**: The core logic MUST be isolated into dedicated classes (no business logic inside the command/controller layer)
- **MUST**: Responsibilities MUST be clearly separated:
  - One class for input/config resolution
  - One class for domain logic
  - One class for processing/transformations
  - One class for output/rendering
- **MUST**: The command layer MUST act only as an orchestrator
- **MUST**: The design MUST allow future extraction into an external reusable package with minimal changes
- **MUST**: The implementation MUST avoid tight coupling to CLI/framework-specific I/O
- **MUST**: The system MUST be extensible without requiring major refactoring
```

## Style Guidelines

- Be highly objective and precise
- Avoid filler language
- Avoid vague statements like "improve things"
- Prefer deterministic, testable statements
- Use clean Markdown formatting
- Use code blocks for CLI examples
- Use bullet points for clarity
- Keep strong engineering tone (not marketing, not casual)

## Output Format

Output the complete, ready-to-use GitHub issue in Markdown format. Do not include:
- Follow-up questions
- Explanations about the prompt
- Meta commentary

## Example Transformation

**Input:**
"Add a command to analyze dependencies using composer-unused and dependency-analyser"

**Output:**
See the complete generated issue with all sections as specified above.
