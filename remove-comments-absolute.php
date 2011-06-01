<?php
/**
 * Plugin Name: Remove Comments Absolute
 * Plugin URI: http://bueltge.de/
 * Text Domain: remove_comments_absolute
 * Domain Path: /languages
 * Description: Deactivate comments functions and remove areas absolute form the WordPress install
 * Author: Frank Bültge
 * Version: 0.0.1
 * Licence: GPLv2
 * Author URI: http://bueltge.de
 * Upgrade Check: none
 * Last Change: 01.06.2011
 */

if ( ! class_exists( 'Remove_Comments_Absolute' ) ) {
	add_action( 'plugins_loaded', array( 'Remove_Comments_Absolute', 'get_object' ) );
	
	class Remove_Comments_Absolute {
		
		static private $classobj = NULL;
		
		/**
		 * Constructor, init on defined hooks of WP and include second class
		 * 
		 * @access  public
		 * @since   0.0.1
		 * @uses    add_filter, add_action
		 * @return  void
		 */
		public function __construct () {
			
			add_filter( 'the_posts', array( $this, 'set_comment_status' ) );
			
			add_filter( 'comments_open', array( $this, 'close_comments', 10, 2 ) );
			add_filter( 'pings_open', array( $this, 'close_comments', 10, 2 ) );
			
			add_action( 'admin_init', array( $this, 'remove_comments' ) );
			
			add_action( 'admin_head', array( $this, 'remove_comments_areas' ) );
			
			add_action( 'wp_before_admin_bar_render', array( $this, 'admin_bar_render' ) );
		}
		
		/**
		 * Handler for the action 'init'. Instantiates this class.
		 * 
		 * @access public
		 * @since 0.0.1
		 * @return $classobj
		 */
		public function get_object () {
			
			if ( NULL === self :: $classobj ) {
				self :: $classobj = new self;
			}
			
			return self :: $classobj;
		}
		
		// set the status on posts and pages - is_singular ()
		public function set_comment_status( $posts ) {
			
			if ( ! empty( $posts ) && is_singular() ) {
				$posts[0]->comment_status = 'closed';
				$posts[0]->post_status = 'closed';
			}
			
			return $posts;
		}
		
		public function close_comments( $open, $post_id ) {
			// if ! open, than back
			if ( ! $open )
				return $open;
			
			$post = get_post( $post_id );
			if ( $post -> post_type ) // all post types
				return FALSE;
			
			return $open;
		}
		
		public function remove_comments () {
			// int values
			foreach ( array( 'comments_notify', 'default_pingback_flag' ) as $option )
				update_option( $option, 0 );
			// string false
			foreach ( array( 'default_comment_status', 'default_ping_status' ) as $option )
				update_option( $option, 'false' );
			
			// all post types
			// alternative define an array( 'post', 'page' )
			foreach ( get_post_types() as $post_type ) {
				// comment status
				remove_meta_box( 'commentstatusdiv', $post_type, 'normal' );
				// remove trackbacks
				remove_meta_box( 'trackbacksdiv', $post_type, 'normal' );
				// remove all commnts from tabels
				remove_post_type_support( $post_type, 'comments' );
			}
			// remove dashboard meta box for recents comments
			remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
			
			// unset menuentry Discussion
			unset( $GLOBALS['submenu']['options-general.php'][25] );
		}
		
		public function remove_comments_areas () {
			?>
			<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function($) {
				$( '.table_discussion' ).remove();
			});
			//]]>
			</script>
			<?php
		}
		
		public function admin_bar_render () {
			// remove entry in admin bar
			$GLOBALS['wp_admin_bar'] -> remove_menu( 'comments' );
		}
		
	
	} // end class

} // end if class exists
?>