<h1>Register Account</h1>
<div class="row col-sm-4">
    <form method="POST">
        
        <div class="form-group">
        <?= $this->repeat($notices, 'li', 'item', 'ul', 'list'); ?>
        <?= $this->repeat($errors, 'li', 'item', 'ul', 'list'); ?>
        </div>
        
        <div class="form-group">
            <label for="inputEmail">Email:</label>
            <input type="email" name="email" id="inputEmail" class="form-control" value="<?= $Validate->setField('email'); ?>" placeholder="example@gmail.com">
        </div>

        <div class="form-group">
            <label for="inputPassword">Password:</label>
            <input type="password" name="password" id="inputPassword" class="form-control" value="<?= $Validate->setField('password'); ?>">
        </div>

        <div class="form-group">
            <label for="inputPasswordConfirm">Password Confirm:</label>
            <input type="password" name="password_confirm" id="inputPasswordConfirm" class="form-control" value="<?= $Validate->setField('password_confirm'); ?>">
        </div>
        <br>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </div>
    </form>
</div>