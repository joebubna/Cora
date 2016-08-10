<h1>Register Provider Account</h1>
<form method="POST">
    
    <?= $this->repeat($errors, 'li', 'item', 'ul', 'list'); ?>
    
    
    <div><h3>Username:</h3><input type="text" name="username" value="<?= $Validate->setField('username'); ?>"></div>
    <div><h3>Email:</h3><input type="text" name="email" value="<?= $Validate->setField('email'); ?>"></div>
    <div><h3>Password:</h3><input type="text" name="password" value="<?= $Validate->setField('password'); ?>"></div>
    <div><h3>Password Confirm:</h3><input type="text" name="password" value="<?= $Validate->setField('password'); ?>"></div>
    <br>
    
    <input type="submit" value="Submit">
</form>