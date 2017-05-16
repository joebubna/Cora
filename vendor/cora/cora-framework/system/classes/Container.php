<?php
namespace Cora;

class Container implements \Serializable, \IteratorAggregate, \Countable, \ArrayAccess
{
    ////////////////////////////////////////////////////////////////////////
    //  DATA MEMBERS
    ////////////////////////////////////////////////////////////////////////
    
    // If this Container has a parent, hold a reference to it here.
    protected $parent;

    // Closure resources.
    protected $signature;

    // If there's a resource that is defined as a closure but after that closure is executed, 
    // you want the result stored as a singleton and returned for any subsequence calls... 
    // then the named property needs to be set to boolean in this object so the Container knows to 
    // save the result as a singleton.
    protected $signaturesToSingletons;

    // Non-closure resources. The reason this is stored separate from Signatures is that you may have a resource
    // that you want to remain in closure form until needed, then store the created resource so subsequent calls
    // return the Singleton version.
    protected $singleton;

    // Combined contents. This stores the combined resources of both $signature and $singleton objects.
    // When a resource is added or removed, this will get recalculated.
    protected $content;
    protected $contentKeys;
    protected $size = 0;

    // If the contents of this container get modified, set to true so that any subsequent calls to iterate over
    // or sort the contents recalculate what's in the $content data member.
    protected $contentModified = false;

    // Normally closures are resolved when the resource is asked for. If you want the actual Closure returned,
    // set this to true.
    protected $returnClosure;

    // Sort direction and key
    protected $sortDirection = false;
    protected $sortKey = false;



    ////////////////////////////////////////////////////////////////////////
    //  MAGIC METHODS
    ////////////////////////////////////////////////////////////////////////

    /**
     *  Constructor 
     *  
     *  @param parent If this container/collection has a parent container. 
     *  @param data Data to be loaded into this collection in the form of an object or array. 
     *  @param dataKey A key or property on the data being passed in. Will be access $name.
     *  @param returnClosure Whether Closures stored should be returned as Closures or executed and result returned.
     */
    public function __construct($parent = false, $data = false, $dataKey = false, $returnClosure = false)
    {
        $this->parent = $parent;
        $this->signature = new \stdClass();
        $this->singleton = new \stdClass();
        $this->signaturesToSingletons = new \stdClass();;

        // If returnClosure is set to true, then fetching resources through __get will not resolve
        // the closure automatically.
        $this->returnClosure = $returnClosure;

        // If data was passed in, then store it.
        if ($data != false && (is_array($data) || $data instanceof \Traversable)) {
            foreach ($data as $item) {
                $this->add($item, false, $dataKey, true);
            }
        }
        $this->generateContent();
    }

    /**
     *  Determines if a resource exists. 
     *
     *  @param name The name of the resource sought. 
     *  @return Boolean
     */
    public function __isset($name)
    {
        if ($this->find($name) !== null) {
            return true;
        }
        return false;
    }


    /**
     *  Returns a resource. If the resource is an object (from Singleton array)
     *  then just returns that object. If resource is defined in a Closure,
     *  then normally executes the Closure and returns the resource within unless 
     *  returnClosure is true. 
     *
     *  @param name A name in the form of a string or numeric offset.
     *  @return Mixed A resource or null.
     */
    public function __get($name)
    {
        // Grab the resource.
        $resource = $this->find($name);

        // Is Closure
        if ($resource instanceof \Closure) {
            if ($this->returnClosure == false) {
                // Create a resource from the closure.
                $item = $resource($this);
               
                // If the closure is marked as needing to be saved as a singleton, store result. 
                if (isset($this->signaturesToSingletons->$name) and $this->signaturesToSingletons->$name) {
                    $this->$name = $item;
                    $this->signaturesToSingletons = false;
                }
               
                // Return the resource
                return $item;
            }
            else {
                // Return closure
                return $resource;
            }
        }

        // If Object/Array/primitive
        else {
            return $resource;
        }
    }


    /**
     *  Allows assigning a value to a resource identifier.
     *
     *  @param name A resource identifier (string). 
     *  @param value The resource value. 
     *  @return Void
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
     *
     *  A good example is when using this class to do dependency injection:
     *  $container->comments = function($c) {
     *      return $c->repository('Comment');
     *  };
     *
     *  When calling $c->repository(args) the resource denoted by the name "repository" 
     *  needs to be grabbed and then the arguments passed to that resource.
     *
     *  @param name The name of a resource within the Container. Should be callable. 
     *  @param arguments The arguments that will be passed to that Resource.
     *  @return The result of the executed resource or null.
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
        return null;
    }



    ////////////////////////////////////////////////////////////////////////
    //  IMPORANT UTILITY FUNCTIONS
    ////////////////////////////////////////////////////////////////////////

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
    public function find($name, $container = false)
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

        // Else check any parents.
        elseif ($container->parent) {
            return $container->find($name, $container->parent);
        }
        return null;
    }


    /**
     *  Adds a resource to this Container. 
     *
     *  @param item The item to be added. Can be a primitive, array, object, or Closure. 
     *  @param key The key (identifier) to store this item as. 
     *  @param dataKey If given, uses the value stored in that key within the Object or Array as the collection key. 
     *  @return self
     */
    public function add($item, $key = false, $dataKey = false)
    {        
        // If the data should be sorted by a property/key on it, but you want to add a prefix to 
        // the result. Example: If $item->month is a numeric value between 1 and 12, but there may be 
        // missing months in the data. Trying to access $collection->{'1'} will fetch OFFSET 1 which if 
        // January data is missing, could be another month. No Bueno. By passing in a prefix you can use 
        // $collection->month1 which will either be set or not and won't resort to returning first offset as result.
        $keyPrefix = '';
        if (is_array($dataKey)) {
            $keyPrefix = $dataKey[1];
            $dataKey = $dataKey[0];
        }

        if (is_object($item)) {
            if ($key) {
                if (!isset($this->key)) { $this->size += 1; }
                $this->singleton->$key = $item;
            }
            else if ($dataKey && isset($item->$dataKey)) {
                $key = $item->$dataKey;
                if (!isset($this->key)) { 
                    $this->size += 1; 
                }
                $this->singleton->{$keyPrefix.$key} = $item;
            }
            else {
                $offset = '_item'.$this->size;
                $this->size += 1;
                $this->singleton->{"$offset"} = $item;
            }
        }
        else if (is_array($item)) {
            if ($key) {
                if (!isset($this->key)) { $this->size += 1; }
                $this->singleton->$key = $item;
            }
            else if ($dataKey && isset($item[$dataKey])) {
                $key = $item[$dataKey];
                if (!isset($this->key)) { $this->size += 1; }
                $this->singleton->{$keyPrefix.$key} = $item;
            }
            else {
                $offset = '_item'.$this->size;
                $this->size += 1;
                $this->singleton->{"$offset"} = $item;
            }
        }
        else {
            if ($key) {
                if (!$this->__isset($key)) { $this->size += 1; }
                $this->singleton->$key = $item;
            }
            else {
                $offset = '_item'.$this->size;
                $this->size += 1;
                $this->singleton->{"$offset"} = $item;
            }
        }
        $this->contentModified = true;
        return $this;
    }


    /**
     *  Remove a resource.
     *
     *  @param name The identifier (key) for this resource within the Container.
     *  @return Void.
     */
    public function delete($name)
    {
        // Get actual name. If "off0" translates to just 0. 
        $name = $this->getName($name);

        // Figure out the key of the object we want to delete.
        // (if numeric value was passed in, turn that into actual key)
        $resourceKey = $name;
        if (is_numeric($name)) {
            $resourceKey = $this->fetchOffsetKey($name);
        }

        // Only mark the content as modified and change count if the delete call found 
        // a resource to remove.
        if ($this->processDelete($resourceKey)) {
            $this->contentModified = true;
            $this->size -= 1;
        }
    }


    /** 
     *  Alias for Delete(). 
     */
    public function remove($name)
    {
        $this->delete($name);
    }


    /** 
     *  Handles deleting a resource. 
     *
     *  @param name The name of the resource to be deleted. 
     *  @param container A parent container which will also be searched for item to remove. 
     */
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
        if (isset($container->signature->$name)) {
            unset($container->signature->$name);
            return true;
        }

        // Else check any parents.
        elseif ($container->parent) {
            return $container->processDelete($name, $container->parent);
        }
        return false;
    }


    /**
     *  Stores a single version of a resource created by a closure so that subsequent requests 
     *  are given the already created resource instead of invoking the closure again. 
     *  This method can be given resources which are not closures, but doesn't do anything useful 
     *  unless given a closure.
     *
     *  @param $name A string which starts with a non-numeric character. 
     *  @param $value A closure which returns an object, array, or primitive when invoked.
     *  @return void
     */
    public function singleton($name, $value)
    {
        // If value is a closure, store a reference that tells us we need to store the resulting 
        // value as a singleton after it's first executed.
        if ($value instanceOf \Closure) {
            $this->signaturesToSingletons->$name = true;
        }
        // Use the __set magic method to handle setting the resource.
        $this->$name = $value;      
    }


    /**
     *  Returns a resource by numerical offset. 
     *  
     *  @param num A number.
     */
    public function fetchOffset($num)
    {
        // If the content has been modified, regenerate $this->content.
        // and $this->contentKeys.
        if ($this->contentModified) {
            $this->generateContent();
        }
        
        // If the offset exists, return the data.
        $key = $this->fetchOffsetKey($num);
        if ($key != null) {
            return $this->content[$key];
        }
        return null;
    }


    /**
     *  Returns the offset name (string) of a resource for a given numerical offset.
     *
     *  @param num A numerical offset.
     */
    public function fetchOffsetKey($num)
    {
        // Keys for resources are always strings. If given a numerical offset, 
        // that offset needs to be interpreted to determine the actual property's name
        // at that offset.
        return isset($this->contentKeys[$num]) ? $this->contentKeys[$num] : null;
    }


    /**
     *  Attempts to return the collection as a simple array.
     *
     *  @return array
     */
    public function toArray()
    {
        $collection = $this->getIterator();
        $plainArray = [];

        foreach($collection as $prop => $result) {
            if (is_object($result) && method_exists($result, 'toArray')) {
                $plainArray[] = $result->toArray();
            } else {
                $plainArray[] = $result;
            }
        }
        return $plainArray;
    }


    /**
     *  Attempts to return the collection as a JSON encoded string.
     *
     *  @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }



    ////////////////////////////////////////////////////////////////////////
    //  UTILITY SUMMATION AND SINGLE RETURN METHODS
    ////////////////////////////////////////////////////////////////////////

    /**
     *  Returns the number of resources stored in this container/collection. 
     *
     *  @param includeParents Include parent resources in the total count?
     *  @param recount If true, then recounts the items rather than returning the stored count.
     *  @return The number of resources stored in this Container.
     */
    public function count($includeParents = false, $recount = false)
    {
        if ($recount) {
            return $this->getIterator()->count();
        }
        return $this->size;
    }
    
    
    /**
     *  Returns the FIRST result with a matching key=>value, else NULL.
     *  Example: if objects representing Users are stored and you want the result with 
     *  an Email value of 'Bob@gmail.com'
     *
     *  @param key A string representing a key or property. 
     *  @param value A value for which you are searching.
     */
    public function getByValue($key, $value)
    {
        $collection = $this->getIterator();

        foreach($collection as $result) {
            if ($result->$key == $value) {
                return $result;
            }
        }
        return null;
    }


    /**
     *  Returns the sum of the contents of the Container. 
     *  If given a key, will sum that $key->value. 
     *  Obviously it's important to have some consistency in the types of resources you 
     *  are storing or else the results might not make much sense or could crash. 
     *
     *  @param key A key on which to sum the when looking at the contents of this Container. 
     *  @return A number.
     */
    public function sumByKey($key = false)
    {
        $collection = $this->getIterator();
        $sum = 0;
        foreach ($collection as $result) {
            if ($key && isset($result->$key)) {
                $sum += $result->$key;
            }
            else if ($key && isset($result[$key])) {
                $sum += $result[$key];
            }
            else {
                $sum += $result;
            }
        }
        return $sum;
    }


    /**
     *  Returns the largest value.
     *
     *  @param key A key on which to sum the when looking at the contents of this Container. 
     *  @return mixed Value
     */
    public function max($key = false)
    {
        $collection = $this->getIterator();
        $max = 0;
        $valueToReturn = 0;
        foreach ($collection as $result) {
            if ($key && isset($result->$key)) {
                if ($result->$key > $max) {
                    $max = $result->$key;
                    $valueToReturn = $result;
                }
            }
            else if ($key && isset($result[$key])) {
                if ($result[$key] > $max) {
                    $max = $result[$key];    
                    $valueToReturn = $result;
                }
            }
            else {
                if ($result > $max) {
                    $max = $result;
                    $valueToReturn = $result;
                }
            }
        }
        return $valueToReturn;
    }


    /**
     *  Returns the smallest value.
     *
     *  @param key A key on which to sum the when looking at the contents of this Container. 
     *  @return mixed Value
     */
    public function min($key = false)
    {
        $sortedCollection = $this->sort($key, 'desc');
        return $sortedCollection->get(0);
    }



    ////////////////////////////////////////////////////////////////////////
    //  DATA FILTERING AND MANIPULATION (Returns instance of Container)
    ////////////////////////////////////////////////////////////////////////

    /**
     *  Returns a SUBSET of the results.
     *  If no matching subset exists, returns an empty Container.
     *
     *  Example would be if bunch of User objects were stored in this Container and you 
     *  wanted to return a subset of Users who's "Type" is equal to "Admin". 
     *  Container->where('Type', 'Admin');
     *
     *  @param key A key to look at when comparing the operator and the desired value. 
     *  @param desiredValue The value to compare against. 
     *  @param op The operator used in comparision. 
     *  @return A Container with the matching resources.
     */
    public function where($key = false, $desiredValue, $op = "==")
    {
        $collection = $this->getIterator();
        $subset = new Container();

        foreach($collection as $prop => $result) {
            // Grab resource value
            $realValue = $result;
            if (is_object($result)) {
                $realValue = $result->$key;
            }
            else if (is_array($result)) {
                $realValue = $result[$key];
            }

            // Check value against operator given
            $add = false;
            if ($op == '==' && $realValue == $desiredValue) {
                $add = true;
            }
            else if ($op == '>=' && $realValue >= $desiredValue) {
                $add = true;
            }
            else if ($op == '<=' && $realValue <= $desiredValue) {
                $add = true;
            }
            else if ($op == '>' && $realValue > $desiredValue) {
                $add = true;
            }
            else if ($op == '<' && $realValue < $desiredValue) {
                $add = true;
            }
            else if ($op == '===' && $realValue === $desiredValue) {
                $add = true;
            }
            else if ($op == '!=' && $realValue != $desiredValue) {
                $add = true;
            }

            // Add the resource to the subset if valid
            if ($add) {
                $subset->add($result, $prop);
            }
        }
        return $subset;
    }


    /**
     *  Merges the data given into this Container. 
     *
     *  @param data The data to merge in. 
     *  @param key The key name to sore the data in (should only be used when passing a single resource in)
     *  @param dataKey The value on the object/array to use as a key. 
     *  @return self
     */
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
        return $this;
    }    


    /** 
     *  Sorts the contents of this Container. 
     *
     *  @param key The key on which to sort. 
     *  @param dir The sort direction. 
     *  @return A reference to this Container. 
     */
    public function sort($key = false, $dir = 'desc')
    {
        if ($this->contentModified) {
            $this->generateContent();
        }
        $this->sortDirection = $dir;
        $this->sortKey = $key;
        $this->mergesort($this->content, array($this, 'compare'));
        $this->contentKeys = array_keys($this->content);
        return $this;
    }


    /**
     *  Map callback to data.
     *
     *  @param callback A callable function.
     *  @return A collection
     */
    public function map($callback)
    {
        $collection = $this->getIterator();
        $mutatedCollection = new Container();

        foreach($collection as $prop => $result) {
            $aValue = $callback($result, $prop);
            $mutatedCollection->add($aValue);
        }
        return $mutatedCollection;
    }  


    /**
     *  Filter data to that which passes the provided callback.
     *
     *  @param callback A callable function.
     *  @return A collection
     */
    public function filter($callback)
    {
        $collection = $this->getIterator();
        $mutatedCollection = new Container();

        foreach($collection as $prop => $result) {
            if ($callback($result, $prop)) {
                $mutatedCollection->add($result);
            }
        }
        return $mutatedCollection;
    }



    ////////////////////////////////////////////////////////////////////////
    //  SIMPLE ACCESSORS AND MODIFIERS
    ////////////////////////////////////////////////////////////////////////

    /**
     *  Simple Accessor for $signature data member
     *  Example of usage is to access the resources of a parent container from a child.
     *
     *  @return Object
     */
    public function getSignatures()
    {
        return $this->signature;
    }


    /**
     *  Simple Accessor for $singleton data member
     *
     *  @return Object
     */
    public function getSingletons()
    {
        return $this->singleton;
    }


    /**
     *  Simple setter for $returnClosure data member
     *
     *  @return Void
     */
    public function returnClosure($bool)
    {
        $this->returnClosure = $bool;
    }


    /**
     *  Unsets a resource stored in the singleton data member.
     *
     *  @return Void
     */
    public function unsetSingleton($name)
    {
        $this->singleton->$name = false;
        $this->contentModified = true;
    }


    /**
     *  Unsets a Closure stored in the signature data member.
     *
     *  @return Void
     */
    public function unsetSignature($name)
    {
        $this->signature->$name = false;
        $this->contentModified = true;
    }



    ////////////////////////////////////////////////////////////////////////
    //  REQUIRED BY PSR-11.
    //  https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md
    //
    //  Note that I'm not fully implementing the spec as I don't want to throw their exceptions.
    ////////////////////////////////////////////////////////////////////////
    
    /**
     *  Alias of magic method __get()
     * 
     *  @param $name Int | String
     *  @return Mixed
     */
    public function get($name)
    {
        return $this->$name;
    }


    /**
     *  Alias of magic method __isset()
     *
     *  @param $name Int | String
     *  @return Boolean
     */
    public function has($name)
    {
        return isset($this->$name);
    }



    ////////////////////////////////////////////////////////////////////////
    //  REQUIRED BY IteratorAggregate INTERFACE
    ////////////////////////////////////////////////////////////////////////
    
    /**
     *  Returns an ArrayIterator for traversing the contents of the Container.
     *
     *  @return ArrayIterator
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
     *  @param $offset Int | String
     *  @return bool
     */
    public function offsetExists($offset)
    {
        if ($this->get($offset)) {
            return true;
        }
        return false;
    }


    /**
     *  Returns an offset. If offset is not set, then returns null.
     *
     *  @param $offset Int | String
     *  @return mixed
     */
    public function offsetGet($offset) 
    {
        return $this->$offset;
    }


    /**
     *  Assigns the value provided to the offset.
     *
     *  @param $offset Int | String
     *  @param $value Mixed
     *  @return Void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }


    /**
     *  Unsets the given offset
     *
     *  @param $offset Int | String
     *  @return Void
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }



    ////////////////////////////////////////////////////////////////////////
    //  REQUIRED BY PHPUNIT (because it tries to serialize containers and Closures can't be serialized)
    ////////////////////////////////////////////////////////////////////////

    public function serialize()
    {
        return serialize($this->singleton);
    }


    public function unserialize($data)
    {
        return unserialize($data);
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
        $this->content = array_merge_recursive((array) $this->signature, (array) $this->singleton);
        $this->contentKeys = array_keys($this->content);
        $this->contentModified = false;
    }


    /**
     *  Find the name/offset of a resource. 
     *  If trying access resource "off0" this should grab the first 
     *  resource stored. Which means removing the "off" part and making 
     *  the name of the resource sought simply the number 0. 
     *
     *  @param $name The designed resource. A name or offset.
     *  @return Mixed
     */
    protected function getName($name)
    {
        preg_match("/off([0-9]+)/", $name, $nameMatch);
        if (isset($nameMatch[1])) {
            $name = (int) $nameMatch[1];
        }
        return $name;
    }


    /**
     *  A simple compare function which is used by the Sort method.
     *
     *  @param $a Mixed
     *  @param $b Mixed
     *  @return boolean
     */
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


    /**
     *  Returns the value when given a piece of data. 
     *  If the data item is a primitive or if no key was given, then the item
     *  is simply returned. However, if the data item is an object or array 
     *  and a key was given, then returns the offset given by the key as a value.
     *
     *  @param $data Mixed
     *  @param $key Int | String
     *  @return mixed
     */
    protected function getValue($data, $key = false)
    {
        $returnValue = $data;
        if ($key && is_object($data)) {
            $returnValue = $data->$key;
        }
        else if ($key && is_array($data)) {
            $returnValue = $data[$key];
        }
        return $returnValue;
    }


    /**
     *  A stable implementation of Mergesort (aka Stable-sort). 
     *  The end result is the array passed in being sorted according to the strategy provided 
     *  by the comparison function passed in.
     *
     *  @param $array Array
     *  @param $comparisionFunction A Callable.
     */
    protected function mergesort(&$array, $comparisonFunction) {

        // Exit right away if only zero or one item.
        if (count($array) < 2) {
            return true;
        }

        // Cut results in half.
        $halfway    = count($array) / 2;
        $leftArray  = array_slice($array, 0, $halfway, true);
        $rightArray = array_slice($array, $halfway, null, true);

        // Recursively call sort on left and right pieces
        $this->mergesort($leftArray, $comparisonFunction);
        $this->mergesort($rightArray, $comparisonFunction);

        // Check if the last element of the first array is less than the first element of 2nd.
        // If so, we are done. Just put the two arrays together for final result.
        if (call_user_func($comparisonFunction, end($leftArray), reset($rightArray)) < 1) {
            $array = $leftArray + $rightArray;
            return true;
        }

        // Set result array to blank. Set pointers to beginning of pieces.
        $array = array();
        reset($leftArray);
        reset($rightArray);

        // While looking at the current element in each array...
        while (current($leftArray) && current($rightArray)) {

            // Add the lowest element between the current element in the left and right arrays to the result.
            // Then advance to the next item on that side.
            if (call_user_func($comparisonFunction, current($leftArray), current($rightArray)) < 1) {
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
        while (current($leftArray)) {
            $array[key($leftArray)] = current($leftArray);
            next($leftArray);
        }
        while (current($rightArray)) {
            $array[key($rightArray)] = current($rightArray);
            next($rightArray);
        }
        return true;
    }
} // END Container
