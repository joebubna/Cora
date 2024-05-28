<div class="page-header">
    <h1>Reset Password</h1>
    <h2>Enter a new password for your account</h2>
</div>

<div class="row col-sm-4">
<form method="POST">

    <div class="form-group">
    <?= $this->repeat($notices, 'li', 'item', 'ul', 'list'); ?>
    <?= $this->repeat($errors, 'li', 'item', 'ul', 'list'); ?>
    </div>

    <div class="form-group">
        <label for="inputPassword">Desired Password</label>
        <input type="password" id="inputPassword" name="password" class="form-control" value="<?= $Validate->setField('password'); ?>">
    </div>

    <div class="form-group">
        <label for="inputConfirm">Password Confirm</label>
        <input type="password" id="inputConfirm" name="password_confirm" class="form-control" value="<?= $Validate->setField('password_confirm'); ?>">
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary btn-block">Update Password</button>
    </div>
</form>
</div>