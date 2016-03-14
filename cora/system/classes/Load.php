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
     */
    public function library($pathname, &$caller = false) {
        
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
            $caller->$name = new $lib($caller);
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
        
        // Determine full filepath to View
        $fullPath = $this->config['pathToViews'] . 
                    $this->getPath($pathname) .
                    $this->config['viewsPrefix'] .
                    $this->getName($pathname) .
                    $this->config['viewsPostfix'] .
                    '.php';
        
        // Debug
        $this->debug('');
        $this->debug( 'Searching for View: ');
        $this->debug( 'View Name: ' . $this->getName($pathname) );
        $this->debug( 'View Path: ' . $this->getPath($pathname) );
        $this->debug('');
        
        // Either return the view for storage in a variable, or output to browser.
        if ($return) {
            ob_start();
            include($fullPath);
            return ob_get_clean();
        }
        else {
            include($fullPath);
        }
    }
    

}