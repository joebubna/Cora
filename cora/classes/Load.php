<?php
namespace Cora;

class Load extends Framework {

    
    /**
     *  Include specified model.
     *  
     *  If $strict == true, then searches for given view EXACTLY as 
     *  stated from base Views directory. Ex: if $pathname == 'user/login'
     *  it will search for login.php in 'views/user/login.php' and fail
     *  the include if that file is not found.
     *
     *  If $strict == false, and $pathname == 'create' it will search for
     *  a file named create.php in a directory matching the controller's
     *  and then walk up the directory path until it reaches the base View
     *  directory.
     */
    public function model($pathname, $strict = false) {
        $fullPath = $this->config['pathToModels'] .
                    $this->getPath($pathname) .
                    $this->config['modelsPrefix'] .
                    $this->getName($pathname) .
                    $this->config['modelsPostfix'] .
                    '.php';
        include_once($fullPath);
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