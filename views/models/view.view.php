<h1><?= (new \ReflectionClass($model))->getShortName(); ?> Info</h1>

<form method="POST">
    <?php foreach ($model->model_attributes as $attribute => $definition) { ?>
        <?php if (!isset($definition['models']) && !isset($definition['model']) && !isset($definition['primaryKey'])) { ?>
            <div class="form-group">
                <label for="<?= $attribute; ?>"><?= $attribute; ?></label>
                <?php if (isset($definition['type']) && in_array($definition['type'], ['binary', 'tinytext', 'text', 'mediumtext', 'longtext', 'tinyblob', 'mediumblob', 'blob', 'longblob'])) { ?>
                    <textarea class="form-control" id="<?= $attribute; ?>" name="<?= $attribute; ?>"><?= $model->$attribute; ?></textarea>
                <?php } else if (isset($definition['type']) && in_array($definition['type'], ['date', 'datetime']) && isset($model->$attribute)) { ?>
                    <input type="text" class="form-control" id="<?= $attribute; ?>" name="<?= $attribute; ?>" value="<?= $model->$attribute->format('m/d/Y'); ?>">
                <?php } else { ?>
                    <input type="text" class="form-control" id="<?= $attribute; ?>" name="<?= $attribute; ?>" value="<?= $model->$attribute; ?>">
                <?php } ?>
            </div>
        <?php } ?>
    <?php } ?>

    <button type="submit" class="btn btn-default">Submit</button>
</form>