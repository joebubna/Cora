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
    
    public function _loadView($method, $data = false)
    {
        if ($data == false) {
            $data = $this->data;
        }
        
        $this->data->content = $this->load->view($this->_getTemplateFilePath($method), $data, true);
        $this->load->view('', $data);
    }
    
    protected function _getTemplateFilePath($function)
    {
        return $this->_convertClassNameToPath('-').$function;
    }

    protected function _convertClassNameToPath($sep = '_')
    {
        $path_string = '';
        //get all namespace levels
        $namespaces = explode('\\', get_class($this));
        foreach ($namespaces as $namespace) {
            //convert namespace name from camel case to underscores
            $path_string .= strtolower(str_replace('\\', '/', (preg_replace('/(?:(?<!^)([A-Z]))/', $sep.'$1', $namespace)))).'/';
        }
        return $path_string;
    }

}