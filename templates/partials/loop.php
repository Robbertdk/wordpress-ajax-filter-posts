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
      <svg xmlns="http://www.w3.org/2000/svg" width="31.95mm" height="41.85mm" viewBox="0 0 90.57 118.62"><defs><style>.a{fill:transparent;}.b,.c,.d,.e,.f,.g{fill:none;stroke:#aaa;stroke-miterlimit:10;}.b,.c,.d,.e,.f{stroke-width:1.45px;}.c{stroke-dasharray:5.41 5.41;}.d{stroke-dasharray:5.86 5.86;}.e{stroke-dasharray:6.41 6.41;}.f{stroke-dasharray:5.75 5.75;}.g{stroke-width:1.48px;stroke-dasharray:5.91;}</style></defs><polygon class="a" points="87.74 117.89 0.73 117.89 0.73 0.73 64.26 0.73 87.74 25.74 87.74 117.89"/><polyline class="b" points="87.74 114.98 87.74 117.89 84.84 117.89"/><line class="c" x1="79.42" y1="117.89" x2="6.34" y2="117.89"/><polyline class="b" points="3.63 117.89 0.73 117.89 0.73 114.98"/><line class="d" x1="0.73" y1="109.12" x2="0.73" y2="6.56"/><polyline class="b" points="0.73 3.63 0.73 0.73 3.63 0.73"/><line class="e" x1="10.05" y1="0.73" x2="58.14" y2="0.73"/><polyline class="b" points="61.35 0.73 64.26 0.73 66.25 2.85"/><polyline class="f" points="70.18 7.03 87.74 25.74 87.74 112.11"/><polyline class="g" points="63.89 0.73 63.89 26.18 87.74 26.18"/>
      </svg>
      <h4><?php printf( __('Oh, we couldn\'t find any %s', 'ajax-filter-posts'), $plural_post_name); ?></h4>
      <p><?php printf( __('Try different filters or <a %s> reset them all.</a>', 'ajax-filter-posts'), 'href="#" class="js-reset-filters"'); ?></p>
  </div>
<?php endif; ?>
