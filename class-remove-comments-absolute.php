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

		// Remove string on frontend in theme.
		add_filter( 'gettext', array( $this, 'remove_theme_string' ), 20, 2 );

		// Unregister default comment widget.
		add_action( 'widgets_init', array( $this, 'unregister_default_widgets' ), 1 );

		// Remove comment feed.
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		add_action( 'wp_head', array( $this, 'feed_links' ), 2 );
		add_action( 'wp_head', array( $this, 'feed_links_extra' ), 3 );
		add_action( 'template_redirect', array( $this, 'filter_query' ), 9 );

		// Unset comment feed pingback HTTP headers.
		add_filter( 'wp_headers', array( $this, 'filter_wp_headers' ) );
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

	/**
	 * Do not display the 'Comments are closed.' string in theme templates.
	 *
	 * @access public
	 * @since  0.0.7
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Text to translate.
	 *
	 * @return string empty
	 */
	public function remove_theme_string( $translation, $text ) {

		if ( is_admin() ) {
			return $translation;
		}

		if ( 'Comments are closed.' === $text ) {
			return '';
		}

		return $translation;
	}

	/**
	 * Unregister default comment widget.
	 *
	 * @since   07/16/2012
	 */
	public function unregister_default_widgets() {
		unregister_widget( 'WP_Widget_Recent_Comments' );
	}

	/**
	 * Display the links to the general feeds, without comments.
	 *
	 * @access public
	 * @since  0.0.4
	 * @see   current_theme_supports(), wp_parse_args(), feed_content_type(), get_bloginfo(), esc_attr(), get_feed_link(), _x(), __()
	 *
	 * @param  array $args Optional arguments
	 *
	 * @return string
	 */
	public function feed_links( $args = array() ) {

		if ( ! current_theme_supports( 'automatic-feed-links' ) ) {
			return NULL;
		}

		$defaults = [
			// Translators: Separator between blog name and feed type in feed links.
			'separator' => _x(
				'&raquo;',
				'feed link',
				'remove_comments_absolute'
			),
			// Translators: 1: blog title, 2: separator (raquo).
			'feedtitle' => __( '%1$s %2$s Feed', 'remove_comments_absolute' ),
		];

		$args = wp_parse_args( $args, $defaults );

		echo '<link rel="alternate" type="' . esc_attr( feed_content_type() ) . '" title="' .
			esc_attr(
				sprintf(
					$args[ 'feedtitle' ],
					get_bloginfo( 'name' ),
					$args[ 'separator' ]
				)
			) . '" href="' . esc_attr( get_feed_link() ) . '"/>' . "\n";
	}

	/**
	 * Display the links to the extra feeds such as category feeds.
	 *
	 * Copy from WP default, but without comment feed; no filter available.
	 *
	 * @since 04/08/2013
	 *
	 * @param array $args Optional argument.
	 */
	public function feed_links_extra( $args = array() ) {

		$defaults = [
			/* Translators: Separator between blog name and feed type in feed links. */
			'separator'     => _x( '&raquo;', 'feed link' ),
			/* Translators: 1: blog name, 2: separator(raquo), 3: category name. */
			'cattitle'      => __( '%1$s %2$s %3$s Category Feed' ),
			/* Translators: 1: blog name, 2: separator(raquo), 3: tag name. */
			'tagtitle'      => __( '%1$s %2$s %3$s Tag Feed' ),
			/* Translators: 1: blog name, 2: separator(raquo), 3: author name.  */
			'authortitle'   => __( '%1$s %2$s Posts by %3$s Feed' ),
			/* Translators: 1: blog name, 2: separator(raquo), 3: search phrase. */
			'searchtitle'   => __( '%1$s %2$s Search Results for &#8220;%3$s&#8221; Feed' ),
			/* Translators: 1: blog name, 2: separator(raquo), 3: post type name. */
			'posttypetitle' => __( '%1$s %2$s %3$s Feed' ),
		];

		$args = wp_parse_args( $args, $defaults );

		if ( is_category() ) {
			$term = get_queried_object();

			$title = sprintf( $args[ 'cattitle' ], get_bloginfo( 'name' ), $args[ 'separator' ], $term->name );
			$href  = get_category_feed_link( $term->term_id );
		} elseif ( is_tag() ) {
			$term = get_queried_object();

			$title = sprintf( $args[ 'tagtitle' ], get_bloginfo( 'name' ), $args[ 'separator' ], $term->name );
			$href  = get_tag_feed_link( $term->term_id );
		} elseif ( is_author() ) {
			$author_id = intval( get_query_var( 'author' ) );

			$title = sprintf(
				$args[ 'authortitle' ], get_bloginfo( 'name' ), $args[ 'separator' ],
				get_the_author_meta( 'display_name', $author_id )
			);
			$href  = get_author_feed_link( $author_id );
		} elseif ( is_search() ) {
			$title = sprintf(
				$args[ 'searchtitle' ], get_bloginfo( 'name' ), $args[ 'separator' ], get_search_query( FALSE )
			);
			$href  = get_search_feed_link();
		} elseif ( is_post_type_archive() ) {
			$title = sprintf(
				$args[ 'posttypetitle' ], get_bloginfo( 'name' ), $args[ 'separator' ],
				post_type_archive_title( '', FALSE )
			);
			$href  = get_post_type_archive_feed_link( get_queried_object()->name );
		}

		if ( isset( $title ) && isset( $href ) ) {
			echo '<link rel="alternate" type="' . esc_attr( feed_content_type() ) . '" title="' . esc_attr(
					$title
				) . '" href="' . esc_url( $href ) . '" />' . "\n";
		}
	}

	/**
	 * Redirect on comment feed, set status 301.
	 *
	 * @since  04/08/2013
	 * @return NULL
	 */
	public function filter_query() {

		if ( ! is_comment_feed() ) {
			return NULL;
		}

		if ( isset( $_GET[ 'feed' ] ) ) {
			wp_redirect( remove_query_arg( 'feed' ), 301 );
			exit();
		}
		// Redirect_canonical will do the rest.
		set_query_var( 'feed', '' );
	}

	/**
	 * Unset comment feed pingback HTTP headers.
	 *
	 * @since   04/07/2013
	 *
	 * @param array $headers
	 *
	 * @return array $headers
	 */
	public function filter_wp_headers( $headers ) {
		unset( $headers[ 'X-Pingback' ] );
		return $headers;
	}
}