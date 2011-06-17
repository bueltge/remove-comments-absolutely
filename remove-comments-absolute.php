<?php
/**
 * Plugin Name: Remove Comments Absolutely
 * Plugin URI: http://wpengineer.com/2230/removing-comments-absolutely-wordpress/
 * Text Domain: remove_comments_absolute
 * Domain Path: /languages
 * Description: Deactivate comments functions and remove areas absolutely from the WordPress install
 * Author: Frank Bültge
 * Version: 0.0.4
 * Licence: GPLv2
 * Author URI: http://bueltge.de/
 * Upgrade Check: none
 * Last Change: 17.06.2011
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
			
			add_filter( 'the_posts',                  array( $this, 'set_comment_status' ) );
			
			add_filter( 'comments_open',              array( $this, 'close_comments'), 10, 2 );
			add_filter( 'pings_open',                 array( $this, 'close_comments'), 10, 2 );
			
			add_action( 'admin_init',                 array( $this, 'remove_comments' ) );
			add_action( 'admin_menu',                 array( $this, 'remove_menu_items' ) );
			add_filter( 'add_menu_classes',           array( $this, 'add_menu_classes' ) );
			
			add_action( 'admin_head',                 array( $this, 'remove_comments_areas' ) );
			
			add_action( 'wp_before_admin_bar_render', array( $this, 'admin_bar_render' ) );
			
			// remove comment feed
			remove_action( 'wp_head', 'feed_links', 2 );
			add_action( 'wp_head', array( $this, 'feed_links' ), 2 );
		}
		
		/**
		 * Handler for the action 'init'. Instantiates this class.
		 * 
		 * @access  public
		 * @since   0.0.1
		 * @return  object $classobj
		 */
		public function get_object () {
			
			if ( NULL === self :: $classobj ) {
				self :: $classobj = new self;
			}
			
			return self :: $classobj;
		}
		
		/**
		 * Set the status on posts and pages - is_singular ()
		 * 
		 * @access  public
		 * @since   0.0.1
		 * @uses    is_singular
		 * @param   string $posts
		 * @return  string $posts
		 */
		public function set_comment_status ( $posts ) {
			
			if ( ! empty( $posts ) && is_singular() ) {
				$posts[0]->comment_status = 'closed';
				$posts[0]->post_status = 'closed';
			}
			
			return $posts;
		}
		
		/**
		 * Close comments, if open
		 * 
		 * @access  public
		 * @since   0.0.1
		 * @param   string | boolean $open
		 * @param   string | integer $post_id
		 * @eturn  string $posts
		 */
		public function close_comments ( $open, $post_id ) {
			// if not open, than back
			if ( ! $open )
				return $open;
			
			$post = get_post( $post_id );
			if ( $post -> post_type ) // all post types
				return FALSE;
			
			return $open;
		}
		
		/**
		 * Change options for dont use comments
		 * Remove meta boxes on edit pages
		 * Remove support on all post types for comments
		 * Remove menu-entries
		 * 
		 * @access  public
		 * @since   0.0.1
		 * @uses    update_option, get_post_types, remove_meta_box, remove_post_type_support
		 * @return  void
		 */
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
				// remove all comments/trackbacks from tabels
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
			// remove dashboard meta box for recents comments
			remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
		}
		
		/**
		 * Remove menu-entries
		 * 
		 * @access  public
		 * @since   0.0.3
		 * @uses    remove_meta_box, remove_post_type_support
		 * @return  void
		 */
		public function remove_menu_items () {
			// Remove menu entries with WP 3.1 and higher
			if ( function_exists( 'remove_menu_page' ) ) {
				remove_menu_page( 'edit-comments.php' );
				remove_submenu_page( 'options-general.php', 'options-discussion.php' );
			} else {
				// unset comments
				unset( $GLOBALS['menu'][25] );
				// unset menuentry Discussion
				unset( $GLOBALS['submenu']['options-general.php'][25] );
			}
		}
		
		/**
		 * Add class for last menu entry with no 20
		 * 
		 * @access  public
		 * @since   0.0.1
		 * @param   array string $menu
		 * @return  array string $menu
		 */
		function add_menu_classes ( $menu ) {
			
			$menu[20][4] .= ' menu-top-last';
			
			return $menu;
		}
		
		/**
		 * Remove areas for comments in backend via JS
		 * 
		 * @access  public
		 * @since   0.0.1
		 * $return  string with js
		 */
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
		
		/**
		 * Remove comment entry in Admin Bar
		 * 
		 * @access  public
		 * @since   0.0.1
		 * @uses    remove_menu
		 * $return  void
		 */
		public function admin_bar_render () {
			// remove entry in admin bar
			$GLOBALS['wp_admin_bar'] -> remove_menu( 'comments' );
		}
		
		/**
		 * Display the links to the general feeds, without comments
		 * 
		 * @access  public
		 * @since   0.0.4
		 * @uses    current_theme_supports, wp_parse_args, feed_content_type, get_bloginfo, esc_attr, get_feed_link, _x, __
		 * @param   array $args Optional arguments
		 * @return  string
		 */
		public function feed_links( $args = array() ) {
			
			if ( ! current_theme_supports('automatic-feed-links') )
				return;
		
			$defaults = array(
				/* translators: Separator between blog name and feed type in feed links */
				'separator'	=> _x( '&raquo;', 'feed link' ),
				/* translators: 1: blog title, 2: separator (raquo) */
				'feedtitle'	=> __( '%1$s %2$s Feed' ),
			);
		
			$args = wp_parse_args( $args, $defaults );
		
			echo '<link rel="alternate" type="' . feed_content_type() . '" title="' . 
				esc_attr(sprintf( $args['feedtitle'], get_bloginfo('name'), $args['separator'] )) . 
				'" href="' . get_feed_link() . "\" />\n";
		}
	
	} // end class

} // end if class exists
?>
