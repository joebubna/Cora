<h1>Create Article</h1>
<form method="POST">
    <?php if (isset($errors)) { ?>
    <ul>
        <?php foreach ($errors as $error) { ?>
            <li><?= $error; ?></li>
        <?php } ?>
    </ul>
    <?php } ?>
    <div><h3>Title:</h3><input type="text" name="title" value="<?= $this->ifset($saved['title']); ?>"></div>
    <div><h3>Content:</h3><textarea name="content"><?= $this->ifset($saved['content']); ?></textarea></div>
    <input type="submit" value="Submit">
</form>