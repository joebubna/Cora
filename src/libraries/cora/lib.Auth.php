<?php
namespace Libraries\Cora;
use Models\User;

class Auth
{
    // Data
    protected $userId;
    protected $secureLogin;
    protected $authField;
    protected $container;

    // Dependencies
    protected $users;
    protected $roles;
    protected $db;
    protected $event;
    protected $session;
    protected $cookie;
    protected $redirect;


    public function __construct($userId = false, $secureLogin = false, $authField = 'username', $repoUser, $repoRole, $event, $session, $cookie, $redirect, $container)
    {
        // Repository Dependencies
        $this->users = $repoUser;
        $this->roles = $repoRole;

        // Utility Dependencies
        $this->db = $this->users->getDb();
        $this->event = $event;
        $this->session = $session;
        $this->cookie = $cookie;
        $this->redirect = $redirect;
        $this->container = $container;

        // The unique field to use for authentication. Usually 'username' or 'email'.
        $this->authField = $authField;

        // If user authentication details were passed, use them.
        $this->userId = $userId;
        $this->secureLogin = $secureLogin;

        // If no user is logged in, try a passive (insecure) login from Cookie.
        if ($this->userId == false) {
            $this->insecureLogin($this->cookie->user, $this->cookie->token);
        }
    }


    public static function accountExists($authValue = false, $args = 'email')
    {
        // If you are updating a user's account info, you want to check that their email
        // doesn't already exist if they try and change it, BUT you want to make an exception
        // if the email entered is the same as their existing email (their existing email is "acceptable")
        $acceptable = false;
        $authField = $args;
        if (is_array($args)) {
            $authField = $args[0];
            $acceptable = $args[1];
        }

        // Setup
        $users = \Cora\RepositoryFactory::make('User');
        $db = $users->getDb();

        if ($acceptable) {
            $db->where($authField, $acceptable, '<>');
        }
        $db->where($authField, $authValue);
        $numberOfMatchingUsers = $users->count($db);

        return $numberOfMatchingUsers > 0 ? true : false;
    }


    /**
     *  Returns true or false to whether the current user has access.
     */
    public function accessCheck($authModelInput)
    {
        // Default to allowing access.
        $permission = true;

        // If an array of Auth Models was passed in.
        // Check each permission, if ANY returns false, deny permission.
        if (is_array($authModelInput)) {
            foreach ($authModelInput as $authModel) {
                if ($authModel->handle($this, $this->session->user) == false) {
                    $permission = false;
                }
            }
        }

        // If a single auth model.
        else {
            $authModel = $authModelInput;
            $permission = $authModel->handle($this, $this->session->user);
        }
        //echo $permission ? 'YES' : 'NO';
        return $permission;
    }

    /**
     *  Checks whether or not the current user has access, and redirects if necessary.
     */
    public function access($authModelInput) {
        if ($this->accessCheck($authModelInput)) {
            return true;
        }
        else if (!$this->session->user) {
            // Redirect to login page.
            // With Saved URL for redirect after login.
            $this->redirect->saveUrl();
            $this->redirect->url('/users/login');
            return false;
        }
        else {
            // Show Forbidden 403 code.
            //$error = new \Cora\App\Error($this->container);
            $error = $this->container->error;
            $error->handle('403');
            return false;
        }
    }


    public function hasGroupMembership($userId, $groupName)
    {
        $user = $this->users->find($userId);

        if ($user->groups->contains('name', $groupName)) {
            return true;
        }
        return false;
    }


    /**
     *  Checks if a user has a permission.
     *
     *  $name is the permission name.
     *  $groupId is for if you want to check if a user has permission to perform
     *  an action within the context of a particular group.
     */
    public function hasPermission($userId, $name, $groupId = null)
    {
        // Make sure $userId is set.
        if ($userId === false || $userId == null) {
            return false;
        }

        $user = $this->users->find($userId);

        // Check if has individual permissions first:
        if ($this->hasPermissionFromIndividual($user, $name, $groupId)) {
            return true;
        }

        // If no matching individual permission was found, then check permissions
        // inherited from Roles.
        else if ($this->hasPermissionFromRole($user, $name, $groupId)) {
            return true;
        }

        // If no matching permission was found by this point, then no permission exists.
        return false;
    }

    /**
     *  Checks if a user has an individual permission.
     */
    protected function hasPermissionFromIndividual($user, $name, $groupId = null)
    {
        // Check individual permissions
        foreach ($user->permissions as $perm) {
            if ($perm->name == $name) {

                // If a group limitation is specified anywhere...
                if (isset($groupId) || isset($perm->group)) {
                    // If the permission we're checking and the permission we're iterating over both
                    // have groups defined, check that there's a match. If not, then do nothing
                    // and proceed to next permission iteration.
                    if (isset($groupId) && isset($perm->group) && $groupId == $perm->group->id) {
                        if ($perm->allow == true) {
                            return true;
                        }
                        else {
                            return false;
                        }
                    }
                }

                // If we aren't dealing with groups.
                else {
                    if ($perm->allow == true) {
                        return true;
                    }
                    else {
                        return false;
                    }
                }
            }
        }

        // If nothing matching above was found, default to false.
        return false;
    }


    /**
     *  Check if a user inherits a permission from one of their Roles.
     */
    protected function hasPermissionFromRole($user, $name, $groupId = null)
    {
        // Check if permission exists on user Primary Role first
        if (!empty($user->primaryRole)) {
            if ($this->permissionFromRoleCheck($user->primaryRole, $user, $name, $groupId)) { return true; }
        }

        // Check Role based permissions.
        foreach ($user->roles as $role) {
            if ($this->permissionFromRoleCheck($role, $user, $name, $groupId)) { return true; }
        }
        return false;
    }


    protected function permissionFromRoleCheck($role, $user, $name, $groupId)
    {
        // Since any 'Group' applied to a Role applies to any Permissions it grants,
        // let's do a group matching check first.

        // If either side has a group defined.
        if (isset($role->group) || isset($groupId)) {

            // We know that at least either the permission we're checking or the permission
            // we're iterating over have a group restriction. Since at least one has a group defined,
            // check that the other also has a group defined or else it's not a match.
            // If both have groups defined, then check if the groups match.
            if (isset($groupId) && isset($perm->group) && $role->group->id == $groupId) {

                // The permission we're checking and the role we're looking at both have the same
                // group restriction, so let's see if the permission we're looking for is granted
                // to this Role.
                foreach ($role->permissions as $perm) {
                    if ($perm->name == $name) {
                        if ($perm->allow == true) {
                            return true;
                        }
                        else {
                            return false;
                        }
                    }
                }
            }
        }

        // If we aren't dealing with groups.
        else {
            foreach ($role->permissions as $perm) {
                if ($perm->name == $name) {
                    if ($perm->allow == true) {
                        return true;
                    }
                    else {
                        return false;
                    }
                }
            }
        }
        return false;
    }


    public function userSetPrimaryRoleByName($userId, $roleName)
    {
        // Grab our new user
        $user = $this->users->find($userId);

        // Assign the Provider role
        $user->primaryRole = $this->roles->findBy('name', $roleName)->get(0);
        $this->users->save($user);
    }


    /**
     *  Get the currently logged in user as an object.
     *  If viewer is not logged in, return a default user.
     */
    public function userGetCurrent()
    {
        if (!$this->session->user) {
            return new \Models\User(null, null, new \Models\Role('AnonymousUser'));
        }
        else {
            return $this->users->find($this->session->user);
        }
    }


    public function userCreate($email, $plainTextPassword)
    {
        // Hash password
        $hashedPassword = $this->passwordCreate($plainTextPassword);

        // Create User
        $user = new User($email, $hashedPassword);

        // Save the user to the database.
        $this->users->save($user);

        return $user->id;
    }


    public function userDelete($userId)
    {
        $user = $this->users->find($userId);

        if ($user) {
            $user->delete();
            return true;
        }
        return false;
    }


    public function userTokenCreate($userId)
    {
        $user = $this->users->find($userId);

        if ($user) {
            $user->token = $this->tokenCreate();
            $this->users->save($user);
            return $user->token;
        }
        return false;
    }


    public function userTokenVerify($userId, $token)
    {
        $user = $this->users->find($userId);

        if ($user) {
            if ($user->token == $token) {
                return true;
            }
        }
        return false;
    }


    public function userResetTokenCreate($userId, $days = 1)
    {
        $user = $this->users->find($userId);

        if ($user) {
            $user->resetToken = $this->tokenCreate();
            $user->resetTokenExpire = (new \DateTime())->modify("+$days day");
            $this->users->save($user);
            return $user->resetToken;
        }
        return false;
    }


    public function userResetTokenVerify($userId, $token)
    {
        $user = $this->users->find($userId);

        if ($user) {
            // If there's a token match and the token isn't empty.
            if ($user->resetToken == $token && !empty($token) && ($user->resetTokenExpire >= new \DateTime())) {
                $user->resetToken = '';
                $this->users->save($user);
                return true;
            }
        }
        return false;
    }


    public function passwordUpdate($userId, $plainTextPassword)
    {
        $user = $this->users->find($userId);

        if ($user) {
            $user->password = $this->passwordCreate($plainTextPassword);
            $this->users->save($user);
            return true;
        }
        return false;
    }


    /**
     *  Normal login with a unique field and password.
     */
    public function login($authField, $password, $rememberMe = false)
    {
        // Setup
        $result = false;

        // Attempt to grab user
        $users = $this->users->findBy($this->authField, $authField);

        // If a single matching user was found, return it.
        if ($users->count() == 1) {

            // Grab user
            $user = $users->get(0);

            // Check if password matches
            if ($this->passwordVerify($user, $password)) {

                // Make sure account is active.
                if ($user->status == 'Active') {
                    // Set user as return value.
                    $result = $user;

                    // Perform Actual Login
                    $this->loginProcess($user, $rememberMe);

                    // If there's a saved URL, return to it.
                    if ($this->redirect->isSaved()) {
                        $this->redirect->gotoSaved();
                        exit;
                    }
                }

                // Reject login with warning about account status.
                else {
                    $result = -1;
                }
            }
        }
        return $result;
    }


    /**
     *  Checks if a password is valid for a given user.
     *
     *  @return boolean
     */
    protected function passwordVerify($user, $password)
    {
        return password_verify($password, $user->password);
    }


    /**
     *  Perform the actual login.
     */
    protected function loginProcess($user, $rememberMe = false)
    {
        // Set a logged-in user in session.
        $this->session->user = $user->id;
        $this->session->secureLogin = true;
        $this->user = $user->id;
        $this->secureLogin = true;

        // Set cookie if necessary
        if ($rememberMe) {
            $this->setRememberMe($user);
        }
    }


    public function logout()
    {
        // If a user is logged in, remove their token in the DB.
        // (to prevent passive cookie login on next visit)
        $userId = $this->session->user;
        if ($userId) {
            $user = $this->users->find($userId);
            $user->token = null;
            $this->users->save($user);
        }

        unset($this->userId);
        unset($this->secureLogin);
        $this->session->delete('user');
        $this->session->delete('secureLogin');
        $this->cookie->delete('user');
        $this->cookie->delete('token');
    }


    /**
     *  For passiely logging in via a "rememberMe" cookie.
     */
    protected function insecureLogin($userId = false, $token = false)
    {
        if ($userId && $token) {
            // Try and grab user from DB.
            $user = $this->users->find($userId);

            if ($user) {
                if ($user->token == $token) {

                    // Check if the user is Active
                    if ($user->status == 'Active') {
                        // Set a logged-in user in session.
                        $this->session->user = $user->id;
                        $this->session->secureLogin = false;
                        $this->user = $user->id;
                        $this->secureLogin = false;
                        return true;
                    }

                    // If the user's account isn't active, log them out so this code doesn't get
                    // executed on every page load.
                    else {
                        $this->logout();
                    }
                }
            }
        }
        return false;
    }


    /**
     *  Create a token and save it on both the user's computer and the server.
     */
    protected function setRememberMe($user)
    {
        // Generate a new token.
        $token = $this->tokenCreate();

        // Store token in cookie on user's machine.
        $this->cookie->user = $user->id;
        $this->cookie->token = $token;

        // Store token in the server's database for later comparison.
        $user->token = $token;

        // save the user.
        $this->users->save($user);
    }


    /**
     *  Hash a password and return it.
     */
    protected function passwordCreate($plainTextPassword)
    {
        return password_hash($plainTextPassword, PASSWORD_DEFAULT);
    }

    /**
     *  Generate a hash from a random string and return it.
     */
    protected function tokenCreate()
    {
        return password_hash($this->randomString(), PASSWORD_DEFAULT);
    }


    /**
     *  Return a random string.
     */
    public function randomString($length = 50) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$string = '';

		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, (strlen($characters)-1))];
		}
		return $string;
	}
}
