<?PHP

class Cora {

    protected $load;
    protected $data;
    
    function __construct($frameworkHandle) {
        $this->load = $frameworkHandle;
        $this->data = new stdClass();
    }
}