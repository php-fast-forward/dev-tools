# Fast Forward PHPDoc Anti-patterns

Use this file as a guardrail before documenting interfaces, commands, managers, services, or orchestration code.

## 1. Duplicated Boilerplate

Do not copy repository-wide license or RFC references into every class or interface docblock when the file header already carries them.

## 2. Invented Operational Guarantees

Avoid claims such as:

- "The command MUST always fail safely."
- "Implementations SHOULD manage lifecycle transitions predictably."
- "This service MUST remain fully idempotent."

These may sound formal, but they are speculative unless the code or issue context proves them.

## 3. Overwritten Local Style

Do not replace a credible local docblock with a more verbose generic one just because the generic wording sounds more formal.

Prefer the surrounding file's voice when it is already clear and accurate.

## 4. Global Rules for `@return void`

Do not force a repository-wide rule to always add or always omit `@return void`.

Mirror the local file style unless the user asks for broader normalization.

## 5. Weakly Supported RFC 2119 Language

Use RFC 2119 keywords only when the contract is visible from:

- types
- method names plus nearby context
- existing docs
- explicit user requirements

Otherwise keep the prose descriptive.

## 6. Redundant Boolean Text

Avoid long patterns like:

- `@return bool True when the command succeeds; otherwise, false.`

Prefer:

- `@return bool Whether the command succeeds.`
