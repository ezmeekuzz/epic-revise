<?php

use CodeIgniter\Pager\PagerRenderer;

/**
 * @var PagerRenderer $pager
 */
$pager->setSurroundCount(2);

if ($pager->getPageCount() <= 1) {
    return;
}
?>
<nav aria-label="<?= lang('Pager.pageNavigation') ?>">
    <ul class="pagination">
        <li class="page-item<?= $pager->hasPrevious() ? '' : ' disabled' ?>">
            <a class="page-link" href="<?= $pager->hasPrevious() ? $pager->getFirst() : '#' ?>" aria-label="<?= lang('Pager.first') ?>">&laquo;</a>
        </li>
        <li class="page-item<?= $pager->hasPrevious() ? '' : ' disabled' ?>">
            <a class="page-link" href="<?= $pager->hasPrevious() ? $pager->getPrevious() : '#' ?>" aria-label="<?= lang('Pager.previous') ?>">&lsaquo;</a>
        </li>

        <?php foreach ($pager->links() as $link) : ?>
            <li class="page-item<?= $link['active'] ? ' active' : '' ?>">
                <a class="page-link" href="<?= $link['uri'] ?>"><?= $link['title'] ?></a>
            </li>
        <?php endforeach ?>

        <li class="page-item<?= $pager->hasNext() ? '' : ' disabled' ?>">
            <a class="page-link" href="<?= $pager->hasNext() ? $pager->getNext() : '#' ?>" aria-label="<?= lang('Pager.next') ?>">&rsaquo;</a>
        </li>
        <li class="page-item<?= $pager->hasNext() ? '' : ' disabled' ?>">
            <a class="page-link" href="<?= $pager->hasNext() ? $pager->getLast() : '#' ?>" aria-label="<?= lang('Pager.last') ?>">&raquo;</a>
        </li>
    </ul>
</nav>
