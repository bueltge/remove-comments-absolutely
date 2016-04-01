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
	 * @see     add_filter()
	 * @see     add_action()
	 */
	public function __construct() {

		// Do not check for plugin updates.
		add_filter( 'site_transient_update_plugins', array( $this, 'remove_update_nag' ) );

		// Remove comment related admin pages.
		add_action( 'admin_init', array( $this, 'remove_admin_pages' ) );

		// Remove comment related admin menu items.
		add_action( 'admin_menu', array( $this, 'remove_menu_items' ) );

		// Remove commentsdiv meta box.
		add_action( 'admin_init', array( $this, 'remove_commentsdiv_meta_box' ) );

		// Remove "Turn comments on or off" from the Welcome Panel.
		add_action( 'admin_footer-index.php', array( $this, 'remove_welcome_panel_item' ) );

		// Remove comment options from profile page.
		add_action( 'personal_options', array( $this, 'remove_profile_items' ) );

		// Remove 'Discussion Settings' help tab from post edit screen.
		add_action( 'admin_head-post.php', array( $this, 'remove_help_tabs' ), 10, 3 );
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

		if ( in_array(
			$pagenow, array( 'comment.php', 'edit-comments.php', 'moderation.php', 'options-discussion.php' )
		) ) {
			wp_die(
				esc_html__( 'Comments are disabled on this site.', 'remove_comments_absolute' ),
				'',
				array( 'response' => 403 )
			);
			exit();
		}
	}

	/**
	 * Remove admin menu items.
	 *
	 * @access public
	 * @since  0.0.3
	 * @see    remove_menu_page()
	 * @see    remove_submenu_page()
	 * @return void
	 */
	public function remove_menu_items() {

		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	/**
	 * Remove commentsdiv meta box.
	 *
	 * Note: Removing post type support for comments removes commentstatusdiv and trackbacksdiv.
	 *       This does not affect commentsdiv as it is still registered if $post->comment_count is bigger than 0.
	 *
	 * @access public
	 * @since  1.2.4
	 * @see    get_post_types()
	 * @see    remove_meta_box()
	 * @return void
	 */
	public function remove_commentsdiv_meta_box() {

		foreach ( get_post_types() as $post_type ) {
			remove_meta_box( 'commentsdiv', $post_type, 'normal' );
		}
	}

	/**
	 * Remove "Turn comments on or off" from the Welcome Panel.
	 *
	 * @access  public
	 * @since   0.0.1
	 * @return  string with js
	 */
	public function remove_welcome_panel_item() {

		?>
		<script type="text/javascript">
			//<![CDATA[
			jQuery( document ).ready( function( $ ) {
				// Welcome screen action "Turn comments on or off"
				$( '.welcome-comments' ).parent().remove();
			} );
			//]]>
		</script>
		<?php
	}

	/**
	 * Remove options for Keyboard Shortcuts from profile page.
	 *
	 * @since  09/03/2012
	 *
	 * @return void
	 */
	public function remove_profile_items() {

		echo '<style type="text/css">.user-comment-shortcuts-wrap{display:none;}</style>';
	}

	/**
	 * Remove 'Discussion Settings' help tab from post edit screen.
	 *
	 * @since  2016-01-01
	 *
	 * @access private
	 */
	public function remove_help_tabs() {

		$current_screen = get_current_screen();
		if ( $current_screen->get_help_tab( 'discussion-settings' ) ) {
			$current_screen->remove_help_tab( 'discussion-settings' );
		}
	}
}
