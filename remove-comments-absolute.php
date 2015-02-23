<?php
/**
 * Plugin Name:   Remove Comments Absolutely
 * Plugin URI:    http://wpengineer.com/2230/removing-comments-absolutely-wordpress/
 * Text Domain:   remove_comments_absolute
 * Domain Path:   /languages
 * Description:   Deactivate comments functions and remove areas absolutely from the WordPress install
 * Author:        Frank Bültge
 * Version:       1.2.1
 * Last access:   2014-08-29
 * License:       GPLv2
 * Author URI:    http://bueltge.de/
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
		 * @return \Remove_Comments_Absolute
		 */
		public function __construct() {
			
			// remove update check
			add_filter( 'site_transient_update_plugins', array( $this, 'remove_update_nag' ) );
			
			add_filter( 'the_posts',                     array( $this, 'set_comment_status' ) );
			
			add_filter( 'comments_open',                 array( $this, 'close_comments'), 20, 2 );
			add_filter( 'pings_open',                    array( $this, 'close_comments'), 20, 2 );
			
			add_action( 'admin_init',                    array( $this, 'remove_comments' ) );
			add_action( 'admin_menu',                    array( $this, 'remove_menu_items' ) );
			add_filter( 'add_menu_classes',              array( $this, 'add_menu_classes' ) );
			
			// remove items in dashboard
			add_action( 'admin_footer-index.php',        array( $this, 'remove_dashboard_comments_areas' ) );
			
			// change admin bar items
			add_action( 'admin_bar_menu',                array( $this, 'remove_admin_bar_comment_items' ), 999 );
			add_action( 'admin_bar_menu',                array( $this, 'remove_network_comment_items' ), 999 );
			
			// remove string on frontend in Theme
			add_filter( 'gettext',                       array( $this, 'remove_theme_string' ), 20, 2 );
			
			// remove comment feed
			remove_action( 'wp_head',                    'feed_links', 2 );
			remove_action( 'wp_head',                    'feed_links_extra', 3 );
			add_action( 'wp_head',                       array( $this, 'feed_links' ), 2 );
			add_action( 'wp_head',                       array( $this, 'feed_links_extra' ), 3 );
			add_action( 'template_redirect',             array( $this, 'filter_query' ), 9 );
			add_filter( 'wp_headers',                    array( $this, 'filter_wp_headers' ) );
			
			// remove default comment widget
			add_action( 'widgets_init',                  array( $this, 'unregister_default_wp_widgets' ), 1 );
			
			// remove comment options in profile page
			add_action( 'personal_options',              array( $this, 'remove_profile_items' ) );
			
			// replace xmlrpc methods
			add_filter( 'xmlrpc_methods',                array( $this, 'xmlrpc_replace_methods' ) );
		}
		
		/**
		 * Handler for the action 'init'. Instantiates this class.
		 * 
		 * @access  public
		 * @since   0.0.1
		 * @return  object $classobj
		 */
		public static function get_object() {
			
			if ( NULL === self::$classobj ) {
				self::$classobj = new self;
			}
			
			return self::$classobj;
		}

		/**
		 * Disable plugin update notifications
		 *
		 * @since  1.0.0  04/02/2012
		 * @link   http://dd32.id.au/2011/03/01/disable-plugin-update-notification-for-a-specific-plugin-in-wordpress-3-1/
		 * @param  array|string $value
		 * @return array|string $value
		 */
		public function remove_update_nag( $value ) {

			if ( isset( $value->response[ plugin_basename( __FILE__ ) ] )
				&& ! empty( $value->response[ plugin_basename( __FILE__ ) ] )
			)
				unset( $value->response[ plugin_basename( __FILE__ ) ] );
			
			return $value;
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
		public function set_comment_status( $posts ) {

			if ( ! empty( $posts ) && is_singular() ) {
				$posts[0]->comment_status = 'closed';
				$posts[0]->ping_status    = 'closed';
			}
			
			return $posts;
		}

		/**
		 * Close comments, if open
		 *
		 * @access  public
		 * @since   0.0.1
		 * @param   string|boolean $open
		 * @param   string|integer $post_id
		 * @return  bool|string $open
		 */
		public function close_comments( $open, $post_id ) {
			
			// if not open, than back
			if ( ! $open )
				return $open;
			
			$post = get_post( $post_id );
			if ( $post->post_type ) // all post types
				return FALSE; // 'closed' don`t work; @see http://codex.wordpress.org/Option_Reference#Discussion
			
			return $open;
		}
		
		/**
		 * Return default closed comment status
		 * 
		 * @since   04/08/2013
		 * @return  String
		 */
		public function __return_closed() {
			
			return 'closed';
		}
		
		/**
		 * Change options for don't use comments
		 * Remove meta boxes on edit pages
		 * Remove support on all post types for comments
		 * Remove menu-entries
		 * Disallow comments pages direct access
		 * 
		 * @access  public
		 * @since   0.0.1
		 * @return  void
		 */
		public function remove_comments() {
			
			// int values
			foreach ( array( 'comments_notify', 'default_pingback_flag' ) as $option ) {
				add_filter( 'pre_option_' . $option, '__return_zero' );
				//update_option( $option, 1 );
			}
			// string false
			foreach ( array( 'default_comment_status', 'default_ping_status' ) as $option ) {
				add_filter( 'pre_option_' . $option, array( $this, '__return_closed' ) );
				//update_option( $option, 'closed' );
			}
			// all post types
			// alternative define an array( 'post', 'page' )
			foreach ( get_post_types() as $post_type ) {
				// comment status
				remove_meta_box( 'commentstatusdiv', $post_type, 'normal' );
				// remove trackbacks
				remove_meta_box( 'trackbacksdiv', $post_type, 'normal' );
				// remove all comments/trackbacks from tables
				if ( post_type_supports( $post_type, 'comments' ) ) {
					remove_post_type_support( $post_type, 'comments' );
					remove_post_type_support( $post_type, 'trackbacks' );
				}
			}
			// all comments pages
			foreach ( array( 'comment.php', 'edit-comments.php', 'moderation.php', 'options-discussion.php' ) as $comments_page ) {
				if ( basename( $_SERVER['PHP_SELF'] ) == $comments_page ) {
					wp_redirect( admin_url() );
					exit;
				}
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
		public function remove_menu_items() {
			
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
		function add_menu_classes( $menu ) {
			
			if ( isset( $menu[20][4] ) )
				$menu[20][4] .= ' menu-top-last';
			
			return $menu;
		}
		
		/**
		 * Remove comment related elements from the admin dashboard via JS
		 * 
		 * @access  public
		 * @since   0.0.1
		 * $return  string with js
		 */
		public function remove_dashboard_comments_areas() {
			?>
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready( function($) {
					// Welcome screen
					$( '.welcome-comments' ).remove();
					// 'Right Now' dashboard widget
					$( 'div.table_discussion:first' ).remove();
					// 'Right Now' dashbaord widget since WP version 3.8, second ID since WP 4.0
					$( 'div#dash-right-now, #dashboard_right_now' ).find( '.comment-count').remove();
					// 'Activity' dashboard widget, since WP version 3.8
					$( 'div#dashboard_activity').find( '#latest-comments' ).remove();
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
		 * @param   $wp_admin_bar
		 * $return  void
		 */
		public function remove_admin_bar_comment_items( $wp_admin_bar ) {
			
			if ( ! is_admin_bar_showing() )
				return NULL;
			
			// remove comment item in blog -list for "My Sites" in Admin Bar
			if ( isset( $GLOBALS['blog_id'] ) )
				$wp_admin_bar->remove_node( 'blog-' . $GLOBALS['blog_id'] . '-c' );
			// remove entry in admin bar
			$wp_admin_bar->remove_node( 'comments' );
		}

		/**
		 * Remove comments item on network admin bar
		 *
		 * @since    04/08/2013
		 * @internal param Array $wp_admin_bar
		 * @return   void
		 */
		public function remove_network_comment_items() {
			
			if ( ! is_admin_bar_showing() )
				return NULL;

			global $wp_admin_bar;

			if ( ! function_exists( 'is_plugin_active_for_network' ) )
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			
			if ( is_multisite() && is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
			
				foreach( (array) $wp_admin_bar->user->blogs as $blog ) {
					$wp_admin_bar->remove_node( 'blog-' . $blog->userblog_id . '-c' );
				}
			}
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
			
			if ( ! current_theme_supports( 'automatic-feed-links' ) )
				return NULL;
		
			$defaults = array(
				// translators: Separator between blog name and feed type in feed links
				'separator' => _x( '&raquo;', 'feed link', 'remove_comments_absolute' ),
				// translators: 1: blog title, 2: separator (raquo)
				'feedtitle' => __( '%1$s %2$s Feed', 'remove_comments_absolute' ),
			);
		
			$args = wp_parse_args( $args, $defaults );
		
			echo '<link rel="alternate" type="' . feed_content_type() . '" title="' . 
				esc_attr(
					sprintf(
						$args['feedtitle'],
						get_bloginfo('name'),
						$args['separator']
					)
				) . '" href="' . get_feed_link() . '"/>' . "\n";
		}
		
		/**
		 * Display the links to the extra feeds such as category feeds.
		 * Copy from WP default, but without comment feed; no filter available
		 *
		 * @since 04/08/2013
		 * @param array $args Optional arguments.
		 */
		public function feed_links_extra( $args = array() ) {
			
			$defaults = array(
				/* translators: Separator between blog name and feed type in feed links */
				'separator'   => _x('&raquo;', 'feed link'),
				/* translators: 1: blog name, 2: separator(raquo), 3: category name */
				'cattitle'    => __('%1$s %2$s %3$s Category Feed'),
				/* translators: 1: blog name, 2: separator(raquo), 3: tag name */
				'tagtitle'    => __('%1$s %2$s %3$s Tag Feed'),
				/* translators: 1: blog name, 2: separator(raquo), 3: author name  */
				'authortitle' => __('%1$s %2$s Posts by %3$s Feed'),
				/* translators: 1: blog name, 2: separator(raquo), 3: search phrase */
				'searchtitle' => __('%1$s %2$s Search Results for &#8220;%3$s&#8221; Feed'),
				/* translators: 1: blog name, 2: separator(raquo), 3: post type name */
				'posttypetitle' => __('%1$s %2$s %3$s Feed'),
			);
			
			$args = wp_parse_args( $args, $defaults );
			
			if ( is_category() ) {
				$term = get_queried_object();

				$title = sprintf( $args['cattitle'], get_bloginfo('name'), $args['separator'], $term->name );
				$href = get_category_feed_link( $term->term_id );
			} elseif ( is_tag() ) {
				$term = get_queried_object();

				$title = sprintf( $args['tagtitle'], get_bloginfo('name'), $args['separator'], $term->name );
				$href = get_tag_feed_link( $term->term_id );
			} elseif ( is_author() ) {
				$author_id = intval( get_query_var('author') );

				$title = sprintf( $args['authortitle'], get_bloginfo('name'), $args['separator'], get_the_author_meta( 'display_name', $author_id ) );
				$href = get_author_feed_link( $author_id );
			} elseif ( is_search() ) {
				$title = sprintf( $args['searchtitle'], get_bloginfo('name'), $args['separator'], get_search_query( false ) );
				$href = get_search_feed_link();
			} elseif ( is_post_type_archive() ) {
				$title = sprintf( $args['posttypetitle'], get_bloginfo('name'), $args['separator'], post_type_archive_title( '', false ) );
				$href = get_post_type_archive_feed_link( get_queried_object()->name );
			}
		
			if ( isset($title) && isset($href) )
				echo '<link rel="alternate" type="' . feed_content_type() . '" title="' . esc_attr( $title ) . '" href="' . esc_url( $href ) . '" />' . "\n";
		
		}
		
		/**
		 * Redirect on comment feed, set status 301
		 * 
		 * @since   04/08/2013
		 * @param   void
		 * @return  void
		 */
		public function filter_query() {
			
			if ( ! is_comment_feed() )
				return NULL;
			
			if ( isset( $_GET['feed'] ) ) {
				wp_redirect( remove_query_arg( 'feed' ), 301 );
				exit;
			}
			// redirect_canonical will do the rest
			set_query_var( 'feed', '' );
		}

		/**
		 * Unset additional HTTP headers for pingback
		 *
		 * @since   04/07/2013
		 * @param   Array $headers
		 * @return  Array $headers
		 */
		public function filter_wp_headers( $headers ) {
			
			unset( $headers['X-Pingback'] );
			
			return $headers;
		}
		
		/**
		 * Unregister default comment widget
		 * 
		 * @since   07/16/2012
		 * @param   void
		 * @return  void
		 */
		public function unregister_default_wp_widgets() {
			
			unregister_widget('WP_Widget_Recent_Comments');
		}
		
		/**
		 * Remove options for Keyboard Shortcuts on profile page
		 * 
		 * @since   09/03/2012
		 * @param   void
		 * @return  void
		 */
		public function remove_profile_items() {
			?>
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready( function($) {
					$('#your-profile').find('.form-table').first().find( 'tr:nth-child(3)' ).remove();
				} );
				//]]>
			</script>
			<?php
		}

		/**
		 * On posts where comments are closed, the plugin will remove the text 'Comments are closed.'
		 *
		 * @access   public
		 * @since    0.0.7
		 * @param    $translation
		 * @param    $text
		 * @return   string empty
		 */
		public function remove_theme_string( $translation, $text ) {
			
			if ( is_admin() )
				return $translation;

			if ( 'Comments are closed.' === $text )
				return '';
			
			return $translation;
		}

		/**
		 * Replace comment related XML_RPC methods.
		 *
		 * @access  public
		 * @since   09/21/2013
		 * @param   array $methods
		 * @return  array $methods
		 */
		public function xmlrpc_replace_methods( $methods ) {
			
			$comment_methods = array(
				'wp.getCommentCount',
				'wp.getComment',
				'wp.getComments',
				'wp.deleteComment',
				'wp.editComment',
				'wp.newComment',
				'wp.getCommentStatusList'
			);

			foreach( $comment_methods as $method_name ) {
				
				if ( isset( $methods[$method_name] ) )
					$methods[$method_name] = array( $this, 'xmlrpc_placeholder_method' );
				
			}
			
			return $methods;
		}
		
		/**
		 * XML_RPC placeholder method.
		 * 
		 * @access  public
		 * @since   09/21/2013
		 * @return  object
		 */
		public function xmlrpc_placeholder_method() {
			
			return new IXR_Error( 403, __( 'Comments are disabled on this site.', 'remove_comments_absolute' ) );
		}
		
	} // end class

} // end if class exists
