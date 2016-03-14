<?PHP

class Cora {

    protected $load;
    protected $data;
    
    public function __construct($container = false)
    {
        // Blank object for storing stuff in.
        $this->data = new stdClass();
        
        // If site specific data was passed along, store it.
        $this->data->site = $container;
        
        // Init useful Cora classes
        $this->load = new Cora\Load();
        $this->input = new Cora\Input();

    }
    
    public function setData($property, $value)
    {
        $this->data->$property = $value;
    }

}