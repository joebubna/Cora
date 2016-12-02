<?PHP

class Cora {

    protected $load;
    protected $data;

    public function __construct($container = false)
    {
        // Blank objects for storing stuff in.
        $this->data = new stdClass(); // Is passed in to Views
        $this->site = new stdClass(); // Is for storing things such as the logged in User, etc.

        // If site specific data was passed along, store it.
        $this->data->site = $container;

        // Init useful Cora classes
        $this->load = new Cora\Load();
        $this->input = new Cora\Input();


        // Store config settings as data member.
        require(dirname(__FILE__).'/../config/config.php'); // Load and set cora config.
        include($config['basedir'].'cora/config/config.php'); // Load custom app config
        if (file_exists($config['basedir'].'cora/config/local.config.php')) {
            include($config['basedir'].'cora/config/local.config.php');
        }
        $this->config = $config;
    }

    public function setData($property, $value)
    {
        $this->data->$property = $value;
    }

    protected function _loadView($method, $data = false)
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

        // Cut off the base namespace. Should be 'Controllers'
        array_shift($namespaces);

        foreach ($namespaces as $namespace) {
            //convert namespace name from camel case to underscores
            $path_string .= strtolower(str_replace('\\', '/', (preg_replace('/(?:(?<!^)([A-Z]))/', $sep.'$1', $namespace)))).'/';
        }
        return $path_string;
    }

}
