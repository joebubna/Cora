<h1>Register Account</h1>
<form method="POST">
    
    <?= $this->repeat($errors, 'li', 'item', 'ul', 'list'); ?>
    
    <div><h3>Email:</h3><input type="text" name="email" value="<?= $Validate->setField('email'); ?>"></div>
    <div><h3>Password:</h3><input type="password" name="password" value="<?= $Validate->setField('password'); ?>"></div>
    <div><h3>Password Confirm:</h3><input type="password" name="password_confirm" value="<?= $Validate->setField('password_confirm'); ?>"></div>
    <br>
    
    <input type="submit" value="Submit">
</form>