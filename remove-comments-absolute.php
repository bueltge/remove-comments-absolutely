<?php
/**
 * Remove WordPress comment functions and interface elements.
 *
 * @package   Remove_Comments_Absolutely
 * @author    Frank Bültge
 * @license   GPLv3+
 * @link      https://github.com/bueltge/Remove-Comments-Absolutely/
 * @copyright 2015 Frank Bültge
 *
 * Plugin Name: Remove Comments Absolutely
 * Plugin URI:  https://github.com/bueltge/Remove-Comments-Absolutely/
 * Description: Deactivate comments functions and remove areas absolutely from the WordPress install
 * Version:     1.2.4
 * Author:      Frank Bültge
 * Author URI:  http://bueltge.de/
 * License:     GPLv3+
 * Domain Path: /languages
 * Text Domain: remove_comments_absolute
 * Last access: 2015-11-16
 *
 * Read the original post about the plugin:
 * http://wpengineer.com/2230/removing-comments-absolutely-wordpress/
 */

// Don't load directly.
defined( 'ABSPATH' ) or die();

require_once( 'class-remove-comments-absolute.php' );
add_action( 'plugins_loaded', array( 'Remove_Comments_Absolute', 'get_object' ), 0 );

if ( is_admin() ) {
	define( 'REMOVE_COMMENTS_ABSOLUTE_BASENAME', plugin_basename( __FILE__ ) );
	require_once( 'class-remove-comments-absolute-admin.php' );
	add_action( 'plugins_loaded', array( 'Remove_Comments_Absolute_Admin', 'get_object' ), 1 );
}
