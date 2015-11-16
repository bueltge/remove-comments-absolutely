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

	/**
	 * Register actions and filters.
	 *
	 * @access  public
	 * @since   0.0.1
	 * @see     add_filter(), add_action()
	 */
	public function __construct() {

		// Return empty array when querying comments.
		add_filter( 'the_comments', array( $this, 'filter_the_comments' ) );

		// Remove post type support for comments and trackbacks.
		add_action( 'init', array( $this, 'remove_post_type_support' ) );

		// Set comment status to closed on posts and pages.
		add_filter( 'the_posts', array( $this, 'filter_post_comment_status' ) );

	}

	/**
	 * Return an empty array when retrieving comments via get_comments().
	 *
	 * Eg. Takes care of the 'Activity' dashboard widget.
	 *
	 * Note: Maybe it is better to add an action to 'pre_get_comments'?
	 *
	 * @param  array $comments Comments
	 * @return array
	 */
	public function filter_the_comments( $comments ) {
		return array();
	}

	/**
	 * Remove post type support for comments and trackbacks.
	 */
	public function remove_post_type_support() {
		foreach ( get_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	/**
	 * Set comment status to closed on posts and pages - is_singular().
	 *
	 * @access public
	 * @since  0.0.1
	 * @see   is_singular()
	 *
	 * @param string $posts
	 *
	 * @return string $posts
	 */
	public function filter_post_comment_status( $posts ) {
		if ( ! empty( $posts ) && is_singular() ) {
			$posts[ 0 ]->comment_status = 'closed';
			$posts[ 0 ]->ping_status    = 'closed';
		}

		return $posts;
	}
}