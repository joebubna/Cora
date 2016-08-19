<h1>Forgot Password</h1>
<form method="POST">
    
    <?= $this->repeat($errors, 'li', 'item', 'ul', 'list'); ?>
    
    Enter your username and a password reset email will be sent:
    <div><h3>Username:</h3><input type="text" name="username" value="<?= $Validate->setField('username'); ?>"></div>

    <br>
    
    <input type="submit" value="Send Password Reset Email">
</form>