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
   * Defaults arguments that can be overwritten vua the shortcode attributes
   *
   * @var      array    $default_shortcode_attributes    list of arguments
   */
  protected $default_shortcode_attributes = array(
    'id'             => null, // a user can add a custom id
    'post_type'      => 'post,page',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'order'          => 'DESC',
    'orderby'        => 'date',
    'tax'            => 'post_tag',
    'multiselect'    => 'true',
  );

  /**
   * Allowed orderby values a user can add as an value in the orderby shortcode attribute
   *
   * Does not support `meta_value`, `meta_value_num`, `post_name__in`, `post_parent__in` `post_parent__in`
   * because additionals arguments needs to be set with these orderby values.
   *
   * @var      array    $allowed_orderby_values    flat list of orderby values
   */
  protected $allowed_orderby_values = array(
    'ID',
    'author',
    'title',
    'name',
    'type',
    'date',
    'modified',
    'parent',
    'rand',
    'comment_count',
    'relevance',
    'menu_order',
  );


  /**
   * Define the core functionality of the plugin.
   *
   * Set the plugin name and the plugin version that can be used throughout the plugin.
   * Load the dependencies, define the locale, and set the hooks.
   *
   */
  public function __construct() {

    $this->plugin_name = 'ajax-filter-posts';
    $this->version = '0.5.2';

    add_action( 'plugins_loaded', [$this, 'load_textdomain'] );
    add_action( 'wp_enqueue_scripts', [$this,'add_scripts'] );
    add_action( 'wp_ajax_process_filter_change', [$this, 'process_filter_change'] );
    add_action( 'wp_ajax_nopriv_process_filter_change', [$this, 'process_filter_change'] );
    add_shortcode( 'ajax_filter_posts', [$this, 'create_shortcode'] );
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
  public function create_shortcode($given_attributes) {

    $attributes = shortcode_atts( $this->default_shortcode_attributes, $given_attributes, $this->plugin_name );

    // Check if the set attributes are allowed
    $attributes = $this->validate_attributes($attributes);

    if ( is_wp_error($attributes) ) {
      return $attributes->get_error_message();
    }

    $filterlists = $this->get_filterlist($attributes['tax']);

    $query = $this->query_posts([
      'post_type'      => $attributes['post_type'],
      'post_status'    => $attributes['post_status'],
      'posts_per_page' => $attributes['posts_per_page'],
      'order'          => $attributes['order'],
      'orderby'        => $attributes['orderby'],
    ], $attributes);

    $plural_post_name = $this->get_post_type_plural_name($query->query['post_type']);

    ob_start();

    $this->load_template('base.php', [
      'attributes' => $attributes,
      'query' => $query,
      'filterlists' => $filterlists,
      'plural_post_name' => $plural_post_name,
    ]);

    return ob_get_clean();
  }

  /**
   * Validate shrotode attributes
   *
   * @param  Array    $atts   Array of given attributes
   * @return Array|WP_Error   given attributes or an erorr when attributes are not valid
   */
  protected function validate_attributes($attributes) {

    // Always convert post type and status to an array so we can always be sure we deal with an array
    // makes multipe is_array/is_string checks redundant
    $attributes['post_type'] = $this->delimited_to_array($attributes['post_type']);
    $attributes['post_status'] = $this->delimited_to_array($attributes['post_status']);

    // only allow publicly post types and status to be queried, if not overwritten by developer
    $is_post_type_viewable = apply_filters('ajax_filter_posts_is_post_type_viewable', $this->is_every_post_type_viewable($attributes['post_type']), $attributes);
    $is_post_status_viewable = apply_filters('ajax_filter_posts_is_post_status_viewable', $this->is_every_post_status_viewable($attributes['post_status']), $attributes);

    if ( !$is_post_type_viewable || !$is_post_status_viewable ) {
      return new WP_Error('Posts not viewable', __("Something went wrong. The posts you've requested does not exist or is not viewable.", 'ajax-filter-posts'));
    }

    // don't allow orderby values that are not supported
    if ( !in_array($attributes['orderby'], $this->allowed_orderby_values) ) {
      return new WP_Error('Invalid orderby attribute', __("Something went wrong. The posts could not be sorted with the given orderby method.", 'ajax-filter-posts'));
    }

    return $attributes;
  }

  /**
   * Convert comma delimited attributes to php arrays
   *
   * @param array $attribute
   *
   * @return array comma delimited to array
   */
  protected function delimited_to_array($attribute) {
    if ( !is_string($attribute) ) {
      return $attribute;
    }
    $attribute = explode(',', $attribute);
    $attribute = array_map('trim', $attribute);
    return $attribute;
  }

  /**
   * Check if all post types are viewable
   *
   * @param array $post_type
   *
   * @return boolean
   */
  protected function is_every_post_type_viewable($post_type) {
    foreach ($post_type as $type) {
      if ( !is_post_type_viewable($type) ) {
        return false;
      }
    }
    return true;
  }

  /**
   * Check if all post status are viewable
   *
   * @param array $post_status
   *
   * @return boolean
   */
  protected function is_every_post_status_viewable($post_status) {
    foreach ($post_status as $status) {
      if ( !is_post_status_viewable($status) ) {
        return false;
      }
    }
    return true;
  }

  /**
   * Query the posts with the given arguments
   * This function is called for the original query and for the queries when filters are applied
   *
   * @param array $args a list of query arguments
   * @param array $shortcode_attributes a list of all shortcode attributes
   *
   * @return WP_Query a new instance of WP Query
   */
  protected function query_posts($args, $shortcode_attributes) {
    $query_args = apply_filters('ajax_filter_posts_query_args', $args, $shortcode_attributes);
    return new WP_Query($query_args);
  }

  /**
   * Get a list of filters and terms, based on the taxonomies set in the shortcode
   *
   * @param  String   $taxonomies     Comma seperated list of taxonomies
   * @return Array                    List of taxonomies with terms
   */
  protected function get_filterlist($taxonomies) {
    $filterlists = $this->delimited_to_array($taxonomies);
    $filterlists = array_filter($filterlists, 'taxonomy_exists');
    $filterlists = $this->get_termlist($filterlists);
    return $filterlists;
  }

  /**
   * Get a list of filters and terms
   *
   * @param  array   $taxonomies   A single taxonomy
   * @return Array                  Taxonomy name and list of terms associated with the taxonomy
   */
  protected function get_termlist($taxonomies) {
    $list = [];

    foreach ($taxonomies as $taxonomy) {
      $terms = get_terms($taxonomy);
      $taxonomy_data = get_taxonomy($taxonomy);
      if (!empty($terms)) {
        $list[] = [
          'name' => $taxonomy_data->labels->singular_name,
          'id' => 'taxonomy-' . str_replace('_', '-', $taxonomy_data->name),
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

    $attributes = array(
      // when the id is null, the id is not transfered
      'id'          => !empty($_POST['params']['id']) ? sanitize_text_field($_POST['params']['id']) : null,
      'post_type'   => sanitize_text_field($_POST['params']['postType']),
      'post_status' => sanitize_text_field($_POST['params']['postStatus']),
      'tax'         => $this->get_tax_query_vars($_POST['params']['tax']),
      'page'        => intval($_POST['params']['page']),
      'orderby'     => $_POST['params']['orderby'],
      'order'       => $_POST['params']['order'],
      'quantity'    => intval($_POST['params']['quantity']),
      'language'    => sanitize_text_field($_POST['params']['language']),
    );

    // Abort on false attributes
    // Because we get these attributes via AJAX the user could have changed the attributes
    $attributes = $this->validate_attributes($attributes);

    if ( is_wp_error($attributes) ) {
      wp_send_json_error( $attributes->get_error_message() );
      die();
    }

    $query_args = array(
        'paged'          => $attributes['page'],
        'post_type'      => $attributes['post_type'],
        'post_status'    => $attributes['post_status'],
        'posts_per_page' => $attributes['quantity'],
        'tax_query'      => $attributes['tax'],
        'orderby'        => $attributes['orderby'],
        'order'          => $attributes['order'],
    );

    $response = $this->get_filter_posts($query_args, $attributes);

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
   * Check of the given terms are valid terms
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
   * @param  array  $attributes All shortcodes attributes passed from the frontend
   * @return string             HTMl to be sent via Ajax
   */
  public function get_filter_posts($args, $attributes) {
    if (function_exists('icl_object_id') && !empty($attributes['language'])) {
      global $sitepress;
      $sitepress->switch_lang( $attributes['language'] );
    }

    $query = $this->query_posts($args, $attributes);
    $plural_post_name = $this->get_post_type_plural_name($query->query['post_type']);
    $response = [];

    ob_start();
    $this->load_template('partials/loop.php', [
      'query' => $query,
      'plural_post_name' => $plural_post_name,
      'attributes' => $attributes,
    ]);

    $response['content'] = ob_get_clean();
    $response['found'] = $query->found_posts;
    return $response;
  }

  /**
   * Load the template
   *
   * @param string $template_name
   *
   * @return void
   */
  private function load_template($template_name, $args) {
    extract($args);
    $template = $this->get_local_template($template_name);
    if ( !$template ) {
      echo 'No template found';
      return;
    }
    include $template;
  }

  /* Get the post type plural name. defaults to post
   *
   * @param string|array $post_type
   *
   * @return string
   */
  protected function get_post_type_plural_name($post_type) {
    $post_type = is_array($post_type) ? $post_type[0] : $post_type;
    $post_type_object = get_post_type_object($post_type);
    return $post_type_object ? strtolower($post_type_object->labels->name) : __('posts', 'ajax-filter-posts');
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
    $template = apply_filters( 'ajax_filter_posts_template_name', $template, $template_name );

    // If template not in theme, get plugins template file.
    if ( !$template ) {
      $template = plugin_dir_path( __FILE__ ) . 'templates/' . $template_name;
    }

    if ( !file_exists( $template ) ) {
      _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template ), '4.6.0' );
      return false;
    }

    return $template;
  }
}
