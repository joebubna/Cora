<div class="row resultset-filters">
    <div class="col-sm-5">
        <form method="GET" action="<?= $paginate->getCalcLink(['query', 'orderBy', 'orderDir', 'orderBy2', 'orderDir2']); ?>">
            <div class="input-group">
                <input type="text" name="query" class="form-control" placeholder="Search">
                <span class="input-group-btn">
                    <button type="submit" class="btn btn-primary">Search</button>
                </span>
            </div>
            <?= $paginate->getCalcInputs(['query', 'orderDir', 'orderBy2', 'orderDir2']); ?>
        </form>
    </div>

    <div class="col-sm-7">
        <ul class="nav nav-pills">
            <li><a href="<?= $paginate->getFilterLink('A'); ?>">A</a></li>
            <li><a href="<?= $paginate->getFilterLink('B'); ?>">B</a></li>
            <li><a href="<?= $paginate->getFilterLink('C'); ?>">C</a></li>
            <li><a href="<?= $paginate->getFilterLink('D'); ?>">D</a></li>
            <li><a href="<?= $paginate->getFilterLink('E'); ?>">E</a></li>
            <li><a href="<?= $paginate->getFilterLink('F'); ?>">F</a></li>
            <li><a href="<?= $paginate->getFilterLink('G'); ?>">G</a></li>
            <li><a href="<?= $paginate->getFilterLink('H'); ?>">H</a></li>
            <li><a href="<?= $paginate->getFilterLink('I'); ?>">I</a></li>
            <li><a href="<?= $paginate->getFilterLink('J'); ?>">J</a></li>
            <li><a href="<?= $paginate->getFilterLink('K'); ?>">K</a></li>
            <li><a href="<?= $paginate->getFilterLink('L'); ?>">L</a></li>
            <li><a href="<?= $paginate->getFilterLink('M'); ?>">M</a></li>
            <li><a href="<?= $paginate->getFilterLink('N'); ?>">N</a></li>
            <li><a href="<?= $paginate->getFilterLink('O'); ?>">O</a></li>
            <li><a href="<?= $paginate->getFilterLink('P'); ?>">P</a></li>
            <li><a href="<?= $paginate->getFilterLink('Q'); ?>">Q</a></li>
            <li><a href="<?= $paginate->getFilterLink('R'); ?>">R</a></li>
            <li><a href="<?= $paginate->getFilterLink('S'); ?>">S</a></li>
            <li><a href="<?= $paginate->getFilterLink('T'); ?>">T</a></li>
            <li><a href="<?= $paginate->getFilterLink('U'); ?>">U</a></li>
            <li><a href="<?= $paginate->getFilterLink('V'); ?>">V</a></li>
            <li><a href="<?= $paginate->getFilterLink('W'); ?>">W</a></li>
            <li><a href="<?= $paginate->getFilterLink('X'); ?>">X</a></li>
            <li><a href="<?= $paginate->getFilterLink('Y'); ?>">Y</a></li>
            <li><a href="<?= $paginate->getFilterLink('Z'); ?>">Z</a></li>
            <li><a href="<?= $paginate->getBaseLink(); ?>">Clear</a></li>
        </ul>
    </div>
</div>