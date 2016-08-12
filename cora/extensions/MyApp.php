<?PHP

class MyApp extends Cora
{   
    protected $db;
    
    public function __construct()
    {
        parent::__construct(); 
        $this->db       = new \Cora\Db_MySQL();
        $this->event    = new \Cora\EventManager(new EventMapping);
        $this->session  = new \Cora\Session();
        $this->cookie   = new \Cora\Cookie();
        $this->auth     = new \Library\Auth();
    }

}