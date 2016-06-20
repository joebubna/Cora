<?PHP

class MyApp extends Cora
{   
    protected $db;
    
    public function __construct()
    {
        parent::__construct(); 
        $this->db = new \Cora\Db_MySQL();
    }

}