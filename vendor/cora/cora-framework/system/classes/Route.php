<?PHP
namespace Cora;

class Route extends Framework
{

    protected $container;           // VARIES   - For passing data to controllers.
    protected $pathString;          // STRING   - URL excluding siteURL. mysite.com/FROM/HERE/ONWARD/
    protected $path;                // ARRAY    - Array version of pathString. URL pieces after baseURL.
    protected $controllerPath;      // STRING   - filepath to controller .php file.
    protected $controllerOffset;    // INT      - The offset within the path array of the controller.
    protected $controllerName;      // STRING
    protected $controllerNamespace; // STRING
    protected $controller;          // OBJECT
    protected $method;              // STRING
    protected $httpMethod;          // STRING
    protected $collectionIDgiven;   // BOOL     - If the URL is of form /articles/107 this is set to true.
    protected $collectionID;        // INT      - The ID if a collection ID is specified.

    protected $paths;               // ARRAY    - Holds custom defined paths.
    protected $pathRESTful = true;  // BOOL     - For enabling/disabling RESTful on individual custom routes.


    /**
     *  Sets up fixed resources.
     */
    public function __construct($container = false)
    {
        parent::__construct(); // Call parent constructor too so we don't lose functionality.

        // For site specific data. This will be passed to Cora's controllers when they
        // are invoked in the routeExec() method.
        $this->container = $container;

        // Assign custom paths
        $paths = [];
        if (file_exists($this->config['basedir'].'cora/app/Paths.php')) {
            include($this->config['basedir'].'cora/app/Paths.php');
        }
        $this->paths = $paths;

        // Namespacing Defaults
        $this->controllerNamespace = 'Controllers\\';

    }

    /**
     *  Processes a route (which in turn sets the PATH, checks custom and default routes), 
     *  then executes the route or lack thereof.
     */
    public function run($uri = false, $method = false)
    {
        // Setup and check for route.
        $this->routeProcess($uri, $method);

        // Debug
        $this->debug('Route: ' . $this->pathString);

        // Execute route (will load 404 if not exists)
        $this->routeExec();
    }


    /**
     *  Checks if a custom or default route exists for a URL and HTTP method. 
     *  By default will grab URL and HTTP method from server variables, 
     *  but can be passed in as arguments.
     */
    public function routeProcess($uri = false, $method = false)
    {
        // Set Request type.
        if (!$method) $method = $_SERVER['REQUEST_METHOD'];
        $this->httpMethod = $method;

        // Set PATH info.
        if (!$uri) $uri = $_SERVER['REQUEST_URI'];
        $this->setPath($uri);

        if (!$this->customFind()) {
            if ($this->config['automatic_routing']) {
                $this->routeFind();
            }
        }
    }


    /**
     *  Checks if a custom path exists for the current URL.
     */
    public function customFind()
    {
        // Setup
        $url = '//'.$this->pathString;
        $matchFound = false;
        $templateRegex = "/\{\w+}/";
        $pathsExecuted = [];


        $numPaths = count($this->paths);
        for($i=0; $i<$numPaths; $i++) {
            $path = $this->paths->get($i);
            
            // Only process this custom route if it hasn't been executed before.
            if (!in_array($path->url, $pathsExecuted)) {
                ///////////////////////////////////////////////
                // Grab path URL template variables
                ///////////////////////////////////////////////
                /**
                *  Create an array of path variables from custom path URL definition.
                *  I.E. users/{someVariable}/{anotherVariable}
                */
                $templateVariables = [];
                preg_match_all($templateRegex, $path->url, $templateVariables);

                ///////////////////////////////////////////////
                // Replacing templates variables with regex
                ///////////////////////////////////////////////
                /**
                *  This is for replacing the bracket variables in the custom route with a regular expression string.
                *  If no custom definition for the variable was set in the variable definitions section, then the variable
                *  will default to alpha-numeric with underscore and dash.
                *  INPUT   = users/action-subaction/23
                *  OUTPUT  = users\/([a-zA-Z0-9-_]+)\-([a-zA-Z0-9-_]+)\/([0-9]+)
                */
                $urlRegex = preg_quote($path->url, '/');
                foreach ($templateVariables[0] as $key => $placeholder) {
                    if (isset($path->def[$placeholder])) {
                        $urlRegex = str_replace(preg_quote($placeholder), '('.$path->def[$placeholder].')', $urlRegex);
                    }
                    else if ($placeholder == '{anything}') {
                        $urlRegex = str_replace(preg_quote($placeholder), '(.+)', $urlRegex);
                    }
                    else {
                        $urlRegex = str_replace(preg_quote($placeholder), '([a-zA-Z0-9_]+)', $urlRegex);
                    }
                }

                ///////////////////////////////////////////////
                // Check for regex match against URL given
                ///////////////////////////////////////////////
                /**
                *   This takes the current URL and checks if it matches the custom route we are currently looking at.
                *   If there's a match, then it marks that we found a valid custom path.
                *   $urlData will get populated with the matching variable values from the URL. This works because
                *   all the regexes from the above section are within parenthesis.
                *   With the following regex: "users/([a-zA-Z0-9_]+)/([a-zA-Z0-9_]+)"
                *   The match for the first set of parenthesis will get placed in $urlData[1], the 2nd set in $urlData[2], etc.
                *
                *   See below for an example:
                *   if URL              = articles/grab-all/23
                *   and custom route    = articles/{action}-{modifier}/{id}
                *   $urlData[1] will be 'grab', $urlData[2] will be 'all', and $urlData[3] will be 23.
                */
                $urlData = [];
                $finalRoute = $path->route;
                if (preg_match("/$urlRegex/", $url, $urlData)) {
                    $matchFound = true;
                    
                    // A match was found, so at this point $urlData contains the matching pieces from the regex.
                    // With the first variable match being in $urlData[1]. Offset 0 is useless, so let's unset it so it
                    // does screw up our count.
                    unset($urlData[0]);
                    for($i=1; $i<=count($urlData); $i++) {
                        // For each REAL variable value from our URL, let's replace any references to that variable in our
                        // route with the actual values.
                        // Example:
                        //                     Path URL = "users/{action}-{modifier}"
                        //    Actual URL in web browser = "users/fetch-all"
                        //                   Path Route = "users/{action}/{modifier}"
                        //  Final Route after this loop = "users/fetch/all"
                        $pathVar = $templateVariables[0][$i-1]; // E.g. {action}. $templateVariables starts at offset 0.
                        $pathVarValue = $urlData[$i];
                        $finalRoute = str_replace($pathVar, $pathVarValue, $finalRoute);
                    }
                }


                ///////////////////////////////////////////////
                // Handle Path match
                ///////////////////////////////////////////////
                /**
                *  If this iteration of the loop found a match to a custom path,
                *  Then execute that Path's pre execution function and if that returns true,
                *  then set our new Route to be the one defined in the custom Path.
                */
                if ($matchFound) {
                    // If the path accepts all http method types or the current request type is listed as being accepted.
                    if (
                        stripos($path->actions, 'all') !== false ||
                        stripos($path->actions, $this->httpMethod) !== false
                    ) {
                        // Run preExec function for this path.
                        if (!$path->preExecCheck($this->container)) {
                            $this->error('403');
                            exit;
                        }
                        
                        // If an internal route was defined for this path, set that route.
                        if ($path->route) {
                            $this->setPath($finalRoute);
                        }

                        // If path is passive, set the path route to be the URL (if necessary) and if so, reset custom paths 
                        // iteration.
                        if ($path->passive == true) {
                            if ($path->route) {
                                $url = $finalRoute;
                                $i = 0;
                                
                                // Add path to list of executed paths. 
                                $pathsExecuted[] = $path->url;
                            }
                        }

                        // If the path is isn't passive, try to find matching route.
                        else {
                            $this->pathRESTful = $path->RESTful;
                            $this->routeFind();
                            return true;
                        }
                    }
                }
            } // end if not in executed paths list
        } // end for loop
        return false;
    }


    /**
     *  Given a URL, sets needed PATH information.
     *  This must be executed before routeFind as it sets the needed pathString var.
     *
     *  NOTE: $_SERVER['QUERY_STRING'] is set by the server and may not be available
     *  depending on the server being used.
     */
    public function setPath($url)
    {
        // Removes any GET data from the url.
        // Makes 'mysite.com/websites/build/?somevalue=test' INTO '/websites/build'
        $cleanURI = str_replace('?'.$_SERVER['QUERY_STRING'], '', $url);

        // Removes the 'mysite.com' from 'mysite.com/controller/method/id/'
        // Resulting pathString will be 'controller/method/id/'
        $this->pathString = explode($this->config['site_url'], $cleanURI, 2)[1];

        // If config option to make url lowercase is true
        if ($this->config['lowercase_url']) {
            $this->pathString = strtolower($this->pathString);
        }

        // Setup Path array
        $this->path = explode('/', $this->pathString);
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
            $controller_path = $curPath[0];
        }
        // Else grab the default controller.
        else {
            $controller_path = $this->config['default_controller'];
        }
            $controller = $this->getClassName($controller_path);
            $controllerFileName =   $this->config['controllersPrefix'] .
                                    $controller .
                                    $this->config['controllersPostfix'] .
                                    '.php';

        // Set working filepath.
        $dirpath = $this->config['pathToControllers'].$basePath.$controller_path;
        $filepath = $this->config['pathToControllers'].$basePath.$controllerFileName;

        // Debug
        $this->debug('Searching for: ' . $filepath);

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

            if (!is_callable(array($controllerInstance, $method))) {
                // Update namespace
                $this->controllerNamespace .= $this->controllerName.'\\';

                //  Controller->Method combo doesn't exist, so undo previously set data.
                $this->controllerPath = null;
                $this->controllerOffset = null;
                $this->controllerName = null;

                // Debug
                $this->debug('Not matching method within controller. Continuing search.');

            } else {
                // Valid controller+method combination found, so stop searching further.
                $this->controller   = $controllerInstance;
                $this->method       = $method;
                return true;
            }
        }
        else {
            // Update namespace
            $this->controllerNamespace .= $controller.'\\';
        }

        // Else check if there's a matching directory we can look through.
        if (is_dir($dirpath)) {
            $this->debug('Directory Found: ' . $basePath . $controller);

            // Recursive call
            $this->routeFind($basePath . $controller_path . '/', $offset+1);
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
            $this->error('404');
            exit;
        }

        // Grab method arguments from the URL.
        if ($this->collectionIDGiven) {
            $methodArgs = $this->partialPathArray($this->controllerOffset+1, 1);
        }
        else {
            $methodArgs = $this->partialPathArray($this->controllerOffset+2);
        }

        // Remove the last item from arguments if empty.
        $lastItem = count($methodArgs)-1;
        if (isset($methodArgs[$lastItem]) && $methodArgs[$lastItem] == '') {
            array_pop($methodArgs);
        }

        // If no arguments are set then make empty array.
        if (empty($methodArgs) || $methodArgs[0] == '') {
            $methodArgs = array();
        }
        else {
            // Sanitize arguments.
            $input = new Input($methodArgs);
            $methodArgs = $input->getData();
        }

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
        call_user_func_array(array($this->controller, $this->method), $methodArgs);

    } // end routeExec


    /**
     *  Checks if routeFind found a path to a controller. Can optionally check this
     *  before calling routeExec.
     *
     *  Note: This helps with integrating Cora into legacy applications.
     *  You can check if a matching controller was found in the directory you're putting
     *  Cora controllers in, and if not, then run legacy routing instead.
     */
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

        // Include the controller code.
        $cPath =    $this->config['pathToControllers'] .
                    $this->getPath($this->controllerPath) .
                    $this->config['controllersPrefix'] .
                    $this->controllerName .
                    $this->config['controllersPostfix'] .
                    '.php';

        if (file_exists($cPath)) {
            include_once($cPath);
        }
        // Return an instance of the controller.
        $class = $this->controllerNamespace.$this->getClassName($this->controllerName);

        return new $class($this->container);
    }


    protected function getMethod()
    {

        $method = '';
        $this->collectionIDGiven = false;
        $this->collectionID = false;

        // Figure out method to be called, or use default.
        if (isset($this->path[$this->controllerOffset+1])) {
            $method = $this->path[$this->controllerOffset+1];

            // If no method is specified or if the method spot is numeric (indicating
            // that an ID is being passed to a collection for RESTful routing)
            // Route to default method.
            if ($method == '') {
                $method = $this->config['default_method'];
            }
            else if (is_numeric($method)) {
                $this->collectionIDGiven = true;
                $this->collectionID = $method;
                $method = $this->config['default_method'];
            }
        }
        else {
            $method = $this->config['default_method'];
        }

        // RESTful routing:
        // Modify method routed to if request is not of type GET.
        if ($this->config['enable_RESTful'] && $this->pathRESTful) {

            $httpMethod = $_SERVER['REQUEST_METHOD'];

            if ($this->collectionIDGiven) {
                switch ($httpMethod) {
                    case 'GET':
                        $method = 'itemGET';
                        break;
                    case 'PUT':
                        $method = 'itemPUT';
                        break;
                    case 'POST':
                        $method = 'itemPOST';
                        break;
                    case 'DELETE':
                        $method = 'itemDELETE';
                        break;
                }
            }
            else {
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
        }

        return $method;

    } // END getMethod


    protected function getClassName($slug)
    {
        return str_replace(' ', '', ucwords(preg_replace('/[-_]/', ' ', $slug)));
    }

    /**
     *  The following two methods work off the URL path stored in $this->path.
     *  They are used to return part of that path in either Array or String form
     *  when asked to by the recursive calls of routeFind().
     */
    protected function partialPathString($offset, $length = null, $dataArray = false)
    {
        if ($dataArray == false) {
            $dataArray = $this->path;
        }
        $partialPathArray = array_slice($dataArray, $offset, $length);
        return implode('/', $partialPathArray);
    }

    protected function partialPathArray($offset, $length = null)
    {
        return array_slice($this->path, $offset, $length);
    }

    /**
     *  Sample Types:
     *  401 = Access Denied
     *  404 = Not Found
     */
    protected function error($type)
    {
        $filepath = $this->config['basedir'].'cora/app/Error.php';

        if (file_exists($filepath)) {
            $error = new \Cora\App\Error($this->container);
        }
        else {
            $error = new \Cora\Error($this->container);
        }
        $error->handle($type);
    }

} // end Class
