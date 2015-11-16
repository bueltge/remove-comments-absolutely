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
 * Main plugin class.
 *
 * @package   Remove_Comments_Absolutely
 * @author    Frank Bültge
 */
class Remove_Comments_Absolute {

	/**
	 * Class object.
	 *
	 * @var null
	 */
	static private $classobj;

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
}