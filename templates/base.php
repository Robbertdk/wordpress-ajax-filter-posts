<section
  class="js-container-async ajax-posts"
  data-post-type="<?= $attributes['post_type']; ?>"
  data-quantity="<?= $attributes['posts_per_page']; ?>"
  data-multiselect="<?= $attributes['multiselect']; ?>"
>
  <div class="ajax-posts__status" style="display:none;"></div>
  <div class="ajax-posts__view">  
    <aside class="ajax-posts__filters">
    <?php if ( $query->have_posts() && $query->post_count > 1) : ?>
        <?php include( $this->get_local_template('partials/filters.php') ); ?>
    <?php endif; ?>
    </aside>
    <div class="ajax-posts__posts">
        <?php include( $this->get_local_template('partials/loop.php') ); ?>
    </div>
  </div>
  <div class="ajax-posts__spinner">
    <span class="ajax-posts__screen-reader-only"><?php _e('Loading', 'ajax-filter-posts'); ?></span>
  </div>
</section>