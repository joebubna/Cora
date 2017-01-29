<?php
namespace Cora;

class Container implements \Serializable, \IteratorAggregate, \Countable
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

    // For tracking the next open offset of the form "off0", "off1"... "offN"
    protected $nextOffset;

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
                $this->add($item, false, $dataKey, true);
            }
        }
        $this->generateContent();
    }

    protected function generateContent()
    {
        $this->content = (object) array_merge_recursive((array) $this->signature, (array) $this->singleton);
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
        return new \ArrayIterator($this->content);
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
        if (is_numeric($name)) {
            return $this->fetchOffset($name);
        }
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
        $this->generateContent();
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


    public function merge($data, $key = false, $dataKey = false)
    {
        if ($data != false && (is_array($data) || is_object($data))) {
            foreach ($data as $item) {
                $this->add($item, $key, $dataKey, true);
            }
            $this->generateContent();
        }
        else {
            $this->add($data, $key, $dataKey);
        }
    }


    public function add($item, $key = false, $dataKey = false, $skipGenerate = false)
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

        if (!$skipGenerate) {
            $this->generateContent();
        }
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

        $this->processDelete($resourceKey);
        $this->generateContent();
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
        }

        // else look for a Closure.
        elseif (isset($container->signature->$name)) {
            unset($container->singleton->$name);
        }

        // Else check any parents.
        elseif ($container->parent) {
            return $container->processDelete($name, $container->parent);
        }
        return null;
    }


    /**
     *  Rather than store the closure for creating an object,
     *  Create the object and store an instance of it.
     *  All calls for that resource will return the created object.
     */
    public function singleton($name, $value)
    {
        $this->singleton->$name = $value($this);
        $this->generateContent();
    }


    public function unsetSingleton($name)
    {
        $this->singleton->$name = null;
        $this->generateContent();
    }


    /**
     *  Similar to Singletons, but instead of giving a closure for creating an object,
     *  you just give an object itself.
     */
    public function setInstance($name, $object)
    {
        $this->singleton->$name = $object;
        $this->generateContent();
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

    /**
     *  Returns the FIRST result with a matching key=>value.
     *  If no match is found, then returns NULL.
     */
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
}
