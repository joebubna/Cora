<?php
namespace Cora;

class Container implements \Serializable, \IteratorAggregate, \Countable
{
    protected $parent;
    protected $signature;
    protected $singleton;
    protected $nextOffset;
    protected $returnClosure;
    
    public function __construct($parent = false, $data = false, $dataKey = false, $returnClosure = false)
    {   
        $this->parent = $parent;
        
        // Stores closures for creating a resource object.
        $this->signature = new \stdClass();
        
        // Stores actual resource objects. This is for when you don't want a new
        // object created each time, but want to reuse the same object.
        $this->singleton = new \stdClass();
        
        // When items are added to this collection without any valid key,
        // they will be added so that they can be accessed like $collection->0, $collection->2, etc.
        // This helps us keep track of current offset.
        $this->nextOffset = 0;
        
        // If returnClosure is set to true, then fetching resources through __get will not resolve
        // the closure automatically.
        $this->returnClosure = $returnClosure;
        
        // If data was passed in, then store it.
        if ($data != false && (is_array($data) || is_object($data))) {
            foreach ($data as $item) {
                $this->add($item, false, $dataKey);
            } 
        }
    }
    
    public function serialize()
    {
        return null;
    }
    
    public function unserialize($data)
    {
        unserialize($data);
    }
    
    public function getIterator() {
        $resources = (object) array_merge_recursive((array) $this->signature, (array) $this->singleton);
        return new \ArrayIterator($resources);
        //return new \ArrayIterator($this->signature);
    }
    
    public function count()
    {
        return $this->getIterator()->count();
    }
    
    
    public function __isset($name)
    {
        if ($this->make($name) !== null) {
            return true;
        }
        return false;
    }
    
    
    /**
     *  Returns a resource. If the resource is an object (from Singleton array)
     *  then just returns that object. If resource is defined in a Closure, 
     *  then executes the Closure and returns the resource within.
     *  
     *  $Container->name() creates the object via __call() below.
     */
    public function __get($name)
    {
        // Grab the resource which can be either a Closure or existing Object.
        $closureOrObject = $this->make($name);
        
        // Is Closure
        if ($closureOrObject instanceof \Closure) {
            if ($this->returnClosure == false) {
                // Execute the closure and create an object.
                return $closureOrObject($this);
            }
            else {
                // Return closure
                return $closureOrObject;
            }
        }
        
        // Is Object
        else {
            return $closureOrObject;
        }
    }
    
    
    public function get($name)
    {
        return $this->$name;
    }
    
    
    /**
     *  $Container->name = function();
     *  Allows assigning a closure to create a resource.
     */
    public function __set($name, $value)
    {   
        if ($value instanceof \Closure) {
            $this->signature->$name = $value;
        }
        else {
            $this->singleton->$name = $value;
        }
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
        
        if ($callback != null) {
            // Add container reference as first argument.
            array_unshift($arguments, $this);

            // Call the callback with the provided arguments.
            return call_user_func_array($callback, $arguments);
        }
        return null;
    }
    
    
    public function add($item, $key = false, $dataKey = false)
    {
        if ($item instanceof \Closure) {
            if ($key) {
                $this->singleton->$key = $item;
            }
            else {
                $offset = 'off'.$this->nextOffset;
                $this->nextOffset =+ 1;
                $this->singleton->$offset = $item;
            }
        }
        else if (is_object($item)) {
            if ($key) {
                $this->singleton->$key = $item;
            }
            else if ($dataKey && isset($item->$dataKey)) {
                $key = $item->$dataKey;
                $this->$key = $item;
            }
            else {
                $offset = 'off'.$this->nextOffset;
                $this->nextOffset += 1;
                $this->singleton->$offset = $item;
            }
        }
        else {
            if ($key) {
                $this->singleton->$key = $item;
            }
            else {
                $offset = 'off'.$this->nextOffset;
                $this->nextOffset += 1;
                $this->singleton->$offset = $item;
            }
        }
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
        $this->singleton->$name = $value($this);
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
    public function make($name, $container = false)
    {
        // Handle if recursive call or not.
        if (!$container) {
            $container = $this;
        }
        
        // If a single object is meant to be returned.
        if (isset($container->singleton->$name)) {
            return $container->singleton->$name;
        }
        
        // else look for a Closure.
        elseif (isset($container->signature->$name)) {
            return $container->signature->$name;
        }
        
        // Else check any parents.
        elseif ($container->parent) {
            return $container->make($name, $container->parent);
        }
        return null;
    }
    
    
    /**
     *  Used when a Container is returned from a container.
     *  I.E. If $container->events is itself another container,
     *  You want methods defined in the events container to have access
     *  to the declarations in the parent.
     */
    public function getSignatures()
    {
        return $this->signature;
    }
    
    public function getSingletons()
    {
        return $this->singleton;
    }
    
    public function returnClosure($bool)
    {
        $this->returnClosure = $bool;
    }
    
    public function getByValue($key, $value)
    {
        $collection = $this->getIterator();

        foreach($collection as $result) {
            if($result->$key == $value) {
                return $result;
            }
        }
        return null;
    }
}