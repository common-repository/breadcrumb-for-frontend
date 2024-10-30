<?php
/**
 * Plugin Name: Breadcrumb For Frontend
 * Plugin URI: https://wordpress.org/plugins/breadcrumb-for-frontend/
 * Description: Breadcrumb For Frontend plugin is used for displaying the breadcrumb for web pages to easily navigate on pages
 * Version: 1.0.0
 * Author: Gautam Buddha
 * Author URI: https://webdevelopmentsolutions.in/author
 * License: GPLv2 or later
 * Text Domain: breadcrumb-for-frontend
 *
 * @package WP_BCFF
 * @copyright Copyright (c) 2018, Gautam Buddha
 * @link      https://wordpress.org/plugins/breadcrumb-for-frontend/
 * @license   GPLv2 or later
 */   

// Exit if accessed directly.
if ( ! defined('ABSPATH')) exit;
 
if ( ! class_exists( 'WP_BCFF' ) ) :
/**
 * This class is used to handle breadcrumb for frontend events.
 *
 * We defined this class for handling the all events regarding to create and display the breadcrumbs.
 *
 * @since 1.0.0
 */
class WP_BCFF {
	/**
	 * Constructor.
	 *
	 * define the constants and called the defined actions
	 *
	 * @since 1.0.0
	 *
	 * @param null
	 */
	public function __construct() {
		
		/** 
		 * BCFF_PLUGIN_DIRECTORY_URL constant used for plugin directory
		 */
		define('BCFF_PLUGIN_DIRECTORY_URL', plugin_dir_url( __FILE__ )  );
		
		/**
		 * Fires inside __construct() method for WP_BCFF class to add the style and js.
		 *
		 * @since 1.0.0
		 *
		 * @param null.
		 */
		add_action( 'wp_footer', array( $this, 'bcff_frontend_scripts' ) );
		
		/**
		 * Fires inside __construct() method for WP_BCFF class to create the breadcrumb shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param null.
		 */
		add_shortcode( 'bcff_display_breadcrumb', array($this, 'bcff_create_breadcrumb') ); 
		
		/**
		 * Fires inside __construct() method for WP_BCFF class to setup the dashbaord widget.
		 *
		 * @since 1.0.0
		 *
		 * @param null.
		 */
		add_action('wp_dashboard_setup', array($this, 'bcff_dashboard_widgets') );
	    
	}
	
	/**
	 * This function is used for load the js and css when the class instancated
	 *
	 * @param null
	 *
	 * @return null
	 */	
	public function bcff_frontend_scripts() {
		wp_enqueue_style('bcff_breadcrumb_style',  BCFF_PLUGIN_DIRECTORY_URL.  'assets/css/breadcrumb-for-frontend.css');
	    wp_enqueue_script('bcff_breadcrumb_js', plugins_url( 'assets/js/breadcrumb-for-frontend.js' , __FILE__ ) , array( 'jquery' ));
	}
	
	/**
	 * This function is used for show the breadcrumb when class instancate
	 *
	 * @param null
	 *
	 * @return null
	 */	
	public function bcff_create_breadcrumb() {
		
		// this code is used to set variables for later use
		$here_text        = __( 'You are here!' );
		$home_link        = home_url('/');
		$home_text        = __( 'Home' );
		$link_before      = '<span typeof="v:Breadcrumb">';
		$link_after       = '</span>';
		$link_attr        = ' rel="v:url" property="v:title"';
		$link             = $link_before . '<a' . $link_attr . ' href="%1$s">%2$s</a>' . $link_after;
		$delimiter        = ' &raquo; ';              // Delimiter between crumbs
		$before           = '<span class="current">'; // Tag before the current crumb
		$after            = '</span>';                // Tag after the current crumb
		$page_addon       = '';                       // Add the page number if the query is paged
		$breadcrumb_trail = '';
		$category_links   = '';

		/** 
		 * this code is used to set our own $wp_the_query variable. Do not use the global variable version due to 
		 * reliability
		 */
		$wp_the_query   = $GLOBALS['wp_the_query'];
		$queried_object = $wp_the_query->get_queried_object();

		// this code is used to handle single post requests which includes single pages, posts and attatchments
		if ( is_singular() ) 
		{
			/** 
			 * this code is used to set our own $post variable. Do not use the global variable version due to 
			 * reliability. We will set $post_object variable to $GLOBALS['wp_the_query']
			 */
			$post_object = sanitize_post( $queried_object );

			// this code is used to set the variables 
			$title          = apply_filters( 'the_title', $post_object->post_title );
			$parent         = $post_object->post_parent;
			$post_type      = $post_object->post_type;
			$post_id        = $post_object->ID;
			$post_link      = $before . $title . $after;
			$parent_string  = '';
			$post_type_link = '';
			
			// this code is executed when $post_type is post
			if ( 'post' === $post_type ) 
			{
				// this code is used to get the post categories
				$categories = get_the_category( $post_id );
				if ( $categories ) {
					// this code is used to grab the first category
					$category  = $categories[0];

					$category_links = get_category_parents( $category, true, $delimiter );
					$category_links = str_replace( '<a',   $link_before . '<a' . $link_attr, $category_links );
					$category_links = str_replace( '</a>', '</a>' . $link_after,             $category_links );
				}
			}
			
			// this code is executed when $post_type 'post', 'page', 'attachment' is in array list
			if ( !in_array( $post_type, ['post', 'page', 'attachment'] ) )
			{
				$post_type_object = get_post_type_object( $post_type );
				$archive_link     = esc_url( get_post_type_archive_link( $post_type ) );

				$post_type_link   = sprintf( $link, $archive_link, $post_type_object->labels->singular_name );
			}

			// this code is used to get post parents if $parent not equal to zero
			if ( 0 !== $parent ) 
			{
				$parent_links = [];
				while ( $parent ) {
					$post_parent = get_post( $parent );

					$parent_links[] = sprintf( $link, esc_url( get_permalink( $post_parent->ID ) ), get_the_title( $post_parent->ID ) );

					$parent = $post_parent->post_parent;
				}

				$parent_links = array_reverse( $parent_links );

				$parent_string = implode( $delimiter, $parent_links );
			}

			// this code is used to build the breadcrumb trail
			if ( $parent_string ) {
				$breadcrumb_trail = $parent_string . $delimiter . $post_link;
			} else {
				$breadcrumb_trail = $post_link;
			}

			if ( $post_type_link )
				$breadcrumb_trail = $post_type_link . $delimiter . $breadcrumb_trail;

			if ( $category_links )
				$breadcrumb_trail = $category_links . $breadcrumb_trail;
		}

		// this code is used to handle archives which includes category-, tag-, taxonomy-, date-, custom post type archives and author archives
		if( is_archive() )
		{
			if ( is_category() || is_tag() || is_tax() ) {
				// this code is used to set the variables for this section
				$term_object        = get_term( $queried_object );
				$taxonomy           = $term_object->taxonomy;
				$term_id            = $term_object->term_id;
				$term_name          = $term_object->name;
				$term_parent        = $term_object->parent;
				$taxonomy_object    = get_taxonomy( $taxonomy );
				$current_term_link  = $before . $taxonomy_object->labels->singular_name . ': ' . $term_name . $after;
				$parent_term_string = '';

				if ( 0 !== $term_parent ) {
					// this code is used to get all the current term ancestors
					$parent_term_links = [];
					while ( $term_parent ) {
						$term = get_term( $term_parent, $taxonomy );
						$parent_term_links[] = sprintf( $link, esc_url( get_term_link( $term ) ), $term->name );
						$term_parent = $term->parent;
					}
					$parent_term_links  = array_reverse( $parent_term_links );
					$parent_term_string = implode( $delimiter, $parent_term_links );
				}

				if ( $parent_term_string ) {
					$breadcrumb_trail = $parent_term_string . $delimiter . $current_term_link;
				} else {
					$breadcrumb_trail = $current_term_link;
				}

			} elseif ( is_author() ) {
				$breadcrumb_trail = __( 'Author archive for ') .  $before . $queried_object->data->display_name . $after;
			} elseif ( is_date() ) {
				$year     = $wp_the_query->query_vars['year'];
				$monthnum = $wp_the_query->query_vars['monthnum'];
				$day      = $wp_the_query->query_vars['day'];

				// this code is used for get the month name if $monthnum has a value
				if ( $monthnum ) {
					$date_time  = DateTime::createFromFormat( '!m', $monthnum );
					$month_name = $date_time->format( 'F' );
				}

				if ( is_year() ) {
					$breadcrumb_trail = $before . $year . $after;
				} elseif( is_month() ) {
					$year_link        = sprintf( $link, esc_url( get_year_link( $year ) ), $year );
					$breadcrumb_trail = $year_link . $delimiter . $before . $month_name . $after;

				} elseif( is_day() ) {
					$year_link        = sprintf( $link, esc_url( get_year_link( $year ) ),             $year       );
					$month_link       = sprintf( $link, esc_url( get_month_link( $year, $monthnum ) ), $month_name );
					$breadcrumb_trail = $year_link . $delimiter . $month_link . $delimiter . $before . $day . $after;
				}

			} elseif ( is_post_type_archive() ) {
				$post_type        = $wp_the_query->query_vars['post_type'];
				$post_type_object = get_post_type_object( $post_type );
				$breadcrumb_trail = $before . $post_type_object->labels->singular_name . $after;
			}
		}   

		// this code is used for the search page
		if ( is_search() ) {
			$breadcrumb_trail = __( 'Search results for: ' ) . $before . get_search_query() . $after;
		}

		// this code is usde for 404's
		if ( is_404() ) {
			$breadcrumb_trail = $before . __( 'Error 404' ) . $after;
		}

		// this code is used for paged pages
		if ( is_paged() ) {
			$current_page = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : get_query_var( 'page' );
			$page_addon   = $before . sprintf( __( ' ( Page %s )' ), number_format_i18n( $current_page ) ) . $after;
		}

		$breadcrumb_output_link  = '';
		$breadcrumb_output_link .= '<div class="breadcrumb-for-frontend">';
		if ( is_home() || is_front_page() ) {
			// this code is used for exclude the breadcrumbs from home and frontpage
			if ( is_paged() ) {
				$breadcrumb_output_link .= $here_text . $delimiter;
				$breadcrumb_output_link .= '<a href="' . $home_link . '">' . $home_text . '</a>';
				$breadcrumb_output_link .= $page_addon;
			}
		} else {
			$breadcrumb_output_link .= $here_text . $delimiter;
			$breadcrumb_output_link .= '<a href="' . $home_link . '" rel="v:url" property="v:title">' . $home_text . '</a>';
			$breadcrumb_output_link .= $delimiter;
			$breadcrumb_output_link .= $breadcrumb_trail;
			$breadcrumb_output_link .= $page_addon;
		}
		$breadcrumb_output_link .= '</div><!-- .breadcrumb -->';

		return $breadcrumb_output_link;
	}
	
	/**
	 * This function is used to add a new dashboard widget
	 *
	 * @param null
	 *
	 * @return null
	 */	
	public function bcff_dashboard_widgets() {
		global $wp_meta_boxes;
		wp_add_dashboard_widget('bcff_help_widget', 'Breadcrumb For Frontend Plugin Support', array($this,'bcff_dashboard_help') );
	}
	
	/**
	 * This function is used for output the contents of the dashboard widget
	 *
	 * @param null
	 *
	 * @return string
	 */	 
	public function bcff_dashboard_help() {
		echo '<p>Welcome to Breadcrumb For Frontend Plugin! Need help? Contact the developer <a href="mailto:chaudharygautam88@gmail.com">here</a>. For more deatils about the plugin to visit: <div class="blink" style="width:100%;
	    height: 50px; background-color: deepskyblue; text-align: center;line-height: 48px;"><span style="font-size: 22px;font-family: cursive;color: white;animation: blink 1s linear infinite;"><a title="More About Breadcrumb For Frontend Plugin" href="https://wordpress.org/plugins/breadcrumb-for-frontend/" target="_blank" style="color: #fff;text-decoration: none">For more details click here &raquo;</a></span></div></p>';
	}
}

// create the instance of class
new WP_BCFF();

endif; // End if class_exists check.
?>