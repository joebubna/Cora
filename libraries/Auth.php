<?php
namespace Library;

class Auth
{
    protected $authField;
    
    
    public function __construct($authField = 'username')
    {
        $this->repo = \Cora\RepositoryFactory::make('User');
        $this->db = $this->repo->getDb();
        $this->event = new \Cora\EventManager(new \EventMapping());
        $this->session = new \Cora\Session();
        
        $this->authField = $authField;
    }
    
    
    public static function accountExists($authValue = false, $authField = false)
    {
        $repo = \Cora\RepositoryFactory::make('User');
        $numberOfMatchingUsers = $repo->findBy($authField, $authValue)->count();
        
        return $numberOfMatchingUsers > 0 ? true : false;
    }
    
    
    // Access Level
    // Individual permissions, can be gained from group
    // User Type
    // 
    public function access($user_id, $level = 1, $secureRequired = false)
    {
        
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
    
    
    public function userDelete($user_id)
    {
        $user = $this->repo->find($user_id);
        
        if ($user) {
            $user->delete();
            return true;
        }
        return false; 
    }
    
    
    public function userTokenCreate($user_id)
    {
        $user = $this->repo->find($user_id);
        
        if ($user) {
            $user->token = $this->tokenCreate();
            $this->repo->save($user);
            return true;
        }
        return false; 
    }
    
    
    public function userTokenVerify($user_id, $token)
    {
        $user = $this->repo->find($user_id);
        
        if ($user) {
            if ($user->token == $token) {
                return true;
            }
        }
        return false; 
    }
    
    
    public function passwordUpdate($user_id, $plainTextPassword)
    {
        $user = $this->repo->find($user_id);
        
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
        
        // Hash password
        $hashedPassword = $this->passwordCreate($password);
        
        // Attempt to grab user
        $users = $this->repo->findBy($this->authField, $authField);
        
        // If a single matching user was found, return it.
        if ($users->count() == 1) {
            
            // Grab user and set it as the return value.
            $user = $users->get(0);
            $result = $user;
            
            // Set a logged-in user in session.
            $this->session->user = $user->id;
            $this->session->secureLogin = true;
            
            // Set cookie if necessary
            if ($rememberMe) {
                $this->setRememberMe($user);
            }
        }
        
        return $result;
    }
    
    
    public function logout()
    {
        $user_id = $this->session->user;
        $user = $this->repo->find($user_id);
        $user->token = null;
        $this->repo->save($user);
        
        unset($this->session->user);
        unset($this->session->secureLogin);
        unset($this->cookie->user);
        unset($this->cookie->token);
    }
    
    
    /**
     *  For passiely logging in via a "rememberMe" cookie.
     */
    protected function insecureLogin($user_id, $token)
    {
        $user = $this->repo->find($user_id);
        
        if ($user) {
            if ($user->token == $token) {
                // Set a logged-in user in session.
                $this->session->user = $user->id;
                $this->session->secureLogin = false;
                return true;
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
        $toke = $this->tokenCreate();
        
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
        return password_hash($this->randomString, PASSWORD_DEFAULT);
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