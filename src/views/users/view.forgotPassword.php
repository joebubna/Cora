<div class="page-header">
    <a class="btn btn-danger pull-right" href="<?= $redirect->getRedirect(-1); ?>">Cancel</a>
    <h1>Forgot Password</h1>
    <h2>Enter email to initiate reset</h2>
</div>

<div class="row col-sm-4">
<form method="POST">

    <div class="form-group">
    <?= $this->repeat($notices, 'li', 'item', 'ul', 'list'); ?>
    <?= $this->repeat($errors, 'li', 'item', 'ul', 'list'); ?>
    </div>

    <div class="form-group">
        <label for="inputEmail">Email</label>
        <input type="email" id="inputEmail" name="email" class="form-control" value="<?= $Validate->setField('email'); ?>">
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary btn-block">Send Password Reset Email</button>
    </div>
</form>
</div>