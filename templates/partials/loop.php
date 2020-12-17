<?php if ( $query->have_posts() ) : ?>
  <?php while ( $query->have_posts() ): $query->the_post();?>
    <div class="ajax-posts__post">
      <article <?php post_class(); ?>>
        <a href="<?= get_the_permalink(); ?>">
          <?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium' ); }?>
          <h3><?php the_title(); ?></h3>
        </a>
      </article>            
  </div>
 <?php endwhile; ?>
 <?php if (!$this->is_last_page($query)) : ?>
  <div class="ajax-posts__load-more">
    <button class="js-load-more" data-page="<?= $this->get_page_number($query) + 1; ?>">
      <?php _e('Load more', 'ajax-filter-posts') ?>        
    </button>            
  </div>
   <?php endif; ?>
<?php else: ?>
  <div class="ajax-posts-message ajax-posts-message--empty">
      <h4><?php printf( __('Oh, we couldn\'t find any %s', 'ajax-filter-posts'), $plural_post_name); ?></h4>
      <p><?php printf( __('Try different filters or <a %s> reset them all.</a>', 'ajax-filter-posts'), 'href="#" class="js-reset-filters"'); ?></p>
  </div>
<?php endif; ?>
