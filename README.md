# JPKCom CF7 Upload Path

**Plugin Name:** JPKCom CF7 Upload Path  
**Plugin URI:** https://github.com/JPKCom/jpkcom-cf7-upload-path  
**Description:** Changes the default CF7 upload path string to a save value.  
**Version:** 1.0.4  
**Author:** Jean Pierre Kolb <jpk@jpkc.com>  
**Author URI:** https://www.jpkc.com  
**Contributors:** JPKCom  
**Tags:** Security, Upload, CF7  
**Requires Plugins:** contact-form-7  
**Requires at least:** 6.9  
**Tested up to:** 7.0  
**Requires PHP:** 8.3  
**Network:** true  
**Stable tag:** 1.0.4  
**License:** GPL-2.0-or-later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Changes the default CF7 upload path string to a save value.


## Description

Changes the default CF7 upload path string to a save value.

```php
<?php
define( 'WPCF7_UPLOADS_TMP_DIR', 'your-custom-tmp-dir' );
```

For more details visit: https://contactform7.com/file-uploading-and-attachment/


### Documentation

**API Documentation:** Complete PHPDoc-generated API documentation is available at:
[https://jpkcom.github.io/jpkcom-cf7-upload-path/docs/](https://jpkcom.github.io/jpkcom-cf7-upload-path/docs/)


## Installation

1. In your admin panel, go to 'Plugins' > and click the 'Add New' button.
2. Click Upload Plugin and 'Choose File', then select the Plugin's .zip file. Click 'Install Now'.
3. Make sure 'Contact Form 7' plugin is activated.
4. Click 'Activate' to use the plugin right away.


## Changelog

### 1.0.4
* Security: update packages are now verified *before* installation — the verified file is handed to WordPress instead of being downloaded a second time, so the bytes that were checked are the bytes that get installed
* Security: a missing or unfetchable SHA-256 checksum now aborts the update instead of installing unverified code (previously it silently skipped verification)
* Security: pinned every GitHub Action to a full commit SHA and added Dependabot with a 7-day cooldown, so a moved tag can no longer change the release build
* Security: tightened which download the updater claims, so sibling plugins cannot match each other's package
* Fixed: `sprintf()` calls in the updater bound named arguments to a variadic parameter, which raises `ArgumentCountError` on PHP 8.3
* Fixed: the "View Details" modal could fail with a `TypeError` when the manifest omitted `requires_plugins`
* Performance: a failed manifest fetch is now cached for an hour instead of being retried on every admin request
* Added: CI workflow on every pull request (PHP lint, named-argument check, YAML validation, action-pinning guard)
* Security: the plugin now writes its own `.htaccess`, `index.php` and `web.config` into the CF7 upload directory and its `.ht.private` parent instead of relying on Contact Form 7 and server configuration; writes are containment-checked against `WP_CONTENT_DIR` and never overwrite existing files

### 1.0.3
* Added secure self-hosted plugin updates via GitHub with SHA256 checksum verification
* Added an automated release workflow (builds the ZIP, generates the manifest and deploys to gh-pages on tag push)
* Raised the minimum WordPress version to 6.9 and "Tested up to" to WordPress 7.0
* Switched license metadata to the SPDX identifier `GPL-2.0-or-later` with the HTTPS license URI
* Added PHPDoc-generated API documentation, built and deployed to gh-pages on release
* Hardening: enabled `declare(strict_types=1)`, guarded the constant definition and removed the global-scope variable

### 1.0.2
* Version update

### 1.0.1
* Updated README.md

### 1.0.0
* Initial Release
