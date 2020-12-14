<?php foreach ($filterlists as $filterlist) : ?>
  <div class="ajax-posts__filterlist">
    <h3><?= $filterlist['name'] ?></h3>
    <ul class="ajax-post__filter-category <?php echo esc_html( $filterlist['id'] ); ?>">
      <?php foreach ($filterlist['filters'] as $filter) : ?>
        <li>
          <a href="<?= get_term_link( $filter, $filter->taxonomy ); ?>" class="ajax-posts__filter" data-filter="<?= $filter->taxonomy; ?>" data-term="<?= $filter->slug; ?>">
              <?= $filter->name; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php if (count($filterlist['filters']) >= 5): ?>
      <div class="ajax-posts__filter-toggle">
        <a href="#" class="js-collapse-filterlist ajax-posts__filter-toggle-show"><?php echo esc_html( __( 'Show more', 'ajax-filter-posts' ) ); ?></a>
        <a href="#" class="js-collapse-filterlist ajax-posts__filter-toggle-hide"><?php echo esc_html( __( 'Show less', 'ajax-filter-posts' ) ); ?></a>
      </div>
  <?php endif; ?>
  </div>
<?php endforeach; ?>