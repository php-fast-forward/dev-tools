# Native GitHub Pages reports

Use this optional wrapper only when the consumer's Pages settings use GitHub Actions as the build source. Keep the existing `reports.yml` reusable workflow for consumers publishing from `gh-pages`.

Copy `reports-native.yml` into the consumer as `.github/workflows/reports.yml`. Review existing triggers before replacing a customized workflow. The wrapper builds PR artifacts, and deploys only `refs/heads/main`; it does not implement preview publication or cleanup schedules. It is intentionally outside automatic `dev-tools:sync` resources.

This wrapper requires the consumer to install `fast-forward/dev-tools` locally through its own Composer manifest. It verifies the template under that consumer's vendor directory; it is not a projectless fallback-runtime wrapper. The ten audited native Pages consumers satisfy this precondition.

The caller's `contents: read`, `pages: write` and `id-token: write` values are ceilings. The build job overrides them to read-only; the checkout-free deployment job alone receives Pages/OIDC writes. Package installation skips plugins and scripts. The official phpDocumentor shim is installed in a separate temporary Composer project allowing only that plugin, verifies the PHAR signature, and exposes the generated binary under the consumer's vendor/bin. The consumer's plugin allowlist is preserved.

The build requires template 2.1.0 or newer, records its selected version/reference and validates rendered identity assets against the installed template. Deployment verification compares the public assets' SHA-256 values with those produced by the build. It preserves generation of documentation, coverage and metrics; failures stop publication.

Existing ^2.0 template constraints accept 2.1.0. Consumers with a committed lock must update `fast-forward/phpdoc-bootstrap-template` before running this workflow. A locally cached vendor directory can still contain an older template; use clean dependency resolution and inspect the logged version.

PR builds use separate per-PR concurrency groups and may cancel their own obsolete runs; they never share the production deployment queue. Main deployments remain serialized, and other feature refs have their own queues. Metrics use a full repository checkout. Public identity checks retry the complete download-and-hash operation up to twelve times per asset, so stale HTTP 200 responses during Pages propagation do not pass validation or immediately fail a successful deployment.
