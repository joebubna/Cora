<?php
namespace Cora;

class Framework {

    protected $config;
    protected $load;
    
    function __construct() {
    
        // Load and set config.
        require(dirname(__FILE__).'/../config/config.php');
        $this->config = $config;
        
    }
    
    protected function debug($message = '', $newLine = true) {
        if ($this->config['debug'] == true) {
            echo $message;
            if ($newLine) echo '<br>';
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

}