<?php
namespace Cora;

class Container implements \Serializable, \IteratorAggregate, \Countable, \ArrayAccess
{
    // If this Container has a parent, hold a reference to it here.
    protected $parent;

    // Closure resources.
    protected $signature;

    // Non-closure resources. The reason this is stored separate from Signatures is that you may have a resource
    // that you want to remain in closure form until needed, then store the created resource so subsequent calls
    // return the Singleton version.
    protected $singleton;

    // Combined contents. This stores the combined resources of both $signature and $singleton objects.
    // When a resource is added or removed, this will get recalculated.
    protected $content;

    // If the contents of this container get modified, set to true so that any subsequent calls to iterate over
    // or sort the contents recalculate what's in the $content data member.
    protected $contentModified = false;

    // For tracking the next open offset of the form "off0", "off1"... "offN"
    protected $nextOffset;
    protected $size;

    // Normally closures are resolved when the resource is asked for. If you want the actual Closure returned,
    // set this to true.
    protected $returnClosure;

    // Sort direction and key
    protected $sortDirection = false;
    protected $sortKey = false;



    public function __construct($parent = false, $data = false, $dataKey = false, $returnClosure = false)
    {
        $this->parent = $parent;

        // Stores closures for creating a resource object.
        $this->signature = new \stdClass();

        // Stores actual resource objects or primitives. Anything that isn't a Closure will be stored in here.
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
                $this->add($item, false, $dataKey, true);
            }
        }
        $this->contentModified = true;
    }

    public function fetchOffset($num)
    {
        $it = $this->getIterator();
        $i = 0;
        while ($i < $num) {
            $it->next();
            $i++;
        }
        return $it->current();
    }

    public function fetchOffsetKey($num)
    {
        $it = $this->getIterator();
        $i = 0;
        while ($i < $num) {
            $it->next();
            $i++;
        }
        return $it->key();
    }

    public function count($recursivelyIncludeParents = false, $recount = false)
    {
        if ($recount) {
            return $this->getIterator()->count();
        }
        return $this->size;
    }


    public function __isset($name)
    {
        if ($this->find($name) !== false) {
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
        $closureOrObject = $this->find($name);

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

    /**
     *  Finds a resource. In the case of singletons or explicitly passed in objects,
     *  this returns that single instance of the object.
     *  In the default case it will return a closure for creating an object.
     */
    public function find($name, $container = false)
    {
        // Handle if recursive call or not.
        if (!$container) {
            $container = $this;
        }

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

        // Else check any parents.
        elseif ($container->parent) {
            return $container->find($name, $container->parent);
        }
        return false;
    }


    /**
     *  $Container->name = function();
     *  Allows assigning a closure to create a resource.
     */
    public function __set($name, $value)
    {
        // If this resource was not already set, increase size variable.
        if (!$this->__isset($name)) {
            $this->size += 1;
        }
        if ($value instanceof \Closure) {
            $this->signature->$name = $value;
        }
        else {
            $this->singleton->$name = $value;
        }
        $this->contentModified = true;
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
        $callback = call_user_func_array(array($this, 'find'), array($name));

        if ($callback != false) {
            // Add container reference as first argument.
            array_unshift($arguments, $this);

            // Call the callback with the provided arguments.
            return call_user_func_array($callback, $arguments);
        }
        return false;
    }


    public function merge($data, $key = false, $dataKey = false)
    {
        if ($data != false && (is_array($data) || is_object($data))) {
            foreach ($data as $item) {
                $this->add($item, $key, $dataKey, true);
            }
        }
        else {
            $this->add($data, $key, $dataKey);
        }
        $this->contentModified = true;
    }


    public function add($item, $key = false, $dataKey = false)
    {
        // If this resource was not already set, increase size variable.
        if (!$key || !$this->__isset($key)) {
            $this->size += 1;
        }
        
        if (is_object($item)) {
            if ($key) {
                $this->singleton->$key = $item;
            }
            else if ($dataKey && isset($item->$dataKey)) {
                $key = $item->$dataKey;
                $this->singleton->$key = $item;
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
        $this->contentModified = true;
    }


    /**
     *  Remove a resource.
     */
    public function delete($name)
    {
        // Figure out the key of the object we want to delete.
        // (if numeric value was passed in, turn that into actual key)
        $resourceKey = false;
        if (is_numeric($name)) {
            $resourceKey = $this->fetchOffsetKey($name);
        }
        else {
            $resourceKey = $name;
        }

        // Only mark the content as modified and change count if the delete call found 
        // a resource to remove.
        if ($this->processDelete($resourceKey)) {
            $this->contentModified = true;
            $this->size -= 1;
        }
    }
    public function remove($name)
    {
        $this->delete($name);
    }

    public function processDelete($name, $container = false)
    {
        // Handle if recursive call or not.
        if (!$container) {
            $container = $this;
        }

        // If a single object is meant to be returned.
        if (isset($container->singleton->$name)) {
            unset($container->singleton->$name);
            return true;
        }

        // else look for a Closure.
        elseif (isset($container->signature->$name)) {
            unset($container->singleton->$name);
            return true;
        }

        // Else check any parents.
        elseif ($container->parent) {
            return $container->processDelete($name, $container->parent);
        }
        return false;
    }


    /**
     *  Rather than store the closure for creating an object,
     *  Create the object and store an instance of it.
     *  All calls for that resource will return the created object.
     */
    public function singleton($name, $value)
    {
        $this->singleton->$name = $value($this);
        $this->contentModified = true;
    }


    public function unsetSingleton($name)
    {
        $this->singleton->$name = false;
        $this->contentModified = true;
    }


    /**
     *  Similar to Singletons, but instead of giving a closure for creating an object,
     *  you just give an object itself.
     */
    public function setInstance($name, $object)
    {
        $this->singleton->$name = $object;
        $this->contentModified = true;
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


    public function sumByKey($key)
    {
        $collection = $this->getIterator();
        $sum = 0;
        foreach ($collection as $result) {
            if (isset($result->$key)) {
                $sum += $result->$key;
            }
        }
        return $sum;
    }

    public function sort($key, $dir = 'desc')
    {
        if (!$this->content || $this->contentModified) {
            $this->generateContent();
        }
        $collection = (array) $this->content;
        $this->sortDirection = $dir;
        $this->sortKey = $key;
        $this->mergesort($collection, array($this, 'compare'));
        $this->content = (object) $collection;
        return $this;
    }

    protected function compare($a, $b)
    {
        $key = $this->sortKey;
        $aValue = $this->getValue($a, $key);
        $bValue = $this->getValue($b, $key);

        if ($aValue == $bValue) {
            return 0;
        }
        if (strtolower($this->sortDirection) == 'desc') {
            return ($aValue < $bValue) ? -1 : 1;
        }
        else {
            return ($aValue < $bValue) ? 1 : -1;
        }
    }

    protected function getValue($data, $key = false)
    {
        $returnValue = $data;
        if (is_object($data)) {
            $returnValue = $data->$key;
        }
        else if (is_array($data)) {
            $returnValue = $data[$key];
        }
        return $returnValue;
    }


    

    /**
     *  Returns the FIRST result with a matching key=>value.
     *  If no match is found, then returns false.
     */
    public function getByValue($key, $value)
    {
        $collection = $this->getIterator();

        foreach($collection as $result) {
            if($result->$key == $value) {
                return $result;
            }
        }
        return false;
    }

    /**
     *  Returns a SUBSET of the results in the form of an array.
     *  If no matching subset exists, returns an empty array.
     */
    public function where($key, $desiredValue, $op = "==")
    {
        $collection = $this->getIterator();
        $subset = [];

        foreach($collection as $result) {
            $realValue = $result->$key;
            if($op == '==' && $realValue == $desiredValue) {
                $subset[] = $result;
            }
            else if($op == '>=' && $realValue >= $desiredValue) {
                $subset[] = $result;
            }
            else if($op == '<=' && $realValue <= $desiredValue) {
                $subset[] = $result;
            }
            else if($op == '>' && $realValue > $desiredValue) {
                $subset[] = $result;
            }
            else if($op == '<' && $realValue < $desiredValue) {
                $subset[] = $result;
            }
            else if($op == '===' && $realValue === $desiredValue) {
                $subset[] = $result;
            }
            else if($op == '!=' && $realValue != $desiredValue) {
                $subset[] = $result;
            }
        }
        return $subset;
    }

    ////////////////////////////////////////////////////////////////////////
    //  REQUIRED BY PSR-11.
    //  https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md
    //
    //  Note that I'm not fully implementing the spec as I don't want to throw their exceptions.
    ////////////////////////////////////////////////////////////////////////
    
    /**
     *  Alias of magic method __get()
     */
    public function get($name)
    {
        return $this->$name;
    }

    /**
     *  Alias of magic method __isset()
     */
    public function has($name)
    {
        return isset($this->$name);
    }

    ////////////////////////////////////////////////////////////////////////
    //  REQUIRED BY IteratorAggregate INTERFACE
    ////////////////////////////////////////////////////////////////////////
    
    /**
     *  Merges the $signature and $singleton resources together into a single result stored in $content.
     *
     *  @return null
     */
    public function getIterator() {
        if (!$this->content || $this->contentModified) {
            $this->generateContent();
        }
        return new \ArrayIterator($this->content);
    }


    ////////////////////////////////////////////////////////////////////////
    //  REQUIRED BY ArrayAccess INTERFACE
    ////////////////////////////////////////////////////////////////////////

    /**
     *  Checks if an offset is set. An offset can be a numeric number or key name (string).
     *
     *  @return bool
     */
    public function offsetExists($offset)
    {
        if ($this->get($offset)) {
            return true;
        }
        return false;
    }

    public function offsetGet($offset) 
    {
        return $this->$offset;
    }

    public function offsetSet ($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }


    ////////////////////////////////////////////////////////////////////////
    //  REQUIRED BY PHPUNIT (because it tries to serialize containers)
    ////////////////////////////////////////////////////////////////////////
    public function serialize()
    {
        return null;
    }

    public function unserialize($data)
    {
        unserialize($data);
    }


    ////////////////////////////////////////////////////////////////////////
    //  Non-public Methods
    ////////////////////////////////////////////////////////////////////////

    /**
     *  Merges the $signature and $singleton resources together into a single result stored in $content.
     *
     *  @return null
     */
    protected function generateContent()
    {
        $this->content = (object) array_merge_recursive((array) $this->signature, (array) $this->singleton);
        $this->contentModified = false;
    }


    /**
     *  A stable implementation of Mergesort (aka Stable-sort)
     */
    protected function mergesort(&$array, $cmp_function) {

        // Exit right away if only zero or one item.
        if(count($array) < 2) {
            return true;
        }

        // Cut results in half.
        $halfway = count($array) / 2;
        $leftArray = array_slice($array, 0, $halfway, true);
        $rightArray = array_slice($array, $halfway, null, true);

        // Recursively call sort on left and right pieces
        $this->mergesort($leftArray, $cmp_function);
        $this->mergesort($rightArray, $cmp_function);

        // Check if the last element of the first array is less than the first element of 2nd.
        // If so, we are done. Just put the two arrays together for final result.
        if(call_user_func($cmp_function, end($leftArray), reset($rightArray)) < 1) {
            $array = $leftArray + $rightArray;
            return true;
        }

        // Set result array to blank. Set pointers to beginning of pieces.
        $array = array();
        reset($leftArray);
        reset($rightArray);

        // While looking at the current element in each array...
        while(current($leftArray) && current($rightArray)) {

            // Add the lowest element between the current element in the left and right arrays to the result.
            // Then advance to the next item on that side.
            if(call_user_func($cmp_function, current($leftArray), current($rightArray)) < 1) {
                $array[key($leftArray)] = current($leftArray);
                next($leftArray);
            } else {
                $array[key($rightArray)] = current($rightArray);
                next($rightArray);
            }
        }

        // After doing the left and right comparisons above, you may hit the end of the left array
        // before hitting the end of the right (or vice-versa). We need to make sure these left-over
        // elements get added to our results.
        while(current($leftArray)) {
            $array[key($leftArray)] = current($leftArray);
            next($leftArray);
        }
        while(current($rightArray)) {
            $array[key($rightArray)] = current($rightArray);
            next($rightArray);
        }
        return true;
    }
}
