<?php
namespace Cora;
/**
*   Has dependency on \Cora\AbstractFactory class.
*/
class Container extends Collection
{
  public function __construct($parent = false, $data = false, $dataKey = false, $returnClosure = false)
  {
    parent::__construct($data, $dataKey, $parent, $returnClosure);
  }

  public function getFactory($class, $closure = false)
  {
    if ($closure) {
      return new AbstractFactory($this, $closure);
    }
    return new AbstractFactory($this, $this->find($class));
  }

  /**
   *  Finds a resource. 
   *  If given a numeric value, will grab that offset from the master resource 
   *  variable $this->content. If given a string as a name, will look for matching singletons 
   *  first, then matching closures second. If given a string, and no match is found, it will 
   *  also look in any parent container. 
   *
   *  @param name An int or string that denotes a resource. 
   *  @param container A parent container. 
   *  @return A resource or NULL.
   */
  public function find($name, $container = false, $exceptionOnNoMatch = false)
  {
    // Handle if recursive call or not.
    if (!$container) {
      $container = $this;
    }

    // Do any conversions on the resource name passed in, then check if numeric. 
    // I.E. "off2" will get returned as simply numeric 2.
    $name = $this->getName($name);
    if (is_numeric($name)) {
      return $this->fetchOffset($name);
    }

    // If a single object is meant to be returned.
    if (isset($container->singleton->$name)) {
      return $container->singleton->$name;
    }

    // else look for a Closure.
    elseif (isset($container->signature->$name)) {
      return $container->signature->$name;
    }

    // else check if the name given is a file name and a di_config function exists for the file.
    elseif (method_exists($name, 'di_config')) {
      return function() use ($name) {
        $args = func_get_args();
        return call_user_func_array(array($name, 'di_config'), $args);
      };
    }

    // Else check any parents.
    elseif ($container->parent) {
      return $container->find($name, $container->parent);
    }

    if ($exceptionOnNoMatch) {
      return new \Exception("No such resource ('$name') exists within this collection");
    }
    return null;
  }
 }