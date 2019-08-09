<?php
namespace Cora;

/**
 * THIS FILE IS DEPRECIATED
 */

class Autoload_old extends Framework
{
    public function __construct()
    {
        parent::__construct(); // Call parent constructor too so we don't lose functionality.
        
        // Register autoloader functions. Are called when an unloaded class is invoked.
        // These should only end up getting called if Composer doesn't work first.
        spl_autoload_register(array($this, 'autoLoader'));
        spl_autoload_register(array($this, 'coraLoader'));
        spl_autoload_register(array($this, 'coraLegacyLoader'));
    }
    
    /************************************************
     *  PSR-4 Autoloaders.
     ***********************************************/
    
    protected function autoLoader($className)
    {   
        $path = $this->getPathBackslash($className);
        $name = $this->getNameBackslash($className);
        $root = strToLower(self::getRootBackslash($className));
        $prefix = isset($this->config[$root.'Prefix']) ? $this->config[$root.'Prefix'] : '';
        $postfix = isset($this->config[$root.'Postfix']) ? $this->config[$root.'Postfix'] : '';
        
        $fullPath = $this->config['basedir'].$path.$prefix.$name.$postfix.'.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }


    protected function coraLoader($className)
    {
        $fullPath = dirname(__FILE__) . '/' .
                    $this->getPathBackslash($className, true) .
                    $this->getNameBackslash($className) .
                    '.php';
        echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
    
    protected function coraLegacyLoader($className)
    {
        $fullPath = dirname(__FILE__) . '/' .
                    //$this->getPathBackslash($className) .
                    $this->getNameBackslash($className) .
                    '.php';
        echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
}