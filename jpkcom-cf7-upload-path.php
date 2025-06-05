<?php
/*
Plugin Name: JPKCom CF7 Upload Path
Plugin URI: https://github.com/JPKCom/jpkcom-cf7-upload-path
Description: Changes the default CF7 upload path string to a save value.
Version: 1.0.0
Author: Jean Pierre Kolb <jpk@jpkc.com>
Author URI: https://www.jpkc.com
Contributors: JPKCom
Tags: Security, Upload, CF7
Requires Plugins: contact-form-7
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.3
Network: true
Stable tag: 1.0.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
GitHub Plugin URI: JPKCom/jpkcom-cf7-upload-path
Primary Branch: main
*/

if ( ! defined( constant_name: 'WPINC' ) ) {
	die;
}

$jpkcomCf7UploadPath = WP_CONTENT_DIR . '/.ht.private/uploads/wpcf7_uploads';

define( constant_name: 'WPCF7_UPLOADS_TMP_DIR', value: $jpkcomCf7UploadPath );
