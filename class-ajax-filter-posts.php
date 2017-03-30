<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://www.robbertdekuiper.com
 * @since      1.0.0
 *
 * @package    Ajax_Filter_Posts
 * @subpackage Ajax_Filter_Posts/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Ajax_Filter_Posts
 * @subpackage Ajax_Filter_Posts/includes
 * @author     Robbert de Kuiper <mail@robbertdekuiper.com>
 */
class Ajax_Filter_Posts {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 */
	public function __construct() {

		$this->plugin_name = 'ajax-filter-posts';
		$this->version = '1.0.0';

		add_action( 'wp_enqueue_scripts', [$this,'add_scripts'] );
		add_action('wp_ajax_process_filter_change', [$this, 'process_filter_change']);
		add_action('wp_ajax_nopriv_process_filter_change', [$this, 'process_filter_change']);
		add_shortcode( 'ajax_filter_posts', [$this, 'create_shortcode']);
	}

	/**
	 * Load the required assets for this plugin.
	 *
	 */
	public function add_scripts() {		
		wp_enqueue_script( 'ajax-filter', plugins_url('/assets/js/ajax-filter-posts.js', __FILE__), [], '', true );
		wp_enqueue_style( 'ajax-filter', plugins_url('/assets/css/ajax-filter-posts.css', __FILE__), []);
		wp_localize_script( 'ajax-filter', 'filterPosts', array(
        'nonce' => wp_create_nonce( 'filter-posts-nonce' ),
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'timeoutMessage' => __('Something went wrong, please try again later.', $this->plugin_name),
      )
  	);
	}

	public function create_shortcode($atts) {

		$attributes = shortcode_atts( array(
				'post_type'=> 'post',
        'tax'      => ['post_tag'],
        'posts_per_page' => 12, // How many posts per page,
    ), $atts, $this->plugin_name );

		$filterlists = $this->get_filterlist($attributes['tax']);

    $query = new WP_Query([
    	'post_type' => $attributes['post_type'],
    	'posts_per_page' => $attributes['posts_per_page'],
    ]);

    return include(plugin_dir_path( __FILE__ ) . 'templates/base.php');
	}

	protected function get_filterlist($taxonomies) {
		$filterlists = explode(',', $taxonomies);
		$filterlists = array_map('trim', $filterlists);
		$filterlists = array_filter($filterlists, 'taxonomy_exists');
		$filterlists = $this->get_termlist($filterlists);
		return $filterlists;
	}

	protected function get_termlist($taxonomies) {
		$list = [];

		foreach ($taxonomies as $taxonomy) {
			$terms = get_terms($taxonomy);
			if (!empty($terms)) {
				$list[] = [
					'name' => get_taxonomy($taxonomy)->labels->singular_name,
					'filters' => $terms,
				];				
			}
		}

		return $list;
	}

	public function process_filter_change() {

		check_ajax_referer( 'filter-posts-nonce', 'nonce' );
	    
	  $post_type = sanitize_text_field($_POST['params']['postType']);
	  $tax  = $this->get_tax_query_vars($_POST['params']['tax']);
	  $page = intval($_POST['params']['page']);
	  $quantity  = intval($_POST['params']['quantity']);

	  $args = [
	      'paged'          => $page,
	      'post_type'      => $post_type,
	      'posts_per_page' => $quantity,
	      'tax_query'      => $tax
	  ];
	  
	 	$response = $this->get_filter_posts($args, $response);
	 	
	 	if ($response) {
	 		wp_send_json_success($response);
	 	} else {
	 		wp_send_json_error(__('Oops, something went wrong', $this->plugin_name));
	 	}
	 	die();
	}

	/**
	 * Converts the queried page to a real page
	 * 
	 * @param  Object 	$query 	WP Query
	 * @return Integer        	Current page
	 */
	private function get_page_number($query){
		$query_page = $query->get( 'paged' );
		return $query_page == 0 ? 1 : $query_page;
	}

	/**
	 * Check if the queried page is the last page of the query
	 * 
	 * @param  Object 	$query 	WP Query
	 * @return Boolean        	true if is last page
	 */
	private function is_last_page($query) {
		return $query->get( 'paged' ) >= $query->max_num_pages;
	}

	protected function get_tax_query_vars($taxonomies) {
		$tax_query = [];

		foreach ($taxonomies as $taxonomy => $terms) {
			$taxonomy = sanitize_text_field($taxonomy);
			if (taxonomy_exists($taxonomy)) {
				$valid_terms = $this->get_valid_terms($terms, $taxonomy);
				if ($valid_terms) {
					$term_query = [
						'taxonomy' => $taxonomy,
						'field'    => 'slug',
						'terms'    => $valid_terms,
					];

					$tax_query[] = $term_query;

				}
			}
		}

		if( count($tax_query) > 1 ) {
			$tax_query[] = ['relation' => 'OR'];
		}
		return $tax_query;
	}

	protected function get_valid_terms($terms, $tax) {
		$valid_terms = [];
		
		foreach ($terms as $term) {
			$term = sanitize_text_field($term);
			if (term_exists($term,$tax)) {
				$valid_terms[] = $term;
			}
		}
		return $valid_terms;
	}

	/**
	 * Set up filtered query
	 */
	public function get_filter_posts($args, $response) {
	    
	  $query = new WP_Query($args);

	  ob_start();
	  include(plugin_dir_path( __FILE__ ) . 'templates/partials/loop.php'); ;
	  $response['content'] = ob_get_clean();
	  $response['found'] = $query->found_posts;
	  return $response;
	}
}