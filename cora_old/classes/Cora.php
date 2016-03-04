<?PHP

class Cora {

    protected $load;
    protected $data;
    
    function __construct() {
        $this->load = new Cora\Load();
        $this->data = new stdClass();
    }
}