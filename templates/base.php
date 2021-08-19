<section
  class="js-container-async ajax-posts"
  <?php if ( !empty( $attributes['id'] ) ) : ?>
    id="ajax-posts-<?= esc_html( $attributes['id'] ); ?>"
    data-id="<?= esc_html( $attributes['id'] ); ?>"
  <?php endif; ?>
  data-post-type="<?= esc_html( implode(',', $attributes['post_type'] ) ); ?>"
  data-post-status="<?= esc_html( implode(',', $attributes['post_status'] ) ); ?>"
  data-quantity="<?= esc_html( $attributes['posts_per_page'] ); ?>"
  data-multiselect="<?= esc_html( $attributes['multiselect'] ); ?>"
  data-orderby="<?= esc_html( $attributes['orderby'] ); ?>"
  data-order="<?= esc_html( $attributes['order'] ); ?>"
>
  <div class="ajax-posts__status" style="display:none;"></div>
  <?php if ( $query->have_posts() && $query->post_count > 1) : ?>
    <button class="js-toggle-filters ajax-posts__toggle-filter">
      <span class="ajax-posts__filter-recipes-text">+ <?php  printf( __('Filter %s', 'ajax-filter-posts'), $plural_post_name); ?></span>
      <span class="ajax-posts__show-recipes-text">+ <?php  printf( __('Show %s', 'ajax-filter-posts'), $plural_post_name); ?></span>
      <span class="ajax-posts__hide-filters-text">- <?= __('Hide filters', 'ajax-filter-posts'); ?></span>
    </button>
  <?php endif; ?>
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
<?php wp_reset_postdata(); ?>