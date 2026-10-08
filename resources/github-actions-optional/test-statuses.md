# Dependabot Test Statuses

The adjacent [test-statuses.yml](test-statuses.yml) template mirrors required
commit statuses for same-repository Dependabot push runs. Use it when branch
protection expects unqualified contexts such as `Run Tests (8.3)` while a
reusable test workflow produces native names such as `tests / Run Tests (8.3)`.
Repositories protecting the native qualified checks do not need this bridge.

This is an optional template. `dev-tools:sync` copies workflows from
`resources/github-actions/`; it does not install this directory. Adoption,
configuration and deployment require an explicit reviewed consumer change.

## Preconditions and configuration

The first-party rollout targets twelve audited consumers with PHP 8.3, 8.4 and
8.5. Before copying the template elsewhere, verify these contracts:

- The source workflow runs on `push`, including Dependabot branches. Its
  `name` matches both `workflow_run.workflows` and the PHP metadata guard:
  `Fast Forward Test Suite` by default.
- Its path matches the metadata guard: `.github/workflows/tests.yml`.
- Its caller job is named `tests`. All matrix and control-job name checks
  expect that prefix, including `tests / Run Tests (<version>)` and
  `tests / Resolve PHP Version`. A different prefix requires updating every
  corresponding guard, not only the workflow trigger.
- `EXPECTED_PHP_VERSIONS` is a JSON array of strings listing the complete
  matrix, initially `["8.3","8.4","8.5"]`. The resulting unqualified contexts
  must match branch protection. Additional observed versions, missing
  expected versions and ambiguous results stop final publication rather than
  silently mirroring a subset.
- The publishing job can obtain `actions: read` and `statuses: write` through
  its own `GITHUB_TOKEN`. These permissions belong only to the isolated bridge
  job; do not add write permissions to code-executing test jobs.
- The runner provides PHP and the GitHub CLI. The template uses `ubuntu-latest`
  and invokes only its fixed inline PHP code and `gh api`; it does not need
  Composer dependencies, a repository checkout or a personal token.

## Explicit installation

Copy the reviewed template into the consumer's `.github/workflows/` directory
as `test-statuses.yml`. For a consumer with an installed DevTools package:

```bash
mkdir -p .github/workflows
cp vendor/fast-forward/dev-tools/resources/github-actions-optional/test-statuses.yml \
  .github/workflows/test-statuses.yml
```

Workflow-only consumers can copy the same file from a reviewed DevTools
checkout. Inspect the configured names, version list and permissions, run
`actionlint .github/workflows/test-statuses.yml`, and open a consumer PR.
The lifecycle listener becomes active only after its file reaches the default
branch. A passing template PR does not prove that listener has run.

## Lifecycle and side effects

`workflow_run` observes requested, in-progress and completed events for the
configured test workflow. Only same-repository Dependabot `push` runs qualify;
pull-request runs, fork pull requests and other actors are outside its scope.
Pull-request runs may test a merge commit, so the bridge uses the source
push's verified `head_sha`, never its own default-branch `github.sha`.

The bridge verifies the source run's identity, repository, SHA, workflow
name/path/ID, actor, event and attempt through GitHub metadata. It skips
superseded runs and attempts and rechecks the source snapshot before each
write. Active attempts receive `pending` statuses. A delayed start event reads
the current state instead of downgrading a completed attempt to pending.

Terminal publication validates the entire configured matrix before writing.
Each version uses its newest attempt, which must have a completed result;
failed-only retries retain completed results for unaffected versions. A full
rerun that fails or is cancelled before its matrix executes must not revive
earlier successes. Verified failed control jobs or a failed source attempt
produce failure statuses for blocked versions. Invalid or ambiguous metadata
stops publication without inventing successful results.

The only writes are commit statuses on the verified source SHA, with links to
its test run. No source, artifacts, caches or caller-produced result files are
downloaded, and no consumer code executes with the writing token.

Scheduling and API access are not instantaneous. Separate status POSTs are
not atomic: an API failure can leave existing statuses unchanged or only some
contexts updated, and source state can change between requests. Inspect the
bridge result and retry the appropriate lifecycle execution after the cause is
resolved. Do not treat a missing status or API error as a successful test.

## Verification and rollback

After deployment, verify a real Dependabot push against the consumer's exact
SHA. Confirm pending contexts while the source attempt is active and the
correct per-version terminal states and target URLs afterward. Check failed
and partial retries, a cancelled or blocked matrix, and stale-event handling.
For template changes, use isolated PHP fixtures with a fake GitHub CLI to
exercise the actual run block, then lint the copied YAML and compare it with
the optional canonical source. No test should write to a real repository or
reuse the operator's real home directory.

Restore the previous reviewed consumer workflow if an adoption fails. Before
removing the bridge, verify that protection requires the actual native checks
or that another trusted publisher still supplies every required alias. Do not
remove required checks or create artificial successes to make a merge pass.
The reusable workflow's ordinary pending job runs again on full reruns; an
individual successful-job rerun can retain successful ancestors and therefore
does not guarantee another ordinary pending publication.

See the [workflow guide](../../docs/usage/github-actions.rst) and
[branch-protection guide](../../docs/advanced/branch-protection-and-bot-commits.rst)
for the separate ordinary-run publishers and caller permission ceiling.
