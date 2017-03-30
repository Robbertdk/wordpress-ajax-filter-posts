<?php foreach ($filterlists as $filterlist) : ?>
  <h3><?= $filterlist['name'] ?></h3>
  <ul>
      <?php foreach ($filterlist['filters'] as $filter) : ?>
          <li>
              <a href="<?= get_term_link( $filter, $filter->taxonomy ); ?>" class="ajax-posts__filter" data-filter="<?= $filter->taxonomy; ?>" data-term="<?= $filter->slug; ?>">
                  <?= $filter->name; ?>
              </a>
          </li>
      <?php endforeach; ?>
  </ul>
<?php endforeach; ?>