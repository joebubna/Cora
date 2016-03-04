<?PHP

class Cora {

    protected $load;
    protected $data;
    protected $container;
    
    function __construct($container) {
        $this->load = new Cora\Load();
        $this->data = new stdClass();
        $this->container = $container;
        $this->data->styles = $this->container['styles'];
        $this->data->scripts = $this->container['scripts'];
    }
}