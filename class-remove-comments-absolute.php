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

		// Return 'closed' status when using comments_open().
		add_filter( 'comments_open', array( $this, 'close_comments' ), 20, 2 );

		// Return 'closed' status when using pings_open().
		add_filter( 'pings_open', array( $this, 'close_comments' ), 20, 2 );

		// Remove comments from the admin bar.
		add_action( 'admin_bar_menu', array( $this, 'remove_admin_bar_comment_items' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'remove_admin_bar_network_comment_items' ), 999 );

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

	/**
	 * Close comments, if open.
	 *
	 * @access public
	 * @since  0.0.1
	 *
	 * @param string|boolean $open
	 * @param string|integer $post_id
	 *
	 * @return bool|string $open
	 */
	public function close_comments( $open, $post_id ) {

		// If not open, then return.
		if ( ! $open ) {
			return $open;
		}

		$post = get_post( $post_id );
		// For all post types.
		if ( $post->post_type ) {
			return FALSE;
		} // 'closed' doesn't work; @see http://codex.wordpress.org/Option_Reference#Discussion

		return $open;
	}

	/**
	 * Remove comment items from the admin bar.
	 *
	 * @access  public
	 * @since   0.0.1
	 *
	 * @param $wp_admin_bar WP_Admin_Bar instance, passed by reference.
	 *
	 * @return null
	 */
	public function remove_admin_bar_comment_items( $wp_admin_bar ) {

		if ( ! is_admin_bar_showing() ) {
			return NULL;
		}

		// Remove comment item in blog list for "My Sites" in Admin Bar.
		if ( isset( $GLOBALS[ 'blog_id' ] ) ) {
			$wp_admin_bar->remove_node( 'blog-' . $GLOBALS[ 'blog_id' ] . '-c' );
		}
		// Remove entry in admin bar.
		$wp_admin_bar->remove_node( 'comments' );
	}

	/**
	 * Remove comment items from the network admin bar.
	 *
	 * @since    04/08/2013
	 * @internal param Array $wp_admin_bar
	 * @return void
	 */
	public function remove_admin_bar_network_comment_items() {

		if ( ! is_admin_bar_showing() ) {
			return NULL;
		}

		global $wp_admin_bar;

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( is_multisite() && is_plugin_active_for_network( REMOVE_COMMENTS_ABSOLUTE_BASENAME ) ) {

			foreach ( (array) $wp_admin_bar->user->blogs as $blog ) {
				$wp_admin_bar->remove_node( 'blog-' . $blog->userblog_id . '-c' );
			}
		}
	}
}