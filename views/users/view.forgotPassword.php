<h1>Forgot Password</h1>
<form method="POST">
    
    <?= $this->repeat($errors, 'li', 'item', 'ul', 'list'); ?>
    
    Enter your username and a password reset email will be sent:
    <div><h3>Email:</h3><input type="text" name="email" value="<?= $Validate->setField('email'); ?>"></div>

    <br>
    
    <input type="submit" value="Send Password Reset Email">
</form>