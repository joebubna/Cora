<?php
namespace Cora;

class Autoload
{
  protected $config;
  protected $debug;
  

  public function __construct($config = [], $debug = false)
  {        
    $this->config = $config;
    $this->debug = $debug;    
  }


  public function register()
  {
    // Register autoloader functions. Are called when an unloaded class is invoked.
    // These should only end up getting called if Composer doesn't work first.
    spl_autoload_register(array($this, 'autoLoader'));
  }


  
  /************************************************
   *  PSR-4 Autoloader(s).
   ***********************************************/
  
  protected function autoLoader($className)
  {   
    $path = $this->getPathBackslash($className);
    $name = $this->getNameBackslash($className);
    $root = strToLower($this->getRootBackslash($className));
    $prefix = isset($this->config[$root.'Prefix']) ? $this->config[$root.'Prefix']: '';
    $postfix = isset($this->config[$root.'Postfix']) ? $this->config[$root.'Postfix']: '';
    $basedir = isset($this->config['basedir']) ? $this->config['basedir'] : dirname(__FILE__).'/';
    
    $fullPath = $basedir.$path.$prefix.$name.$postfix.'.php';

    if ($this->debug) echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
    
    if (file_exists($fullPath)) {
      include($fullPath);
    }
  }



  /************************************************
   *  Utility methods
   ***********************************************/

  /**
   * Get the 'fileName' out of 'folder\folder\fileName
   * 
   * @return String
   */
  public function getNameBackslash($pathname) {
    $arr = explode('\\', $pathname);
    return $arr[count($arr)-1];
  }


  /**
   * Get the 'models' out of 'models\folder\fileName
   * 
   * @return String
   */
  public function getRootBackslash($pathname) {
      
    // Remove leading slash if necessary.
    if($pathname[0] == '\\') {
      $pathname = substr($pathname, 1);
    }

    // Create array from path
    $arr = explode('\\', $pathname);
    
    // If a pathname was given (as opposed to just a filename like "myFile.php")
    // Return the first part of it. Otherwise just return nothing for root.
    if (count($arr) > 1) {
      return $arr[0];
    }
    return '';
  }


  /**
   * Get the 'folder/folder' out of 'folder\folder\fileName
   * 
   * @return String
   */
  public function getPathBackslash($pathname, $removeBaseName = false) {
    $arr = explode('\\', $pathname);
    if ($removeBaseName) {
      $partialPathArray = array_slice($arr, 1, count($arr)-2);
    }
    else {
      $partialPathArray = array_slice($arr, 0, count($arr)-1);
    }
    array_walk($partialPathArray, array($this, '_lcfirst'));
    $path = implode('/', $partialPathArray);

    // If path isn't blank, then add ending slash to it.
    if ($path != '')
      $path = $path . '/';

    return $path;
  }


  /**
   * Lowercase the first letter of a string.
   * 
   * @return String
   */
  protected function _lcfirst(&$str, $i)
  {
    $str = lcfirst($str);
  }
}