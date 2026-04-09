# Fast Forward PHPDoc Checklist

## Transformation Checklist

- Preserve execution flow, side effects, visibility, and public symbol names.
- Keep the existing member order unless the user explicitly asks for reorganization.
- Preserve `declare(strict_types=1);`, namespaces, imports, attributes, `final`, `readonly`, and native types already present.
- Normalize spacing, braces, blank lines, and multiline wrapping to the repository's PSR-12 plus Symfony-style layout.
- Keep PHPDoc tags left-aligned to match the local code style.
- Preserve the repository file header when present. If a nearby file pattern clearly requires the standard Fast Forward header, add the matching header instead of inventing a new one.
- Write or repair PHPDoc in English for classes, interfaces, traits, enums, properties, constants, methods, and functions when the file needs documentation work.
- Prefer concise summaries plus one or two evidence-based sentences over long filler paragraphs.
- Add `@param`, `@return`, and `@throws` tags only when the code or nearby context supports them.
- Prefer PHPDoc annotations over speculative signature changes when a type is uncertain.
- Run `composer dev-tools phpdoc` after repository edits when feasible.

## Evidence Hierarchy

Use the strongest evidence first.

### Strong Evidence

- Native parameter, property, constant, and return types
- Imported classes, interfaces, and enums
- Default values and enum cases
- Existing credible docblocks in the same file or neighboring symbols
- Explicit inheritance or implemented interfaces
- Repository vocabulary present in namespaces and symbol names

### Medium Evidence

- Established PHP naming patterns reinforced by visible types
- Adjacent symbols in the same file
- Existing project conventions in nearby files

### Weak Evidence

- Guesses based only on method names
- Unstated lifecycle or safety guarantees
- Imagined failure modes or fallback behavior
- Architectural claims not supported by the visible code

When evidence is weak, keep the PHPDoc descriptive and conservative.

## RFC 2119 Usage

- Use `MUST`, `MUST NOT`, `SHOULD`, `SHOULD NOT`, and `MAY` only when they describe a visible contract.
- Prefer ordinary descriptive prose when the method or symbol does not expose a formal obligation.
- Avoid boilerplate paragraphs explaining RFC 2119 unless the surrounding code already includes them or the user explicitly asks.
