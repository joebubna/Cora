<?php
namespace Cora;

class Auth
{
    // Data
    protected $userId;
    protected $secureLogin;
    protected $authField;
    
    // Dependencies
    protected $repo;
    protected $db;
    protected $event;
    protected $session;
    protected $cookie;
    protected $redirect;
    
    
    public function __construct($userId = false, $secureLogin = false, $authField = 'username', $repo, $event, $session, $cookie, $redirect)
    {
        // Dependencies
        $this->repo = $repo;
        $this->db = $this->repo->getDb();
        $this->event = $event;
        $this->session = $session;
        $this->cookie = $cookie;
        $this->redirect = $redirect;
        
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
    
    
    public static function accountExists($authValue = false, $authField = false)
    {
        $repo = \Cora\RepositoryFactory::make('User');
        $numberOfMatchingUsers = $repo->findBy($authField, $authValue)->count();
        
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
        else if (!isset($this->session->user)) {
            // Redirect to login page.
            // With Saved URL for redirect after login.
            $this->redirect->saveUrl();
            $this->redirect->url('/users/login');
        }
        else {
            // Show Forbidden 403 code.
            $error = new \Cora\Error();
            $error->handle('403');
            exit;
        }
    }
    
    
    public function hasGroupMembership($userId, $groupName)
    {
        $user = $this->repo->find($userId);
        
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
        $user = $this->repo->find($userId);
        
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
        // Check Role based permissions.
        foreach ($user->roles as $role) {
            
            // Since any 'Group' applied to a Role applies to any Permissions it grants, 
            // let's do a group matching check first.
            
            // If either side has a group defined.
            if (isset($role->group) || isset($groupId)) {
                
                // We know that at least either the permission we're checking or the permission
                // we're iterating over have a group restriction. Since at least one has a group defined,
                // check that the other also has a group defined or else it's not a match.
                // If both have groups defined, then check if the groups matchs.
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
        }
    }
    
    
    public function userCreate($username, $email, $plainTextPassword)
    {
        // Hash password
        $hashedPassword = $this->passwordCreate($plainTextPassword);
        
        // Create User
        $user = new \User($username, $email, $hashedPassword);
            
        // Save the user to the database.
        $this->repo->save($user);
        
        return $user->id;
    }
    
    
    public function userDelete($userId)
    {
        $user = $this->repo->find($userId);
        
        if ($user) {
            $user->delete();
            return true;
        }
        return false; 
    }
    
    
    public function userTokenCreate($userId)
    {
        $user = $this->repo->find($userId);
        
        if ($user) {
            $user->token = $this->tokenCreate();
            $this->repo->save($user);
            return $user->token;
        }
        return false; 
    }
    
    
    public function userTokenVerify($userId, $token)
    {
        $user = $this->repo->find($userId);
        
        if ($user) {
            if ($user->token == $token) {
                return true;
            }
        }
        return false; 
    }
    
    
    public function userResetTokenCreate($userId)
    {
        $user = $this->repo->find($userId);
        
        if ($user) {
            $user->resetToken = $this->tokenCreate();
            $user->resetTokenExpire = (new \DateTime())->modify('+1 day');
            $this->repo->save($user);
            return $user->resetToken;
        }
        return false; 
    }
    
    
    public function userResetTokenVerify($userId, $token)
    {
        $user = $this->repo->find($userId);
        
        if ($user) {
            // If there's a token match and the token isn't empty.
            if ($user->resetToken == $token && !empty($token) && ($user->resetTokenExpire >= new \DateTime())) {
                $user->resetToken = '';
                $this->repo->save($user);
                return true;
            }
        }
        return false; 
    }
    
    
    public function passwordUpdate($userId, $plainTextPassword)
    {
        $user = $this->repo->find($userId);
        
        if ($user) {
            $user->password = $this->passwordCreate($plainTextPassword);
            $this->repo->save($user);
            return true;
        }
        return false; 
    }
    
    
    /**
     *  Normal login with a username and password.
     */
    public function login($authField, $password, $rememberMe = false)
    {
        // Setup
        $result = false;
        
        // Attempt to grab user
        $users = $this->repo->findBy($this->authField, $authField);
        
        // If a single matching user was found, return it.
        if ($users->count() == 1) {
            
            // Grab user
            $user = $users->get(0);
            
            // Check if password matches
            if (password_verify($password, $user->password)) {
               
                // Set user as return value.
                $result = $user;
            
                // Set a logged-in user in session.
                $this->session->user = $user->id;
                $this->session->secureLogin = true;
                $this->user = $user->id;
                $this->secureLogin = true;

                // Set cookie if necessary
                if ($rememberMe) {
                    $this->setRememberMe($user);
                }
                
                // If there's a saved URL, return to it.
                if ($this->redirect->isSaved()) {
                    $this->redirect->gotoSaved();
                    exit;
                }
            }
        }
        return $result;
    }
    
    
    public function logout()
    {
        // If a user is logged in, remove their token in the DB.
        // (to prevent passive cookie login on next visit)
        $userId = $this->session->user;
        if ($userId) {
            $user = $this->repo->find($userId);
            $user->token = null;
            $this->repo->save($user);
        }
         
        unset($this->user);
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
            $user = $this->repo->find($userId);
        
            if ($user) {
                if ($user->token == $token) {
                    // Set a logged-in user in session.
                    $this->session->user = $user->id;
                    $this->session->secureLogin = false;
                    $this->user = $user->id;
                    $this->secureLogin = false;
                    return true;
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
        $this->repo->save($user);
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
    protected function randomString($length = 50) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$string = '';

		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, (strlen($characters)-1))];
		}
		return $string;
	}
}