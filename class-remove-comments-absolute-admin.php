<?php
/**
 * Remove Comments Absolutely
 *
 * @package   Remove_Comments_Absolutely
 * @author    Frank Bültge
 * @license   GPL-2.0+
 * @link      https://github.com/bueltge/Remove-Comments-Absolutely/
 * @copyright 2015 Frank Bültge
 */

/**
 * Admin plugin class.
 *
 * @package   Remove_Comments_Absolutely
 * @author    Frank Bültge
 */
class Remove_Comments_Absolute_Admin {

	/**
	 * Class object.
	 *
	 * @var null
	 */
	static private $classobj;

	/**
	 * Register actions and filters.
	 *
	 * @access  public
	 * @since   0.0.1
	 * @see    add_filter()
	 * @see    add_action()
	 */
	public function __construct() {

		// Do not check for plugin updates.
		add_filter( 'site_transient_update_plugins', array( $this, 'remove_update_nag' ) );

		// Remove comment related admin pages.
		add_action( 'admin_init', array( $this, 'remove_admin_pages' ) );
	}

	/**
	 * Get an instance of this class.
	 *
	 * @access public
	 * @since  0.0.1
	 * @return null|Remove_Comments_Absolute $classobj object
	 */
	public static function get_object() {

		if ( NULL === self::$classobj ) {
			self::$classobj = new self;
		}

		return self::$classobj;
	}

	/**
	 * Do not check for plugin updates.
	 *
	 * @since  1.0.0  04/02/2012
	 * @link   http://dd32.id.au/2011/03/01/disable-plugin-update-notification-for-a-specific-plugin-in-wordpress-3-1/
	 *
	 * @param array|string $value
	 *
	 * @return array|string $value
	 */
	public function remove_update_nag( $value ) {

		if ( isset( $value->response[ plugin_basename( __FILE__ ) ] )
			&& ! empty( $value->response[ plugin_basename( __FILE__ ) ] )
		) {
			unset( $value->response[ plugin_basename( __FILE__ ) ] );
		}

		return $value;
	}

	/**
	 * Remove comment related admin pages.
	 *
	 * @since 1.3.0
	 */
	public function remove_admin_pages() {

		global $pagenow;

		if ( in_array( $pagenow, array( 'comment.php', 'edit-comments.php', 'moderation.php', 'options-discussion.php' ) ) ) {
			wp_die(
				esc_html__( 'Comments are disabled on this site.', 'remove_comments_absolute' ),
				'',
				array( 'response' => 403 )
			);
			exit();
		}
	}
}