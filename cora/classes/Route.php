<?PHP
namespace Cora;

class Route extends Framework
{
    
    protected $debug;
    protected $container;
    protected $pathString;
    protected $path;
    protected $controllerPath;      // STRING - filepath to controller .php file.
    protected $controllerOffset;    // INT - The offset within the path array of the controller.
    protected $controllerName;      // STRING
    protected $controllerNamespace; // STRING
    
    
    public function __construct($container = false)
    {
        parent::__construct(); // Call parent constructor too so we don't lose functionality.
        
        spl_autoload_register(array($this, 'autoLoader'));
        
        // For site specific data. This will be passed to Cora's controllers when they
        // are invoked in the routeExec() method.
        $this->container = $container;
        
        // Figure out and set pathString. E.g. "/controller/method/id"
        $cleanURI = str_replace('?'.$_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
        $this->pathString = explode($this->config['site_url'], $cleanURI, 2)[1];
        
        // Setup Path array
        $this->path = explode('/', $this->pathString);
        
        // Debug
        $this->debug('Route: ' . $this->pathString);

    }
    
    
    /**
     *  Searches through $path to figure out what part of it is the controller.
     *  This requires searching through the filesystem.
     *
     *  If   $path = /folder1/folder2/Controller/Method/Id
     *  Then $controllerPath    = '/folder1/folder2/Controller'
     *  And  $controllerOffset  = 2
     *
     *  NOTE: This is a recursive function.
     */
    public function routeFind($basePath = '', $offset = 0)
    {
        $this->debug('');
        
        // Vars setup
        $curPath = $this->partialPathArray($offset, 1);
        $controller = '';
        $controllerFileName = '';
        
        $this->debug('Current Path: '.implode('/', $curPath));

        // if $curPath isn't empty
        if (is_array($curPath) and !empty($curPath) and $curPath[0] != '') {
            $controller = $curPath[0];
            $controllerFileName =   $this->config['controllersPrefix'] .
                                    $this->getClassName($controller) .
                                    $this->config['controllersPostfix'] .
                                    '.php';
        }
        
        // Else grab the default controller.
        else {
            $controller = $this->config['default_controller'];
            $controllerFileName =   $this->config['controllersPrefix'] .
                                    $this->getClassName($this->config['default_controller']) .
                                    $this->config['controllersPostfix'] .
                                    '.php';
        }

        // Set working filepath.
        $dirpath = $this->config['pathToControllers'].$basePath.$controller;
        $filepath = $this->config['pathToControllers'].$basePath.$controllerFileName;

        // Debug
        $this->debug('Searching for: ' . $filepath);
        
        //$this->debug('Namespace: '.$this->controllerNamespace);

        // Check if the controller .php file exists in this directory.
        if (file_exists($filepath)) {
            $this->controllerPath = $this->partialPathString(0, $offset+1);
            $this->controllerOffset = $offset;
            $this->controllerName = $controller;
            
            $this->debug('File Found: ' . $controllerFileName);
            $this->debug('Controller Path: ' . $this->controllerPath);
            
            // Create instance of controller and check if that method exists in it. Otherwise we want to keep
            // looking for other sub-folder classes that might map to the url.
            $controllerInstance = $this->getController();
            $method = $this->getMethod();
            $this->debug("getMethod() call in routeFind() returns: ".$method);
            
            if (!method_exists($controllerInstance, $method)) {
                
                // Update namespace
                $this->controllerNamespace .= $this->getClassName($controller).'\\';
                
                //  Controller->Method combo doesn't exist, so undo previously set data.
                $this->controllerPath = null;
                $this->controllerOffset = null;
                $this->controllerName = null;
                
                // Debug
                $this->debug('Not matching method within controller. Continuing search.');
                
                // Recursive call
                //$this->routeFind($basePath . $controller . '/', $offset+1);
            }
            else {
                return false;
            }
        }

        // Else check if there's a matching directory we can look through.
        if (is_dir($dirpath)) {
            $this->debug('Directory Found: ' . $basePath . $controller);
            
            // Recursive call
            $this->routeFind($basePath . $controller . '/', $offset+1);
        }
        
    } // end routeFind
    
    
    /**
     *  Uses the info generated by routeFind() to then create an instance of the
     *  appropriate controller and call the desired method.
     */
    public function routeExec()
    {
        
        // If no controller was found by routeFind()...
        if (!isset($this->controllerPath)) {
            $this->debug('routeExec: No controllerPath set. Routing to 404.');
            $this->error404();
            exit;
        }
        
        $controller = $this->getController();
        $method = $this->getMethod();
        
        // If that method exists, call it or output 404 error.
        if (method_exists($controller, $method)) {
            
            // Setup and clean method arguments in URL
            $methodArgs = $this->partialPathArray($this->controllerOffset+2);

            if (empty($methodArgs)) {
                array_push($methodArgs, '');
            }
            $input = new Input($methodArgs);
            $methodArgs = $input->getData();
            
            // PHP7 Version
            // $controller->$method(...$this->partialPathArray($this->controllerOffset+2));
            
            // Older PHP compatible version
            /** Maps an array of arguments derived from the URL into a method with a comma
             *  delimited list of parameters. Calls the method.
             *
             *  I.E. If the URL is:
             *  'www.mySite.com/MyController/FooBar/Param1/Param2/Param3'
             *
             *  And the FooBar method within MyController is defined as:
             *  public function FooBar($a, $b, $c) {}
             *
             *  $a will have the value 'Param1'
             *  $b will have the value 'Param2'  ... and so forth.
             */
            call_user_func_array(array($controller, $method), $methodArgs);
            
        }
        else {
            $this->error404();
        }
        
    } // end routeExec
    
    
    public function exists()
    {
        if (!isset($this->controllerPath)) {
            return false;
        }
        return true;
    }
    
    
    protected function getController()
    {
        
        // Load generic Cora parent class
        require_once('Cora.php');
        
        // If the config specifies an application specific class that extends Cora, load that.
        if ($this->config['cora_extension'] != '') {
            require_once(dirname(__FILE__).'/../extensions/'.$this->config['cora_extension'].'.php');
        }
        
        // Include the controller code.
        $cPath =    $this->config['pathToControllers'] .
                    $this->getPath($this->controllerPath) .
                    $this->config['controllersPrefix'] .
                    $this->controllerName .
                    $this->config['controllersPostfix'] .
                    '.php';

        require_once($cPath);
        
        // Return an instance of the controller.
        $class = $this->controllerNamespace.$this->getClassName($this->controllerName);
        //$class = $this->getClassName($this->controllerName);
        return new $class($this->container);
    }
    
    
    protected function getMethod()
    {
        
        $method = '';
        
        // Figure out method to be called, or use default.
        if (isset($this->path[$this->controllerOffset+1])) {
            $method = $this->path[$this->controllerOffset+1];
            if ($method == '') {
                $method = $this->config['default_method'];
            }
        }
        else {
            $method = $this->config['default_method'];
        }
        
        // RESTful routing:
        // Modify method routed to if request is not of type GET.
        if ($this->config['enable_RESTful']) {

            $httpMethod = $_SERVER['REQUEST_METHOD'];
            
            switch ($httpMethod) {
                case 'PUT':
                    $method = $method.'PUT';
                    break;
                case 'POST':
                    $method = $method.'POST';
                    break;
                case 'DELETE':
                    $method = $method.'DELETE';
                    break;
            }
        }
        
        return $method;
        
    } // END getMethod

    protected function getClassName($slug)
    {
        return str_replace(' ', '', ucwords(preg_replace('/-_/', ' ', $slug)));
    }
    
    /**
     *  The following two methods work off the URL path stored in $this->path.
     *  They are used to return part of that path in either Array or String form
     *  when asked to by the recursive calls of routeFind().
     */
    protected function partialPathString($offset, $length = null)
    {
        $partialPathArray = array_slice($this->path, $offset, $length);
        return implode('/', $partialPathArray);
    }
       
    protected function partialPathArray($offset, $length = null)
    {
        return array_slice($this->path, $offset, $length);
    }
    
    
    protected function error404()
    {
        
        // Grab load object.
        require('CoraError.php');
        $error = new \CoraError($this->container);
        $error->index();
        
    }
    
    protected function autoLoader($className)
    {
        $fullPath = $this->config['pathToModels'] .
                    $this->getPathBackslash($className) .
                    $this->config['modelsPrefix'] .
                    $this->getNameBackslash($className) .
                    $this->config['modelsPostfix'] .
                    '.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        include_once($fullPath);
    }
} // end Class
