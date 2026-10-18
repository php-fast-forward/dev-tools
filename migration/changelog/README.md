# External changelog adoption

This is a review proposal, not an applied release reconciliation.

## Selected runtime and integration

On 2026-10-18 the latest stable GitHub Release was `v0.2.0`,
published at `2026-10-18T07:15:48Z`. Its tag, release target, the
installed Composer lock source reference and the `v0` branch all resolved to
`2d3f84fa9e5350f03636153fdb3ae2a822e5ae60`.
The caller follows `@v0` by explicit owner choice; this is a moving branch,
not a concrete `v0` tag. Local verification uses exact runtime `0.2.0`.

The workflow preserves Ubuntu, GitHub's default Bot/token, the
`Prepare release v{next_version}` title, the `release/v` branch prefix
(one managed branch `release/vnext`), required test dispatch and
the Project transition from Merged to Release Prepared. A callable adapter
retains managed-file/project/runner settings for existing callers and rejects
legacy explicit-version promotion rather than silently ignoring it.
Consumer templates keep the external lifecycle and the separate Project/test
adapters. Preserve repository-specific credentials and runner labels when
adopting a synchronized template.

Initial publication is explicitly disabled. The former automatic publication,
wiki-preview refresh and arbitrary release-branch bypass do not carry into the
new lifecycle: wiki uses its separately approved direct publication flow, and
the external package verifies ownership of its managed version PR.
Automatic PR origin discovery is enabled consistently.

The DevTools PHP package remains installable with PHP 8.3. The new
`dev-tools changelog -- …` bridge runs CPX's isolated exact
`fast-forward/changelog:0.2.0` installation and requires PHP 8.5 and CPX
on the execution path. The removed `changelog:*` aliases have different
semantics from fragments and are deliberately not translated.
The migration fragment declares major impact because it removes public aliases
and internal-domain APIs; the next-version preview is not release authorization.
`pyrech/composer-changelogs` remains: it displays dependency update notes.

## Related issues and ownership

| Issue | Observed state | Relationship |
| --- | --- | --- |
| [changelog#1](https://github.com/php-fast-forward/changelog/issues/1) | Closed | Direct extraction contract: independent domain/CLI, with DevTools as an adapter. |
| [dev-tools#145](https://github.com/php-fast-forward/dev-tools/issues/145) | Open | Stale baseline safeguards; its legacy Unreleased wording needs reassessment against external policy. |
| [dev-tools#243](https://github.com/php-fast-forward/dev-tools/issues/243) | Open | Release readiness; legacy wording does not authorize publication or all remaining work. |
| [dev-tools#197](https://github.com/php-fast-forward/dev-tools/issues/197) | Open | Aggregator/shim direction, broader than this migration. |
| [dev-tools#238](https://github.com/php-fast-forward/dev-tools/issues/238) | Open | Broader automation versioning policy. |
| [dev-tools#240](https://github.com/php-fast-forward/dev-tools/issues/240) | Open | Shared automation extraction; DevTools still synchronizes consumer wrappers. |

This PR does not close or rewrite these issues. The external library owns
parsing, rendering, fragments, planning and authorization validation.
DevTools retains the CLI bridge and consumer asset synchronization.
Legacy central-history conflict auto-merging is retired; wiki gitlink
resolution remains. The upstream skill is copied with its license from the
selected release; the old skill name is a compatibility pointer.

## Prepared Release operations

Read [the inventory](reconciliation.md), `note-evidence.json`,
`github-releases-before.json` and `github-releases-plan.json`.
The last file contains exact per-version bodies from the official `notes`
command, before/after hashes and identities to preserve. All 44 remote tags
already have Releases; no creation is planned. Five additional legacy records
are unresolved and must not cause tag or Release creation.

The upstream publish operation verifies a new approved consolidation; it is
not an API for rewriting an existing divergent historical body. This proposal
does not change its validators. After separate authorization and integration:

1. Re-read the merged canonical SHA, approvals, checks and current remote tags.
   Verify every tag still resolves to the inventory's exact commit. Work from
   a disposable checkout of that immutable integrated SHA, with canonical
   GitHub origin and exact runtime 0.2.0.
2. Re-extract each body with `changelog notes VERSION --output=notes/VERSION.md`.
   Inspect output and compare it with the reviewed desired-body hash.
   Build a JSON request containing only `{"body": "...exact bytes..."}`.
   Do not submit this premerge plan directly.
3. GET `repos/php-fast-forward/dev-tools/releases/RELEASE_ID`; compare its
   tag, name, target, draft/prerelease flags, assets and original body against
   the reviewed snapshot. Stop on drift.
4. Only under that specific authorization, execute
   `gh api --method PATCH repos/php-fast-forward/dev-tools/releases/RELEASE_ID --input BODY_PAYLOAD.json`.
   The only supplied field is `body`; no tag, target, asset, name, date or flag
   update is authorized by this plan.
5. GET that Release again. Require exact body equality with the extracted bytes
   and unchanged identity, flags and assets. Record the response and integrated
   source SHA before proceeding to the next version.

All PR links are inline. Official extraction includes no document footer, so
the isolated bodies need no other versions' definitions. No automatic authors,
commit lists or full changelog are added.

## Gates and remaining approval

Current main protection requires strict up-to-date `Run Tests (8.3)`,
`Run Tests (8.4)`, `Run Tests (8.5)` and `Analyze (actions)` statuses.
This proposal does not edit protection. The new library's
`Fast Forward Changelog` status should be observed in a deployment pilot;
adding a required context needs a deliberate protection change.
Default CodeQL setup owns Analyze (actions); verify its generated version-head
status independently before any version-PR merge.

An adoption PR edits central history. The external maintenance policy requires
a live `changelog-maintenance` grant by a verified human maintain/admin actor,
checked through the timeline and collaborator-permission APIs at the exact PR
head. Labels alone are insufficient. No fake Bot trailers or managed-branch
ownership are used. There was no open version PR at investigation time.

Review/merge of this ordinary migration PR, deployment pilot, any future
version-PR merge, publication enablement and historical Release PATCH operations
remain separate decisions. No tag or Release writes were executed.
