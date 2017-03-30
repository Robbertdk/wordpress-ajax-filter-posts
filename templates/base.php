<section data-post-type="<?= $attributes['post_type']; ?>" data-quantity="<?= $attributes['posts_per_page']; ?>" class="js-container-async ajax-posts">
  <button class="js-toggle-filters ajax-posts__toggle-filter">
    <span class="ajax-posts__hide-filter-text"><?php _e('Show filters', $this->plugin_name); ?></span>
    <span class="ajax-posts__show-filter-text"><?php _e('Show results', $this->plugin_name); ?></span>   
  </button>
  <aside class="ajax-posts__filters">
    <?php include(plugin_dir_path( __FILE__ ) . 'partials/filters.php' ); ?>
  </aside>
  <div class="ajax-posts__posts">
      <div class="ajax-posts__status"></div>
      <?php include(plugin_dir_path( __FILE__ ) . 'partials/loop.php' ); ?>
  </div>
  <div class="ajax-posts__spinner">
    <span class="ajax-posts__screen-reader-only"><?php _e('Loading', $this->plugin_name); ?></span>
  </div>
</section>