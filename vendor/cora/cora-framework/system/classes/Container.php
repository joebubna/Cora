<?php
namespace Cora;

class Container
{
    protected $signature;
    protected $singleton;
    
    public function __construct()
    {
        // Stores closures for creating a resource object.
        $this->signature = new \stdClass();
        
        // Stores actual resource objects. This is for when you don't want a new
        // object created each time, but want to reuse the same object.
        $this->singleton = new \stdClass();
    }
    
    /**
     *  $Container->name returns the closure for creating an object.
     *  $Container->name() creates the object via __call() below.
     */
    public function __get($name)
    {
        return $this->make($name);
    }
    
    
    /**
     *  $Container->name = function();
     *  Allows assigning a closure to create a resource.
     */
    public function __set($name, $value)
    {
        $this->signature->$name = $value;
    }
    
    /**
     *  Intercepts methods calls on this object.
     *  $Container->name(arg1, arg2)
     *  $name is passed to make() method to get callback for resource.
     *  The arguments are then passed onwards to the callback.
     */
    public function __call($name, $arguments)
    {   
        // Grab the callback for the specified name.
        $callback = call_user_func_array(array($this, 'make'), array($name));
        
        // Add container reference as first argument.
        array_unshift($arguments, $this);
        
        // Call the callback with the provided arguments.
        return call_user_func_array($callback, $arguments);
    }
    
    /**
     *  Remove a resource.
     */
    public function delete($name)
    {
        $this->signature->$name = null;
    }
     
    /**
     *  Rather than store the closure for creating an object,
     *  Create the object and store an instance of it.
     *  All calls for that resource will return the created object.
     */
    public function singleton($name, $value)
    {
        $this->singleton->$name = $value();
    }
    
    public function unsetSingleton($name)
    {
        $this->singleton->$name = null;
    }
    
    /**
     *  Similar to Singletons, but instead of giving a closure for creating an object,
     *  you just give an object itself.
     */
    public function setInstance($name, $object)
    {
        $this->singleton->$name = $object;
    }
    
    /**
     *  Makes a resource. In the case of singletons or explicitly passed in objects,
     *  this returns that single instance of the object.
     *  In the default case it will return a closure for creating an object.
     */
    public function make($name)
    {
        // If a single object is meant to be returned.
        if (isset($this->singleton->$name)) {
            return $this->singleton->$name;
        }
        else {
            return $this->signature->$name;
        }
    }
}