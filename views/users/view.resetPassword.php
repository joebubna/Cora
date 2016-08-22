<h1>Reset Password</h1>
<form method="POST">
    
    <?= $this->repeat($errors, 'li', 'item', 'ul', 'list'); ?>
    
    <div><h3>Desired Password:</h3><input type="password" name="password" value="<?= $Validate->setField('password'); ?>"></div>
    <div><h3>Password Confirm:</h3><input type="password" name="password_confirm" value="<?= $Validate->setField('password_confirm'); ?>"></div>
    <br>
    
    <input type="submit" value="Submit">
</form>