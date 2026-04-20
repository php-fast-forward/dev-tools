# AGENTS.md Content Outline

Use this reference when planning or reviewing a repository-level `AGENTS.md`.

## Core Sections

Include the sections that materially fit the repository:

- `Project Overview`
- `Setup Commands`
- `Development Workflow`
- `Testing Instructions`
- `Code Style`
- `Build and Deployment`

## Optional Sections

Add these only when the repository clearly supports them:

- `Security Considerations`
- `Monorepo Instructions`
- `Pull Request Guidelines`
- `Debugging and Troubleshooting`
- `Additional Notes`

## What to Gather Before Writing

- Package manager and install command
- Main build, dev, and test entrypoints
- Focused test commands or patterns
- Lint, format, static-analysis, or code-style commands
- CI workflow locations in `.github/workflows/`
- Deployment, publishing, or release commands if present
- Existing repository conventions from `README.md`, `composer.json`, `package.json`,
  `Makefile`, `pyproject.toml`, or similar project files

## Quality Checklist

- Every command exists in the checked-out repository.
- Every referenced path is real and spelled correctly.
- The file explains what agents need to know, not broad product marketing.
- The guidance complements `README.md` instead of repeating it wholesale.
- The instructions mention generated artifacts, sync workflows, or docs outputs
  when agents can affect them.
- The file uses concrete repository vocabulary rather than generic advice.
- The final result is specific enough that another agent could start work without
  guessing the workflow.
