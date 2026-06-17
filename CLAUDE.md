# JPKCom CF7 Upload Path – Developer Reference

## Plugin Overview

Hardens Contact Form 7 by defining `WPCF7_UPLOADS_TMP_DIR` to a protected `.ht.private` directory inside `wp-content`, moving the temporary upload location out of the publicly served web root.

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
└── define WPCF7_UPLOADS_TMP_DIR = WP_CONTENT_DIR . '/.ht.private/uploads/wpcf7_uploads' (guarded)
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
| `JPKCOM_CF7_UPLOAD_PATH_VERSION` | `'1.0.3'` | Plugin version (sync with header/README/phpdoc.xml) |
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
- Shared JPKCom updater (downstream copy of upstream `jpkcom-post-filter`; do not edit per-plugin). SHA256 verification, `wp_safe_remote_get()`, URL validation, race-condition lock, 24 h cache, timing-safe `hash_equals()`.
- Hooks: `plugins_api`, `site_transient_update_plugins`, `upgrader_process_complete`, `upgrader_pre_download`.

---

## Release Workflow

Triggered by **pushing a `v*` tag**; the workflow creates the GitHub release automatically. Pipeline: setup PHP/Python/Pandoc/GraphViz → README metadata → slug-named ZIP → SHA256 → upload ZIP + `.sha256` → `plugin_<slug>.json` manifest → PHPDoc → deploy to `gh-pages`.

---

## Security Checklist

- `declare(strict_types=1)` in every PHP file
- Guarded constant define (no overwrite, no global-scope leak)
- Upload path resolves under `wp-content/.ht.private` (out of web root)
- Updater: SHA256 verification + URL validation (audited separately)

---

## Release Checklist

1. Bump version in: header `Version:` + `Stable tag:`, `JPKCOM_CF7_UPLOAD_PATH_VERSION`, `README.md`, `phpdoc.xml`
2. Add a `### x.y.z` block to `## Changelog` in `README.md`
3. Commit, tag `vx.y.z`, push the tag → the workflow builds and publishes everything
