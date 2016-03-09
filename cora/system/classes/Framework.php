<?php
namespace Cora;

class Framework {

    protected $config;
    // protected $load;
    
    function __construct() {
    
        // Load and set config.
        require(dirname(__FILE__).'/../config/config.php');
        include(dirname(__FILE__).'/../../config/config.php');
        $this->config = $config;
        
    }
    
    protected function debug($message = '', $newLine = true) {
        if ($this->config['debug'] == true) {
            
            // Start hiding HTML comment
            if ($this->config['debugHide'] == true) {
                echo '<!-- ';
            }
            
            // Actual debug message.
            echo $message;
            
            // If hiding output, do /n to form newline, otherwise use <br>
            if ($newLine) {
                if ($this->config['debugHide']) {
                    echo "\n";
                }
                else {
                    echo "<br>";
                }
            }
            
            // End if hiding HTML comment.
            if ($this->config['debugHide'] == true) {
                echo '-->';
            }
        }
    }
    
    protected function debugArray($arr) {
        if ($this->config['debug'] == true) {
            echo '<pre>';
            print_r($arr);
            echo '</pre>';
        }
    }
    
    
    /**
     *  Get the 'fileName' out of 'folder/folder/fileName.php
     */
    protected function getName($pathname) {
        $arr = explode('/', $pathname);
        return $arr[count($arr)-1];
        
    }
    
    /**
     *  Get the 'fileName' out of 'folder\folder\fileName.php
     */
    protected function getNameBackslash($pathname) {
        $arr = explode('\\', $pathname);
        return $arr[count($arr)-1];
        
    }
    
    /**
     *  Get the 'folder/folder' out of 'folder/folder/fileName.php
     */
    protected function getPath($pathname) {
        $arr = explode('/', $pathname);
        $partialPathArray = array_slice($arr, 0, count($arr)-1);
        $path = implode('/', $partialPathArray);
        
        // If path isn't blank, then add ending slash to it.
        if ($path != '')
            $path = $path . '/';
        
        return $path;
    }
    
    /**
     *  Get the 'folder/folder' out of 'folder\folder\fileName.php
     */
    protected function getPathBackslash($pathname) {
        $arr = explode('\\', $pathname);
        $partialPathArray = array_slice($arr, 0, count($arr)-1);
        array_walk($partialPathArray, array($this, '_lcfirst'));
        $path = implode('/', $partialPathArray);
        
        // If path isn't blank, then add ending slash to it.
        if ($path != '')
            $path = $path . '/';
        
        return $path;
    }
    
    private function _lcfirst(&$str, $i)
    {
        $str = lcfirst($str);
    }

}