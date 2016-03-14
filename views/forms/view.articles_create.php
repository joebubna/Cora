<h1>Create Article</h1>
<form method="POST">
    <?php if (isset($errors)) { ?>
    <ul>
        <?php foreach ($errors as $error) { ?>
            <li><?= $error; ?></li>
        <?php } ?>
    </ul>
    <?php } ?>
    
    
    <div><h3>Title:</h3><input type="text" name="title" value="<?= $Validate->setField('title', 'test'); ?>"></div>
    <div><h3>Content:</h3><textarea name="content"><?= $Validate->setField('content'); ?></textarea></div>
    <br>
    
    <div>
        Item 1: <input type="checkbox" name="mycheck[]" value="1" <?= $Validate->setCheckbox('mycheck', '1'); ?> /><br>
        Item 2: <input type="checkbox" name="mycheck[]" value="2" <?= $Validate->setCheckbox('mycheck', '2', true); ?> />
    </div>
    <br>
    
    <div>
        <select name="myselect">
            <option value="Option One" <?= $Validate->setSelect('myselect', 'Option One'); ?> >One</option>
            <option value="Option Two" <?= $Validate->setSelect('myselect', 'Option Two', true); ?> >Two</option>
            <option value="Option Three" <?= $Validate->setSelect('myselect', 'Option Three'); ?> >Three</option>
        </select>
    </div>
    <br>
    
    <input type="submit" value="Submit">
</form>