<h1>Update Your Account</h1>
<div class="row col-sm-4">
    <form method="POST">
        
        <div class="form-group">
        <?= $this->repeat($notices, 'li', 'item', 'ul', 'list'); ?>
        <?= $this->repeat($errors, 'li', 'item', 'ul', 'list'); ?>
        </div>

        <div class="form-group">
            <label for="inputFirstName">First Name:</label>
            <input type="text" name="firstName" id="inputFirstName" class="form-control" value="<?= $Validate->setField('firstName', $user->firstName); ?>">
        </div>

        <div class="form-group">
            <label for="inputLastName">Last Name:</label>
            <input type="text" name="lastName" id="inputLastName" class="form-control" value="<?= $Validate->setField('lastName', $user->lastName); ?>">
        </div>
        
        <div class="form-group">
            <label for="inputEmail">Email:</label>
            <input type="email" name="email" id="inputEmail" class="form-control" value="<?= $Validate->setField('email', $user->email); ?>" placeholder="example@gmail.com">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Update</button>
        </div>
    </form>
</div>