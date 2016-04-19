<?php
namespace Cora;

class Load extends Framework 
{
    
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
     *  Include specified library.
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
        $fullPath = $this->config['pathToLibraries'] .
                    $path .
                    $this->config['librariesPrefix'] .
                    $name .
                    $this->config['librariesPostfix'] .
                    '.php';
        
        // If the file exists in the Libraries directory, load it.
        if (file_exists($fullPath)) {
            include_once($fullPath);
        }
        
        // Otherwise try and load it from the Cora system files.
        else {
            include_once($name.'.php');
        }
              
        // If a reference to the calling object was passed, set an instance of
        // the library as one of its members.
        if ($caller) {
            $lib = '\\Library\\'.$name;
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
        // $filePath = $fullPath . $fileName;

        $filePath = $this->_getFilePath($pathname, $fileName);

        // Debug
        $this->debug('');
        $this->debug( 'Searching for View: ');
        $this->debug( 'View Name: ' . $this->getName($pathname) );
        $this->debug( 'View Path: ' . $this->getPath($pathname) );
        $this->debug( 'File Path: ' . $filePath);
        $this->debug('');
        
        // Either return the view for storage in a variable, or output to browser.
        if ($return) {
            ob_start();
            include($filePath);
            return ob_get_clean();
        }
        else {
            include($filePath);
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