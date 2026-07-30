# JPKCom CF7 Upload Path – Developer Reference

## Plugin Overview

Hardens Contact Form 7 by defining `WPCF7_UPLOADS_TMP_DIR` to a protected `.ht.private` directory inside `wp-content`, and by writing the HTTP access guards for that directory itself.

> **Wording note:** the directory is *inside* the document root, not outside it — `wp-content` is web-served. What makes it unreachable is the access guards plus the dot-segment prefix, not its location. Earlier revisions of this file claimed "out of the web root"; that was wrong and is worth not reintroducing.

- **Text Domain:** `jpkcom-cf7-upload-path` (no header declared, defaults to slug; only used by the shared updater)
- **Requires Plugins:** `contact-form-7`
- **Min PHP:** 8.3 | **Min WP:** 6.9
- **Network:** network-active (`Network: true`)

---

## Architecture

```
Main file (jpkcom-cf7-upload-path.php)
├── declare(strict_types=1)
├── Plugin header (Requires Plugins: contact-form-7, Network: true)
├── JPKCOM_CF7_UPLOAD_PATH_VERSION constant
├── init @ priority 5: boot JPKComGitPluginUpdater
├── define WPCF7_UPLOADS_TMP_DIR = WP_CONTENT_DIR . '/.ht.private/uploads/wpcf7_uploads' (guarded)
├── jpkcom_cf7_upload_path_guard_files()   → .htaccess / index.php / web.config bodies
├── jpkcom_cf7_upload_path_protect_dir()   → mkdir + containment check + write missing guards
├── register_activation_hook               → write guards on activation
└── wpcf7_init @ priority 20               → re-verify once a day (transient-gated, after CF7's own init)
```

---

## Behaviour

| Constant defined | Value | Effect |
|------------------|-------|--------|
| `WPCF7_UPLOADS_TMP_DIR` | `WP_CONTENT_DIR . '/.ht.private/uploads/wpcf7_uploads'` | CF7 stores temp uploads outside the web root |

The define is guarded with `! defined()` so an existing definition is not overwritten; no intermediate global-scope variable is used.

---

## Constants

| Constant | Value | Purpose |
|----------|-------|---------|
| `JPKCOM_CF7_UPLOAD_PATH_VERSION` | matches the header `Version:` | Plugin version (sync with header/README/phpdoc.xml) |
| `WPCF7_UPLOADS_TMP_DIR` | path | CF7 temporary upload directory (consumed by Contact Form 7) |

---

## File Structure

```
jpkcom-cf7-upload-path/
├── jpkcom-cf7-upload-path.php    ← Main: header, constant, CF7 path define, updater bootstrap
├── includes/
│   └── class-plugin-updater.php  ← GitHub auto-updater (namespace: JPKComCf7UploadPathGitUpdate)
├── .github/workflows/release.yml ← Build ZIP, manifest, PHPDoc, deploy to gh-pages (on tag push)
├── phpdoc.xml                    ← phpDocumentor config
├── README.md                     ← Public readme (source for the WP plugin modal)
├── CLAUDE.md                     ← This file
├── LICENSE                       ← GPL-2.0-or-later
└── .gitignore
```

---

## Plugin Updater

- **Namespace:** `JPKComCf7UploadPathGitUpdate\JPKComGitPluginUpdater`
- **Manifest URL:** `https://jpkcom.github.io/jpkcom-cf7-upload-path/plugin_jpkcom-cf7-upload-path.json`
- Shared JPKCom updater (downstream copy of upstream `jpkcom-post-filter`; do not edit per-plugin). SHA256 verification, `wp_safe_remote_get()`, URL validation, race-condition lock, 24 h cache, timing-safe `hash_equals()`. Checksum verification is **mandatory**: a missing or unfetchable `checksum_sha256` aborts the update instead of installing unverified code. The verified temp file is returned from `upgrader_pre_download`, so WordPress installs exactly the bytes that were hashed (no second download). Failed manifest fetches are negatively cached for 1 h.
- Hooks: `plugins_api`, `site_transient_update_plugins`, `upgrader_process_complete`, `upgrader_pre_download`.

---

## Release Workflow

**Actions are pinned to commit SHAs.** Every `uses:` line in `.github/workflows/` references a 40-character commit SHA instead of a tag (`@v4`), with the version as a trailing comment. A tag is a movable pointer and can be repointed; a SHA cannot. Since the release workflow builds the plugin ZIP **and** the SHA256 checksum the auto-updater trusts, a compromised action would ship a tampered ZIP together with a matching checksum — the checksum secures the transport, the pinning secures the build. `.github/dependabot.yml` keeps the pins current weekly in one combined PR; when updating, always change the SHA *and* the version comment together.

**CI** (`.github/workflows/ci.yml`) runs on every pull request *and* on every push to `main` — a required status check only covers pull requests, so a direct push with bypass rights would otherwise skip the checks entirely. It runs `php -l` over all PHP files; flags invalid named arguments to internal PHP functions (catches `sprintf(format:, values:)` → `ArgumentCountError`, which `php -l` does not see); validates the YAML of every `.github` file; asserts every action is pinned to a 40-character commit SHA; and executes `tests/test-*.php` where present.

**Dependabot auto-merge** (`.github/workflows/dependabot-auto-merge.yml`) merges only `semver-patch` and `semver-minor`, and only PRs from `dependabot[bot]` in this repo — never from forks. Major updates get a comment and stay manual. Two repo settings are prerequisites, otherwise this is useless or outright dangerous: "Allow auto-merge" must be enabled, and branch protection must list `CI / Lint & Guards` as a **required status check** — without it `gh pr merge --auto` merges *immediately*, since there is nothing left to wait for. Together with `cooldown: default-days: 7` no action release is adopted during its first week.

Triggered by **pushing a `v*` tag**; the workflow creates the GitHub release automatically. Pipeline: setup PHP/Python/Pandoc/GraphViz → README metadata → slug-named ZIP → SHA256 → upload ZIP + `.sha256` → `plugin_<slug>.json` manifest → PHPDoc → deploy to `gh-pages`.

---

## Security Checklist

- `declare(strict_types=1)` in every PHP file
- Guarded constant define (no overwrite, no global-scope leak)
- Upload path resolves under `wp-content/.ht.private` (inside the document root, protected by the guards below)
- Plugin writes its own `.htaccess` / `index.php` / `web.config` into the upload dir **and** its `.ht.private` parent
- Guard writes are containment-checked: nothing is written unless the resolved path is inside `WP_CONTENT_DIR`
- Existing guard files are never overwritten (CF7 maintains its own `.htaccess`; admins may harden further)
- Updater: SHA256 verification + URL validation (audited separately)

### Why the plugin writes its own guards

The protection used to be inherited rather than owned, from two facts this plugin does not control:

1. **Contact Form 7 writes its own `.htaccess`** into the tmp dir (`includes/file.php`, `wpcf7_init_uploads()`), using `Require all denied` with a `Deny from all` fallback. True in CF7 6.1.6, but an implementation detail, not a contract.
2. **Server config denies dot-segments.** This is what actually protects `.ht.private` on nginx, where `.htaccess` is ignored outright (e.g. `location ~ /\. { deny all; }`).

Important: the `.ht.` prefix does **not** by itself trigger Apache's stock `<FilesMatch "^\.ht">` rule. That rule matches the requested *file name*, not the directories above it, so `.ht.private/…/file.pdf` is not covered by it. The prefix helps only where the server denies dot-*paths*.

Writing the guards in this plugin makes the protection a property of the plugin. On nginx, `.htaccess` still does nothing — there the dot-segment rule (or an explicit `location` deny) remains the real control.

---

## Release Checklist

1. Bump version in: header `Version:` + `Stable tag:`, `JPKCOM_CF7_UPLOAD_PATH_VERSION`, `README.md`, `phpdoc.xml`
2. Add a `### x.y.z` block to `## Changelog` in `README.md`
3. Commit, tag `vx.y.z`, push the tag → the workflow builds and publishes everything
