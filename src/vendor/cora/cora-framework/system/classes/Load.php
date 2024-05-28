<?php
namespace Cora;

class Load extends Framework
{
    protected $neverOutput;
    public function __construct($neverOutput = false)
    {
        parent::__construct(); // Call parent constructor too so we don't lose functionality.
        
        $this->neverOutput = $neverOutput;
    }
    
    /**
     *  For echo'ing data in Views only if that data is set.
     */
    public function ifset(&$property)
    {
        if (isset($property))
            echo $property;
        else
            echo '';
    }

    /**
     *  For echo'ing data in Views that repeats.
     */
    public function repeat(&$property, $repeatTag, $classes = '', $outerTag = false, $outerClasses = '')
    {
        if (isset($property)) {
            $output = '';
            if ($outerTag) { $output .= '<'.$outerTag.' class="'.$outerClasses.'">'; }
            if (is_array($property)) {
                foreach ($property as $value) {
                    $output .= '<'.$repeatTag.' class="'.$classes.'">';
                    $output .= $value;
                    $output .= '</'.$repeatTag.'>';
                }
            }
            else {
                $output .= '<'.$repeatTag.' class="'.$classes.'">';
                $output .= $property;
                $output .= '</'.$repeatTag.'>';
            }
            if ($outerTag) { $output .= '</'.$outerTag.'>'; }
            echo $output;
        }
        else
            echo '';
    }

    /**
     *  Include specified model.
     *
     *  This is included for people that like to specifically load their classes.
     *  It's recommended you not use this and just let the autoloader handle
     *  model loading.
     */
    public function model($pathname) {
        $fullPath = $this->config['pathToModels'] .
                    $this->getPath($pathname) .
                    $this->config['modelsPrefix'] .
                    $this->getName($pathname) .
                    $this->config['modelsPostfix'] .
                    '.php';
        include_once($fullPath);
    }


    /**
     *  Include specified library. - Depreciated. Now let's autoloading handle the file include.
     *
     *  if a reference to the calling class is passed in, then the specified library
     *  will be invoked and a reference passed back to the calling class.
     *
     *  If $exposeToView is set to true along with a reference to the calling class,
     *  then the library will be loaded into the calling controller's Data field
     *  for use within a View file.
     */
    public function library($pathname, &$caller = false, $exposeToView = false) {

        $name = $this->getName($pathname);
        $path = $this->getPath($pathname);
        
        // If a reference to the calling object was passed, set an instance of
        // the library as one of its members.
        if ($caller) {
            
            // If no namespace is given, default to Cora namespace.
            if ($this->getPathBackslash($pathname) == '') {
                $lib = '\\Cora\\'.$name;
            }
            else {
                $lib = $pathname;
            }
            
            $libObj = new $lib($caller);

            // Set library to be available within a class via "$this->$libraryName"
            $caller->$name = $libObj;

            // Set library to also be available via "$this->load->$libraryName"
            // This is so this library will be available within View files as $libraryName.
            if ($exposeToView)
                $caller->setData($name, $libObj);
        }

    }


    /**
     *  Load view OR return view depending on 2nd parameter.
     */
    public function view($pathname = '', $data = false, $return = false) {

        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $value) {
                $$key = $value;
            }
        }

        // If no pathname specified, grab template name.
        if ($pathname == '') {
            $pathname = $this->config['template'];
        }

        $fullPath = $this->config['pathToViews'] .
                    $this->getPath($pathname);
        $path = $this->getPath($pathname);

        $this->debug('Full Path: '.$fullPath);

        $fileName = $this->config['viewsPrefix'] .
                    $this->getName($pathname) .
                    $this->config['viewsPostfix'] .
                    '.php';


        // Determine full filepath to View
        //$filePath = $fullPath . $fileName;

        $filePath = $this->_getFilePath($pathname, $fileName);

        // Debug
        $this->debug('');
        $this->debug( 'Searching for View: ');
        $this->debug( 'View Name: ' . $this->getName($pathname) );
        $this->debug( 'View Path: ' . $this->getPath($pathname) );
        $this->debug( 'File Path: ' . $filePath);
        $this->debug('');

        // Either return the view for storage in a variable, or output to browser.
        if ($return || $this->neverOutput) {
            ob_start();
            $inc = include($filePath);

            // If in dev mode and the include failed, throw an exception.
            if ($inc == false && $this->config['mode'] == 'development') { 
                throw new \Exception("Can't find file '$fileName' using path '$filePath'"); 
            }
            return ob_get_clean();
        }
        else {
            $inc = include($filePath);

            // If in dev mode and the include failed, throw an exception.
            if ($inc == false && $this->config['mode'] == 'development') { 
                throw new \Exception("Can't find file '$fileName' using path '$filePath'"); 
            }
        }
    }
    
    protected function _getFilePath($pathname, $fileName)
    {
        $path_steps = explode('/', $this->getPath($pathname));
        do {
            $path = implode('/', $path_steps);
            if (file_exists($this->config['pathToViews'] . $this->getPath($path) . $fileName)) {
                return $this->config['pathToViews'] . $this->getPath($path) . $fileName;
            }
            array_pop($path_steps);
        } while (count($path_steps) > 0);
    }
} // end class
