<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://www.robbertdekuiper.com
 * @since      0.1.0
 *
 * @package    Ajax_Filter_Posts
 */

/**
 * The core plugin class.
 *
 * The plugin logic lives here
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.1.0
 * @package    Ajax_Filter_Posts
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
   * @var      String    $version    The current version of the plugin.
   */
  protected $version;


  /**
   * Define the core functionality of the plugin.
   *
   * Set the plugin name and the plugin version that can be used throughout the plugin.
   * Load the dependencies, define the locale, and set the hooks.
   *
   */
  public function __construct() {

    $this->plugin_name = 'ajax-filter-posts';
    $this->version = '0.3.0';

    add_action( 'plugins_loaded', [$this, 'load_textdomain'] );
    add_action( 'wp_enqueue_scripts', [$this,'add_scripts'] );
    add_action('wp_ajax_process_filter_change', [$this, 'process_filter_change']);
    add_action('wp_ajax_nopriv_process_filter_change', [$this, 'process_filter_change']);
    add_shortcode( 'ajax_filter_posts', [$this, 'create_shortcode']);
  }

  /**
   * Set the plugins language domain
   */
  public function load_textdomain() {
    if (strpos( __FILE__, basename( WPMU_PLUGIN_DIR ))) {
      load_muplugin_textdomain( 'ajax-filter-posts', basename( dirname( __FILE__ )) . '/languages' );
    } else {
      load_plugin_textdomain( 'ajax-filter-posts', false, basename(dirname( __FILE__ )) . '/languages' );
    }
  }

  /**
   * Load the required assets for this plugin.
   *
   */
  public function add_scripts() {

    $script_variables = [
      'nonce' => wp_create_nonce( 'filter-posts-nonce' ),
      'ajaxUrl' => admin_url( 'admin-ajax.php' ),
      'timeoutMessage' => __('It took to long the get the posts. Please reload the page and try again.', 'ajax-filter-posts'),
      'serverErrorMessage' => __('Got no response. Please reload the page and try again.', 'ajax-filter-posts'),
    ];

    // IF WPML is installed add language variable to set variable later during the query
    // WPML can't figure out which language to query, when posts are loaded via AJAX.
    if (function_exists('icl_object_id')) {
      $script_variables['language'] = ICL_LANGUAGE_CODE;
    }

    wp_enqueue_script( 'ajax-filter', plugins_url('/assets/js/ajax-filter-posts.js', __FILE__), [], '', true );
    wp_enqueue_style( 'ajax-filter', plugins_url('/assets/css/ajax-filter-posts.css', __FILE__), []);
    wp_localize_script( 'ajax-filter', 'filterPosts', $script_variables);
  }

  /**
   * Create shortcode
   * 
   * @param  Array    $atts   Array of given attributes
   * @return String           HTML initial rendered by shortcode
   */
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

    $plural_post_name = strtolower(get_post_type_object($query->query['post_type'])->labels->name);

    ob_start();
    include( $this->get_local_template('base.php') );
    return ob_get_clean();
  }

  /**
   * Get a list of filters and terms, based on the taxonomies set in the shortcode
   * 
   * @param  String   $taxonomies     Comma seperated list of taxonomies
   * @return Array                    List of taxonomies with terms
   */
  protected function get_filterlist($taxonomies) {
    $filterlists = explode(',', $taxonomies);
    $filterlists = array_map('trim', $filterlists);
    $filterlists = array_filter($filterlists, 'taxonomy_exists');
    $filterlists = $this->get_termlist($filterlists);
    return $filterlists;
  }

  /**
   * Get a list of filters and terms
   * 
   * @param  string   $taxonomies   A single taxonomy
   * @return Array                  Taxonomy name and list of terms associated with the taxonomy
   */
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

  /**
   * Send new posts query via AJAX after filters are changed in the frontend
   * 
   * @return String HTML string with parsed posts or an error message
   */
  public function process_filter_change() {

    check_ajax_referer( 'filter-posts-nonce', 'nonce' );
      
    $post_type = sanitize_text_field($_POST['params']['postType']);
    $tax  = $this->get_tax_query_vars($_POST['params']['tax']);
    $page = intval($_POST['params']['page']);
    $quantity  = intval($_POST['params']['quantity']);
    $language = sanitize_text_field($_POST['params']['language']);

    $args = [
        'paged'          => $page,
        'post_type'      => $post_type,
        'posts_per_page' => $quantity,
        'tax_query'      => $tax
    ];
    
    $response = $this->get_filter_posts($args, $language);
    
    if ($response) {
      wp_send_json_success($response);
    } else {
      wp_send_json_error(__('Oops, something went wrong. Please reload the page and try again.', 'ajax-filter-posts'));
    }
    die();
  }

  /**
   * Converts the queried page number to a real page number
   * 
   * @param  Object   $query  WP Query
   * @return Integer          Current page
   */
  private function get_page_number($query){
    $query_page = $query->get( 'paged' );
    return $query_page == 0 ? 1 : $query_page;
  }

  /**
   * Check if the queried page is the last page of the query
   * 
   * @param  Object   $query  WP Query
   * @return Boolean          true if is last page
   */
  private function is_last_page($query) {
    return $this->get_page_number($query) >= $query->max_num_pages;
  }

  /**
   * Get the query paramaters based on set filters
   * 
   * @param  array  $taxonomies   list of taxanomies with terms
   * @return array                taxonomies prepared for the WordPress Query
   */
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

  /**
   * Check of the given thers are valid terms
   * 
   * @param  array    $terms  List of terms set by the filters
   * @param  string   $tax    Taxomy associated with the terms
   * @return array            List of valid terms
   */
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
   * Set up a filters query and parse the template
   * 
   * @param  array  $args       Arguments for the WordPress Query
   * @return string             HTMl to be sent via Ajax
   */
  public function get_filter_posts($args, $language) {
    if (function_exists('icl_object_id') && !empty($language)) {
      global $sitepress;
        $sitepress->switch_lang( $language );
    }

    $query = new WP_Query($args);
    $plural_post_name = strtolower(get_post_type_object($query->query['post_type'])->labels->name);
    $response = [];
    
    ob_start();
    include( $this->get_local_template('partials/loop.php'));
    $response['content'] = ob_get_clean();
    $response['found'] = $query->found_posts;
    return $response;
  }

  /**
   * Locate template.
   *
   * Locate the called template.
   * Search Order:
   * 1. /themes/theme/ajax-posts-filters/$template_name
   * 2. /plugins/ajax-filter-posts/templates/$template_name.
   *
   * @since 0.3.0
   *
   * @param   string  $template_name          Template to load.
   * @return  string                          Path to the template file.
   */
  public function get_local_template($template_name) {

    if (empty($template_name)) return false;

    $template = locate_template('ajax-filter-posts/' . $template_name);

    // If template not in theme, get plugins template file.
    if ( !$template ) {
      $template = plugin_dir_path( __FILE__ ) . 'templates/' . $template_name;
    }

    if ( !file_exists( $template ) ) {
      _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template ), '4.6.0' );
      return;
    }

    return $template;
  }
}
