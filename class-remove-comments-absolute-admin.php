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
}