<?php
/*
Plugin Name: JPKCom CF7 Upload Path
Plugin URI: https://github.com/JPKCom/jpkcom-cf7-upload-path
Description: Changes the default CF7 upload path string to a save value.
Version: 1.0.5
Author: Jean Pierre Kolb <jpk@jpkc.com>
Author URI: https://www.jpkc.com
Contributors: JPKCom
Tags: Security, Upload, CF7
Requires Plugins: contact-form-7
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.3
Network: true
Stable tag: 1.0.5
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

declare(strict_types=1);

if ( ! defined( constant_name: 'WPINC' ) ) {
	die;
}


/**
 * Plugin Constants
 *
 * @since 1.0.3
 */
if ( ! defined( 'JPKCOM_CF7_UPLOAD_PATH_VERSION' ) ) {
    define( 'JPKCOM_CF7_UPLOAD_PATH_VERSION', '1.0.5' );
}


/**
 * Initialize Plugin Updater
 *
 * Loads and initializes the GitHub-based plugin updater with SHA256 checksum verification.
 *
 * @since 1.0.3
 *
 * @return void
 */
add_action( 'init', static function (): void {
    $updater_file = plugin_dir_path( __FILE__ ) . 'includes/class-plugin-updater.php';

    if ( file_exists( $updater_file ) ) {
        require_once $updater_file;

        if ( class_exists( 'JPKComCf7UploadPathGitUpdate\\JPKComGitPluginUpdater' ) ) {
            new \JPKComCf7UploadPathGitUpdate\JPKComGitPluginUpdater(
                plugin_file: __FILE__,
                current_version: JPKCOM_CF7_UPLOAD_PATH_VERSION,
                manifest_url: 'https://jpkcom.github.io/jpkcom-cf7-upload-path/plugin_jpkcom-cf7-upload-path.json'
            );
        }
    }
}, 5 );

/**
 * Move Contact Form 7's temporary upload directory out of the web root.
 *
 * Points `WPCF7_UPLOADS_TMP_DIR` at a protected `.ht.private` location inside
 * `wp-content` so uploaded files are not served directly.
 *
 * @since 1.0.0
 */
if ( ! defined( 'WPCF7_UPLOADS_TMP_DIR' ) ) {
    define( 'WPCF7_UPLOADS_TMP_DIR', WP_CONTENT_DIR . '/.ht.private/uploads/wpcf7_uploads' );
}


/**
 * Access guards written into the upload directories.
 *
 * @since 1.0.4
 *
 * @return array<string, string> Map of file name => file contents.
 */
function jpkcom_cf7_upload_path_guard_files(): array {
    return [
        // Mirrors the syntax Contact Form 7 uses itself: the 2.4+ block is the
        // one that actually applies on current Apache, the 2.2 block is the
        // fallback for hosts without mod_authz_core.
        '.htaccess'  => "# Apache 2.4+\n"
            . "<IfModule authz_core_module>\n    Require all denied\n</IfModule>\n\n"
            . "# Apache 2.2\n"
            . "<IfModule !authz_core_module>\n    Deny from all\n</IfModule>\n",
        // Defeats directory listing where the server would otherwise index it.
        'index.php'  => "<?php\n// Silence is golden.\n",
        'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<configuration>\n  <system.webServer>\n    <authorization>\n"
            . "      <deny users=\"*\" />\n"
            . "    </authorization>\n  </system.webServer>\n</configuration>\n",
    ];
}


/**
 * Ensure the CF7 temporary upload directory carries its own access protection.
 *
 * This plugin picks the upload location, so it should also guarantee that the
 * location is unreachable over HTTP. Until now that guarantee was borrowed from
 * two external facts, neither of which this plugin controls:
 *
 * 1. Contact Form 7 drops its own `.htaccess` into the directory. True today,
 *    but it is CF7's implementation detail, not a contract.
 * 2. Many server configs deny any request path containing a dot-segment, which
 *    is what actually protects the `.ht.private` prefix on nginx — `.htaccess`
 *    is ignored there entirely.
 *
 * Note that the `.ht.` prefix alone does *not* invoke Apache's stock
 * `<FilesMatch "^\.ht">` rule: that matches the requested file name, not the
 * directories above it, so `.ht.private/…/file.pdf` is not covered by it.
 *
 * Writing the guards here makes the protection a property of this plugin
 * instead of an inherited coincidence. Runs on activation and re-verifies once
 * a day, so a deleted guard file heals itself.
 *
 * @since 1.0.4
 *
 * @return void
 */
function jpkcom_cf7_upload_path_protect_dir(): void {
    if ( ! defined( 'WPCF7_UPLOADS_TMP_DIR' ) ) {
        return;
    }

    $target = (string) WPCF7_UPLOADS_TMP_DIR;

    if ( ! wp_mkdir_p( $target ) ) {
        return;
    }

    // Containment check: only ever write inside wp-content, even if the
    // constant was redefined elsewhere to point somewhere unexpected.
    $content_dir = realpath( WP_CONTENT_DIR );
    $real_target = realpath( $target );

    if ( false === $content_dir || false === $real_target ) {
        return;
    }

    $content_dir = rtrim( $content_dir, '/\\' ) . DIRECTORY_SEPARATOR;

    if ( ! str_starts_with( $real_target . DIRECTORY_SEPARATOR, $content_dir ) ) {
        return;
    }

    // Guard the whole subtree this plugin introduces, not just the leaf: the
    // parent '.ht.private' is ours too, and protecting it stops traversal into
    // any sibling directory a future version might add.
    $private_root = realpath( WP_CONTENT_DIR . '/.ht.private' );
    $dirs         = [ $real_target ];

    if ( false !== $private_root && str_starts_with( $private_root . DIRECTORY_SEPARATOR, $content_dir ) ) {
        $dirs[] = $private_root;
    }

    foreach ( array_unique( $dirs ) as $dir ) {
        foreach ( jpkcom_cf7_upload_path_guard_files() as $name => $contents ) {
            $file = $dir . DIRECTORY_SEPARATOR . $name;

            // Never clobber an existing guard – CF7 maintains its own
            // .htaccess and an administrator may have hardened it further.
            if ( file_exists( $file ) ) {
                continue;
            }

            @file_put_contents( $file, $contents, LOCK_EX );
        }
    }
}

register_activation_hook( __FILE__, 'jpkcom_cf7_upload_path_protect_dir' );

/**
 * Re-verify the guards periodically without touching the filesystem on every
 * request. The transient read is cheap; the filesystem work happens once a day.
 *
 * Priority 20 keeps this after CF7's own `wpcf7_init_uploads()` (priority 10),
 * so CF7 creates the directory first and we only fill in what is missing.
 *
 * @since 1.0.4
 */
add_action( 'wpcf7_init', static function (): void {
    if ( get_transient( 'jpkcom_cf7_upload_path_guarded' ) ) {
        return;
    }

    jpkcom_cf7_upload_path_protect_dir();

    set_transient( 'jpkcom_cf7_upload_path_guarded', true, DAY_IN_SECONDS );
}, 20 );
