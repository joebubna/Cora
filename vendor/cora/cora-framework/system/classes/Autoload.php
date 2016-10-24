<?php
namespace Cora;

class Autoload extends Framework
{
    public function __construct()
    {
        parent::__construct(); // Call parent constructor too so we don't lose functionality.
        
        // Register autoloader functions. Are called when an unloaded class is invoked.
        // These should only end up getting called if Composer doesn't work first.
        spl_autoload_register(array($this, 'autoLoader'));
        spl_autoload_register(array($this, 'controllerLoader'));
        spl_autoload_register(array($this, 'coraExtensionLoader'));
        spl_autoload_register(array($this, 'coraLoader'));
        spl_autoload_register(array($this, 'libraryLoader'));
        spl_autoload_register(array($this, 'listenerLoader'));
        spl_autoload_register(array($this, 'eventLoader'));
    }
    
    /************************************************
     *  PSR-4 Autoloaders.
     ***********************************************/
    
    protected function autoLoader($className)
    {   
        $fullPath = $this->config['basedir'] .
                    $this->getPathBackslash($className) .
                    $this->config['modelsPrefix'] .
                    $this->getNameBackslash($className) .
                    $this->config['modelsPostfix'] .
                    '.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
    
    protected function controllerLoader($className)
    {   
        $fullPath = $this->config['basedir'] .
                    $this->getPathBackslash($className) .
                    $this->config['controllersPrefix'] .
                    $this->getNameBackslash($className) .
                    $this->config['controllersPostfix'] .
                    '.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
    
    protected function eventLoader($className)
    {
        $fullPath = $this->config['basedir'] .
                    $this->getPathBackslash($className) .
                    $this->config['eventsPrefix'] .
                    $this->getNameBackslash($className) .
                    $this->config['eventsPostfix'] .
                    '.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
    
    protected function listenerLoader($className)
    {
        $fullPath = $this->config['basedir'] .
                    $this->getPathBackslash($className) .
                    $this->config['listenerPrefix'] .
                    $this->getNameBackslash($className) .
                    $this->config['listenerPostfix'] .
                    '.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
    
    protected function coraLoader($className)
    {
        $fullPath = dirname(__FILE__) . '/' .
                    //$this->getPathBackslash($className) .
                    $this->getNameBackslash($className) .
                    '.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
    
    protected function coraExtensionLoader($className)
    {
        $fullPath = $this->config['basedir'] .
                    $this->getPathBackslash($className) .
                    $this->getNameBackslash($className) .
                    '.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
    
     protected function libraryLoader($className)
    {
        //$name = $this->getName($className);
        //$path = $this->getPath($className);
        $fullPath = $this->config['basedir'] .
                    $this->getPathBackslash($className) .
                    $this->config['librariesPrefix'] .
                    $this->getNameBackslash($className) .
                    $this->config['librariesPostfix'] .
                    '.php';
        
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        
         // If the file exists in the Libraries directory, load it.
        if (file_exists($fullPath)) {
            include_once($fullPath);
        }
    }
}