<?PHP

class MyApp extends Cora
{   
    protected $db;
    
    public function __construct($container = false)
    {
        parent::__construct($container); 
        
        $this->app = $container;
        $this->db = $this->app->db();
        $this->event = $this->app->event();
        $this->session = $this->app->session();
        $this->cookie = $this->app->cookie();
        $this->redirect = $this->app->redirect();
        
        $this->auth = $this->app->auth($this->session->user, $this->session->loginSecure, 'username');
    }

}