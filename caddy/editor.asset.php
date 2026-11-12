<?php
/**
 * Script dependencies for the block editor script (editor.js).
 *
 * @package caddy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'dependencies' => array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ),
	'version'      => defined( 'CADDY_VERSION' ) ? CADDY_VERSION : false,
);
