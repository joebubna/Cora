<h1><?= (new \ReflectionClass($models[0]))->getShortName(); ?> Data</h1>

<table class="model-list">
    <tr>
        <?php foreach ($modelFields as $field) { ?>
            <th><?= ucfirst($field); ?></th>
        <?php } ?>
    </tr>
    <?php foreach ($models as $model) { ?>
        <tr>
        <?php foreach ($modelFields as $field) { ?>
            <td><?= $model->$field; ?></td>
        <?php } ?>
        </tr>
    <?php } ?>
</table>