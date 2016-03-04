<?PHP

class MyApp extends Cora
{

    protected $container;
    
    public function __construct($container)
    {
        parent::__construct(); 
               
        $this->container = false;
    }

}