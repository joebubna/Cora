<div class="row col-sm-4">
    <h1>Login</h1>
    <form method="POST" >

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
            <div class="form-check">
                <label class="form-check-label">
                    <input class="form-check-input" type="checkbox" name="rememberMe[]" value="1" <?= $Validate->setCheckbox('rememberMe', '1'); ?>> Remember Me
                </label>
            </div>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Sign in</button>
        </div>
    </form>
</div>