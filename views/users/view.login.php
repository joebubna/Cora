<h1>Login</h1>
<form method="POST">
    
    <?= $this->repeat($notices, 'li', 'item', 'ul', 'list'); ?>
    <?= $this->repeat($errors, 'li', 'item', 'ul', 'list'); ?>
    
    
    <div><h3>Email:</h3><input type="text" name="email" value="<?= $Validate->setField('email'); ?>"></div>
    <div><h3>Password:</h3><input type="password" name="password" value="<?= $Validate->setField('password'); ?>"></div>
    
    <div>
        Remember Me: <input type="checkbox" name="rememberMe[]" value="1" <?= $Validate->setCheckbox('rememberMe', '1'); ?> /><br>
    </div>
    <br>
    
    <input type="submit" value="Login">
</form>