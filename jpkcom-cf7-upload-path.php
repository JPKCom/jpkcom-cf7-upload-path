<?php
/*
Plugin Name: JPKCom CF7 Upload Path
Plugin URI: https://github.com/JPKCom/jpkcom-cf7-upload-path
Description: Changes the default CF7 upload path string to a save value.
Version: 1.0.3
Author: Jean Pierre Kolb <jpk@jpkc.com>
Author URI: https://www.jpkc.com
Contributors: JPKCom
Tags: Security, Upload, CF7
Requires Plugins: contact-form-7
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.3
Network: true
Stable tag: 1.0.3
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
    define( 'JPKCOM_CF7_UPLOAD_PATH_VERSION', '1.0.3' );
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
