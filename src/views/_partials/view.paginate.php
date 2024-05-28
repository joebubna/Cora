<nav aria-label="Page navigation" class="text-center">
    <ul class="pagination">
        <li class="<?= $paginate->active($paginate->showPrevious()); ?> hidden-xs">
            <<?= $paginate->display($paginate->showPrevious(), 'a', 'span'); ?> href="<?= $paginate->getLink(0); ?>" aria-label="Previous">
                <span aria-hidden="true">&#10094;&#10094; First</span>
            </a>
        </li>
        <li class="<?= $paginate->active($paginate->showPrevious()); ?>">
            <<?= $paginate->display($paginate->showPrevious(), 'a', 'span'); ?> href="<?= $paginate->getRelLink(-1); ?>" aria-label="Previous">
                <span aria-hidden="true">&#10094; Prev</span>
            </a>
        </li>
        <li class="<?= $paginate->display($paginate->showRelOffset(-2)); ?> hidden-xs">
            <a href="<?= $paginate->getRelLink(-2); ?>"><?= $paginate->getOffset() - 1; ?></a>
        </li>
        <li class="<?= $paginate->display($paginate->showRelOffset(-1)); ?>">
            <a href="<?= $paginate->getRelLink(-1); ?>"><?= $paginate->getOffset(); ?></a>
        </li>
        <li class="active">
            <span><?= $paginate->getOffset() + 1; ?></span>
        </li>
        <li class="<?= $paginate->display($paginate->showRelOffset(+1)); ?>">
            <a href="<?= $paginate->getRelLink(+1); ?>"><?= $paginate->getOffset() + 2; ?></a>
        </li>
        <li class="<?= $paginate->display($paginate->showRelOffset(+2)); ?> hidden-xs">
            <a href="<?= $paginate->getRelLink(+2); ?>"><?= $paginate->getOffset() + 3; ?></a>
        </li>
        <li class="<?= $paginate->display($paginate->showRelOffset(+3)); ?> hidden-xs">
            <a href="<?= $paginate->getRelLink(+3); ?>"><?= $paginate->getOffset() + 4; ?></a>
        </li>
        <li class="<?= $paginate->display($paginate->showRelOffset(+4)); ?> hidden-xs">
            <a href="<?= $paginate->getRelLink(+4); ?>"><?= $paginate->getOffset() + 5; ?></a>
        </li>
        <li class="<?= $paginate->active($paginate->showNext()); ?>">
            <<?= $paginate->display($paginate->showNext(), 'a', 'span'); ?> href="<?= $paginate->getRelLink(1); ?>" aria-label="Next">
                <span aria-hidden="true">Next &#10095;</span>
            </a>
        </li>
        <li class="<?= $paginate->active($paginate->showNext()); ?> hidden-xs">
            <<?= $paginate->display($paginate->showNext(), 'a', 'span'); ?> href="<?= $paginate->getLink($paginate->getNumPages()-1); ?>" aria-label="Next">
                <span aria-hidden="true">Last &#10095;&#10095;</span>
            </a>
        </li>
    </ul>
</nav>