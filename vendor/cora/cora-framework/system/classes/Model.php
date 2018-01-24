<?php
namespace Cora;

/**
 *
 */
class Model
{
    protected $model_data;
    protected $model_hydrating = false;
    public $data;
    public $model_db = false;
    public $model_dynamicOff;

    public function __construct()
    {
        $this->data = new \stdClass();
        
        if ($this->model_attributes_add && is_array($this->model_attributes_add)) {
            $this->model_attributes = array_merge($this->model_attributes, $this->model_attributes_add);
        }
    }

    public function _populate($record = null, $db = false)
    {
        // In order to stop unnecessary recursive issetExtended() checks while doing initial hydrating of model.
        $this->model_hydrating = true;

        // If this model is having a custom DB object passed into it,
        // then we'll use that for any dynamic fetching instead of
        // the connection defined on the model.
        // This is to facilitate using a Test database when running tests.
        if ($db) {
            $this->model_db = $db;
        }

        if($record) {
            // Populate model related data.
            foreach ($this->model_attributes as $key => $def) {

                // If the data is present in the DB, assign to model.
                // Otherwise ignore any data returned from the DB that isn't defined in the model.
                if (isset($record[$this->getFieldName($key)])) {
                    $fieldName = $this->getFieldName($key);
                    
                    if (\Cora\Gateway::is_serialized($record[$fieldName])) {
                        $value = unserialize($record[$fieldName]);
                        $this->beforeSet($key, $value); // Lifecycle callback
                        $this->model_data[$key] = $value;
                        $this->afterSet($key, $value); // Lifecycle callback
                    }
                    else if (isset($def['type']) && ($def['type'] == 'date' || $def['type'] == 'datetime')) {
                        $value = new \DateTime($record[$fieldName]);
                        $this->beforeSet($key, $value); // Lifecycle callback
                        $this->model_data[$key] = $value;
                        $this->afterSet($key, $value); // Lifecycle callback
                    }
                    else {
                        $value = $record[$fieldName];
                        $this->beforeSet($key, $value); // Lifecycle callback
                        $this->model_data[$key] = $value;
                        $this->afterSet($key, $value); // Lifecycle callback
                    }
                }
                else if (isset($def['models']) || (isset($def['model']) && isset($def['usesRefTable']))) {
                    if (!isset($this->model_data[$key])) $this->model_data[$key] = 1;
                }
            }

            // Populate non-model related data.
            // If a custom query was passed in to the repository (that had a JOIN or something)
            // and there was extra data fetched that doesn't directly below to the model,
            // we'll assign it to a normal model property here. This data will obviously
            // NOT be saved if a call is later made to save this object.
            $nonObjectData = array_diff_key($record, $this->model_attributes);
            if (count($nonObjectData) > 0) {
                foreach ($nonObjectData as $key => $value) {

                    // Note that if the model is using custom field names, this will result in a piece of data 
                    // getting set to both the official attribute and as non-model data. 
                    // I.E. If 'field' is set to 'last_modified' and the attribute name is 'lastModified', 
                    // the returned value from the Gateway will get assigned to the attribute in the code above like so: 
                    // $model->lastModified = $value 
                    // However because it's not worth doing a backwards lookup of the form $this->getAttributeFromField($recordKey) 
                    // (such a method would have to loop through all the attributes to find a match) 
                    // The data will also end up getting assigned here like so: 
                    // $model->last_modified = $value 
                    // An extra loop per custom field didn't seem worth the savings of a small amount of model memory size/clutter.
                    $this->$key = $value;
                }
            }
        }

        // Call onLoad method 
        $this->onLoad();

        $this->model_hydrating = false;
    }


    public function isPlaceholder($name)
    {
        // Ref this model's attributes in a shorter variable.
        $def = $this->model_attributes[$name];

        if (isset($def['models']) || (isset($def['model']) && isset($def['usesRefTable']))) {
            return true;
        }
        return false;
    }


    public function __isset($name)
    {
        if ($this->getAttributeValue($name) != null) {
            return true;
        }
        return false;
    }


    public function __get($name)
    {
        ///////////////////////////////////////////////////////////////////////
        // -------------------------------------
        // If the model DB data is already set.
        // -------------------------------------
        // ADM allows fetching of only part of a model's data when fetching
        // a record. So we have to check if the data in question has been fetched
        // from the DB already or not. If it has been fetched, we have to check
        // if it's a placeholder for a related model (related models can be set
        // to boolean true, meaning we have to dynamically fetch the model)
        ///////////////////////////////////////////////////////////////////////
        if (isset($this->model_data[$name])) {
            
            // Check if the stored data is numeric.
            // If it's not, then we don't need to worry about it being a
            // class reference that we need to fetch.
            if (is_numeric($this->model_data[$name])) {
                
                // Ref this model's attributes in a shorter variable.
                $def = $this->model_attributes[$name];

                // If desired data is a reference to a singular object.
                if (isset($def['model']) && !isset($this->model_dynamicOff)) {

                    if (isset($def['using'])) {
                        $this->$name = $this->getModelFromCustomRelationship($name, $def['model']);
                    }

                    // In the rare case that we need to fetch a single related object, and the developer choose
                    // to use a relation table to represent the relationship.
                    else if (isset($def['usesRefTable'])) {
                        $this->$name = $this->getModelFromRelationTable($name, $def['model']);
                    }

                    // In the more common case of fetching a single object, where the related object's
                    // ID is stored in a column on the parent object.
                    // Under this scenario, the value stored in $this->$name is the ID of the related
                    // object that was already fetched. So we can use that ID to populate a blank
                    // object and then rely on it's dynamic loading to fetch any additional needed info.
                    else {
                        // Create a blank object of desired type and assign it the ID we know
                        // references it. When we try and grab data from this new object,
                        // dynamic data fetching will trigger on it.
                        $relatedObj = $this->fetchRelatedObj($def['model']);

                        // Populate the new obj with data we have about it (should only be primaryKey/ID)
                        //$relatedObj->_populate([$relatedObj->getPrimaryKey() => $this->model_data[$name]]);
                        // $this->$name = $relatedObj;

                        // Fetch related object in whole 
                        $relObjRepo = $relatedObj->getRepository(true);
                        $this->$name = $relObjRepo->find($this->model_data[$name]);
                    }
                }

                // If desired data is a reference to a collection of objects.
                else if (isset($def['models']) && !isset($this->model_dynamicOff)) {
                    // If the relationship is one-to-many.
                    if (isset($def['via'])) {
                        $this->$name = $this->getModelsFromTableColumn($def['models'], $def['via']);
                    }

                    else if (isset($def['using'])) {
                        $this->$name = $this->getModelsFromCustomRelationship($name, $def['models']);
                    }

                    // If the relationship is many-to-many.
                    // OR if the relationship is one-to-many and no 'owner' type column is set,
                    // meaning there needs to be a relation table.
                    else {
                        $this->$name = $this->getModelsFromRelationTable($name, $def['models']);
                    }
                }
            }

            $this->beforeGet($name); // Lifecycle callback
            $returnValue = $this->model_data[$name];
            $this->afterGet($name, $returnValue); // Lifecycle callback
            return $returnValue;
        }

        ///////////////////////////////////////////////////////////////////////
        // If the model DB data is defined, but not grabbed from the database,
        // then we need to dynamically fetch it.
        // OR, we need to return an empty collection or NULL
        // in the case of the attribute pointing to models.
        ///////////////////////////////////////////////////////////////////////
        else if (isset($this->model_attributes[$name]) && !isset($this->model_dynamicOff)) {
            
            // If the attribute isn't the primary key of our current model, do dynamic fetch.
            if ($name != $this->getPrimaryKey()) {
                
                $def = $this->model_attributes[$name];
                
                if (isset($def['model'])) {

                    // If fetching via a defined column on a table.
                    if (isset($def['via'])) {
                        $this->$name = $this->getModelFromTableColumn($def['model'], $def['via']);
                    }

                    // If custom defined relationship for this single model
                    else if (isset($def['using'])) {
                        $this->$name = $this->getModelFromCustomRelationship($name, $def['model']);
                    }

                    // If desired data is a reference to a singular object, but it's defined as using a reference 
                    // table, then it's abstract in the sense that there's nothing on the current model's table 
                    // leading to it. We need to grab it using our method to grab data from a relation table.
                    else if (isset($def['usesRefTable'])) {
                        $this->$name = $this->getModelFromRelationTable($name, $def['model']);
                    }

                    // If we fell down to here, then the data we need is located on this model's table. 
                    else {
                        $this->$name = $this->fetchData($name);
                    }
                }
                // If desired data is a reference to a collection of objects. This means the relationship is 
                // abstract (no data on this model's table). We need to call appropriate method to get data 
                // from external table.
                else if (isset($def['models'])) {
                    // If the relationship is one-to-many.
                    if (isset($def['via'])) {
                        $this->$name = $this->getModelsFromTableColumn($def['models'], $def['via']);
                    }

                    else if (isset($def['using'])) {
                        $this->$name = $this->getModelsFromCustomRelationship($name, $def['models']);
                    }

                    // If the relationship is many-to-many.
                    // OR if the relationship is one-to-many and no 'owner' type column is set,
                    // meaning there needs to be a relation table.
                    else {
                        $this->$name = $this->getModelsFromRelationTable($name, $def['models']);
                    }
                } 
                
                // If we fell down to here, then the data we need is located on this model's table. 
                else {
                    $this->$name = $this->fetchData($name);
                }
                    
                // Fetch the value for this attribute that presumably got loaded from one of the above logic blocks.
                $this->beforeGet($name); // Lifecycle callback
                $returnValue = $this->model_data[$name];
                $this->afterGet($name, $returnValue); // Lifecycle callback

                // If the data we fetched from this model's table is a reference to either a single or collection of models
                // then we need to do some more work. If the data we fetched is an ID reference to model, that will need to be 
                // turned into an actual model still. If the data want is a collection of models, but the collection is empty, 
                // we'll have a null value on our hands - in this case let's return an empty collecion object instead.
                if (isset($def['models'])) {
                    if ($returnValue == null) {
                        $this->$name = new \Cora\Collection();
                        return $this->model_data[$name];
                    } else {
                        return $this->__get($name);
                    }
                }
                else if (isset($def['model'])) {
                    if ($returnValue != null) {
                        // Now that we fetched the ID, let's recursively call this method again and return the result so that ID 
                        // gets turned into an object.
                        return $this->__get($name);
                    }
                }
                return $returnValue;
            }
            else {
                $this->$name = null;
                $this->beforeGet($name); // Lifecycle callback
                $returnValue = $this->model_data[$name];
                $this->afterGet($name, $returnValue); // Lifecycle callback
                return $returnValue;
            }
        }


        ///////////////////////////////////////////////////////////////////////
        // If there is a defined DATA property (non-DB related), return the data.
        ///////////////////////////////////////////////////////////////////////
        if (isset($this->data->{$name})) {
            $this->beforeGet($name); // Lifecycle callback
            $returnValue = $this->data->{$name};
            $this->afterGet($name, $returnValue); // Lifecycle callback
            return $returnValue;
        }


        ///////////////////////////////////////////////////////////////////////
        // If there is a defined property (non-DB related), return the data.
        ///////////////////////////////////////////////////////////////////////
        $class = get_class($this);
        if (property_exists($class, $name)) {
            $this->beforeGet($name); // Lifecycle callback
            $returnValue = $this->{$name};
            $this->afterGet($name, $returnValue); // Lifecycle callback
            return $returnValue;
        }


        ///////////////////////////////////////////////////////////////////////
        // IF NONE OF THE ABOVE WORKED BECAUSE TRANSLATION FROM 'ID' TO A CUSTOM ID NAME
        // NEEDS TO BE DONE:
        // If your DB id's aren't 'id', but instead something like "note_id",
        // but you always want to be able to refer to 'id' within a class.
        ///////////////////////////////////////////////////////////////////////
        if ($name == 'id' && property_exists($class, 'id_name')) {
            $this->beforeGet($this->id_name); // Lifecycle callback
            if (isset($this->model_data[$this->id_name])) {
                $returnValue = $this->model_data[$this->id_name];
            }
            else {
                $returnValue = $this->{$this->id_name};
            }
            $this->afterGet($this->id_name, $returnValue); // Lifecycle callback
            return $returnValue;
        }


        ///////////////////////////////////////////////////////////////////////
        // If this model extends another, and the data is present on the parent, return the data.
        ///////////////////////////////////////////////////////////////////////
        if (substr($name, 0, 6 ) != "model_" && $this->issetExtended($name)) {
            $this->beforeGet($name); // Lifecycle callback
            $returnValue = $this->getExtendedAttribute($name);
            $this->afterGet($name, $returnValue); // Lifecycle callback
            return $returnValue;
        }


        ///////////////////////////////////////////////////////////////////////
        // No matching property was found! Normally this will return null.
        // However, just-in-case the object has something special setup
        // in the beforeGet() callback, we need to double check that the property
        // still isn't set after that is called.
        ///////////////////////////////////////////////////////////////////////
        $this->beforeGet($name); // Lifecycle callback
        //if (property_exists($class, $name)) {
        if (isset($this->{$name})) {
            $returnValue = $this->{$name};
        }
        else {
            $returnValue = null;
        }
        $this->afterGet($name, $returnValue); // Lifecycle callback
        return $returnValue;
    }


    public function __set($name, $value)
    {
        // Lifecycle callback
        $this->beforeSet($name, $value);
        
        // If a matching DB attribute is defined for this model.
        if (isset($this->model_attributes[$name])) {
            $def = $this->model_attributes[$name];
            if (isset($def['type']) && ($def['type'] == 'date' || $def['type'] == 'datetime') && is_string($value)) {
                $value = new \DateTime($value);
                $this->model_data[$name] = $value;
            }
            else {
                $this->model_data[$name] = $value;
            }
        }

        // If your DB id's aren't 'id', but instead something like "note_id",
        // but you always want to be able to refer to 'id' within a class.
        else if ($name == 'id' && property_exists(get_class($this), 'id_name')) {
            $id_name = $this->id_name;
            if (isset($this->model_attributes[$id_name])) {
                $this->model_data[$id_name] = $value;
            }
            else {
                $id_name = $this->id_name;
                $this->{$id_name} = $value;
            }
        }

        // If this model extends a parent, and the parent has this attribute.
        else if ($this->model_hydrating == 0 && $this->hasAttribute($name)) {
            $this->setExtendedAttribute($name, $value);
        }

        // Otherwise if a plain model attribute is defined.
        else {
            $this->{$name} = $value;
        }

        // Lifecycle callback
        $this->afterSet($name, $value);
    }


    public function hasAttribute($name) 
    {
        if (isset($this->model_attributes[$name])) {
            return true;
        }
        else if (isset($this->model_extends) && isset($this->model_attributes[$this->model_extends])) {
            $extendedModel = $this->{$this->model_extends};
            
            if ($extendedModel) {
                return $extendedModel->hasAttribute($name);
            }
        }
        return false;
    }


    public function issetExtended($name) 
    {
        if (isset($this->$name)) {
            return true;
        }
        else if (isset($this->model_extends) && isset($this->model_attributes[$this->model_extends])) {
            $extendedModel = $this->{$this->model_extends};
            
            if ($extendedModel) {
                return $extendedModel->issetExtended($name);
            }
        }
        return false;
    }


    /**
     *  Fetches a collection of the data attributes that apply to this model.
     *  Does not include relationships. Will included extended fields unless set to exclude.
     *  
     *  @param boolean $excludeExtended Whether or not to exclude extended data elements. Defaults to false.
     *  @return \Cora\Collection
     */
    public function getDataAttributes($excludeExtended = false) 
    {
        $attributes = new \Cora\Collection();

        foreach ($this->model_attributes as $key => $def) {
            if (!isset($def['model']) && !isset($def['models'])) {
                $attributes->add($key);
            }
        }

        if (isset($this->model_extends) && isset($this->model_attributes[$this->model_extends])) {
            $extendedModel = $this->{$this->model_extends};
            if ($extendedModel) {
                $attributes->merge($extendedModel->getDataAttributes());
            }
        }
        return array_unique($attributes->toArray());
    }


    public function getExtendedAttribute($name) 
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
        else if (isset($this->model_extends) && isset($this->model_attributes[$this->model_extends])) {
            $extendedModel = $this->{$this->model_extends};
            if ($extendedModel) {
                return $extendedModel->getExtendedAttribute($name);
            }
        }
        return null;
    }


    public function setExtendedAttribute($name, $value) 
    {
        if (isset($this->model_attributes[$name])) {
            $this->$name = $value;
        }
        else if (isset($this->model_extends) && isset($this->model_attributes[$this->model_extends])) {
            $extendedModel = $this->{$this->model_extends};
            if ($extendedModel) {
                $extendedModel->setExtendedAttribute($name, $value);
            }
        }
    }


    /**
     *  For getting model data without triggering dynamic data fetching.
     */
    public function getAttributeValue($name, $convertDates = true)
    {
        if (isset($this->model_data[$name])) {
            $result = $this->model_data[$name];
            if ($result instanceof \DateTime && $convertDates == true) {
                $result = $result->format('Y-m-d H:i:s');
            }
            return $result;
        }
        if (isset($this->data->{$name})) {
            return $this->data->{$name};
        }
        return null;
    }


    /**
     *  For getting model data without triggering unnecessary dynamic data fetching.
     *  Will trigger load of extended object, but not on any ID being referenced.
     * 
     *  If EMPLOYEE extends USER and each user has a reference to another user who is their boss,
     *  you may want to grab $employee->boss->id, but not want to load the info for their boss.
     *  $employee->getAttributeValueExtended('boss') allows you to do that.
     * 
     *  This method is different from getAttributeValue in that this one checks extended relationships
     *  and the other doesn't. They could theoretically be combined, but some places seem to use 
     *  getAttributeValue as an "issetAttribute" check, so if it returns any value for stuff not 
     *  on the model's immediate table, it screws stuff up.
     */
    public function getAttributeValueExtended($name, $convertDates = true)
    {
        if (isset($this->model_data[$name])) {
            $result = $this->model_data[$name];
            if ($result instanceof \DateTime && $convertDates == true) {
                $result = $result->format('Y-m-d H:i:s');
            }
            return $result;
        }
        if (isset($this->data->{$name})) {
            return $this->data->{$name};
        }
        else if (isset($this->model_extends) && isset($this->model_attributes[$this->model_extends])) {
            $extendedModel = $this->{$this->model_extends};
            if ($extendedModel && $result = $extendedModel->getAttributeValue($name)) {
                return $result;
            }
        }
        return null;
    }


    protected function getModelFromRelationTable($attributeName, $objName)
    {
        // Same logic as grabbing multiple objects, we just return the first (and only expected) result.
        return $this->getModelsFromRelationTable($attributeName, $objName)->get(0);
    }


    protected function getModelsFromRelationTable($attributeName, $objName)
    {
        $relatedObj = $this->fetchRelatedObj($objName);

        // Grab relation table name and the name of this class.
        $relTable = $this->getRelationTableName($relatedObj, $attributeName, $this->model_attributes[$attributeName]);
        $className = strtolower((new \ReflectionClass($this))->getShortName());
        $objectId = $this->getPrimaryKey();
        $relatedClassName = strtolower((new \ReflectionClass($relatedObj))->getShortName());

        // Create repo that uses the relationtable, but returns models populated
        // with their IDs.
        $repo = \Cora\RepositoryFactory::make('\\'.get_class($relatedObj), false, $relTable, false, $this->model_db);
        
        ///////////////////////////////////////
        // Define custom query for repository.
        ///////////////////////////////////////

        // Get DB adaptor to use. 
        // In situations where multiple DBs are being used and there's a relation table 
        // between data on different DBs, we can't be sure which DB holds the relation table. 
        // First try the DB the related object is on. If that doesn't contain the relation table,
        // then try the current object's DB.
        $db = $relatedObj->getDbAdaptor();
        if (!$db->tableExists($relTable)) {
            $db = $this->getDbAdaptor();
        }

        // Setup relation table field names 
        $relThis = isset($this->model_attributes[$attributeName]['relThis']) ? $this->model_attributes[$attributeName]['relThis'] : $className;
        $relThat = isset($this->model_attributes[$attributeName]['relThat']) ? $this->model_attributes[$attributeName]['relThat'] : $relatedClassName;
        
        // DEFAULT CASE 
        // The objects that are related aren't the same class of object...
        // (or they are, but relThis and relThat definitions were setup)
        if ($relThis != $relThat) {
            $db ->select($relThat.' as '.$relatedObj->getPrimaryKey())
                ->where($relThis, $this->$objectId);

            return $repo->findAll($db);
        }
        
        // EDGE CASE 
        // The objects that are related ARE the same class of object...
        // If two Users are related to each other, can't have two "user" columns in the ref table. Instead 2nd column gets named "User2" 
        // TABLE: ref_users__example__users
        // COLUMNS:   User      |  User2 
        //            Bob's ID     Bob's relative's ID
        else {
            // Fetch related objects where the subject is the left side reference.
            $db ->select($relThat.'2'.' as '.$relatedObj->getPrimaryKey())
                ->where($relThis, $this->$objectId);
            $leftSet = $repo->findAll($db);

            // Fetch related objects where the subject is the right side reference.
            $db ->select($relThat.' as '.$relatedObj->getPrimaryKey())
                ->where($relThis.'2', $this->$objectId);
            $rightSet = $repo->findAll($db);
            $leftSet->merge($rightSet);
            return $leftSet;
        }
    }


    protected function getModelFromTableColumn($objName, $relationColumnName)
    {
        // Same logic as grabbing multiple objects, we just return the first (and only expected) result.
        return $this->getModelsFromTableColumn($objName, $relationColumnName)->get(0);
    }


    /**
     *  The Related Obj's table should have some sort of 'owner' column
     *  for us to fetch by.
     */
    protected function getModelsFromTableColumn($objName, $relationColumnName)
    {
        // Figure out the unique identifying field of the model we want to grab.
        $relatedObj = $this->fetchRelatedObj($objName);
        $idField = $relatedObj->getPrimaryKey();

        //$relatedClassName = strtolower((new \ReflectionClass($relatedObj))->getShortName());
        $repo = \Cora\RepositoryFactory::make($objName, false, false, false, $this->model_db);

        $db = $relatedObj->getDbAdaptor();
        $db->where($relationColumnName, $this->{$this->getPrimaryKey()});
        //$db->select($idField);
        return $repo->findAll($db);
    }


    /**
     *  The Related Obj's are defined by a custom query. A "using" definition states which model 
     *  method defines the relationship. Within that method any query parameters must bet set and 
     *  a Query Builder object returned.
     */
     public function getModelsFromCustomRelationship($attributeName, $objName)
     {
         // Grab a dummy instance of the related object.
         $relatedObj = $this->fetchRelatedObj($objName);

         // Create a repository for the related object.
         $repo = \Cora\RepositoryFactory::make($objName, false, false, false, $this->model_db);
        
         // Grab a Query Builder object for the connection this related model uses.
         $query = $relatedObj->getDbAdaptor();
         
         // Grab the name of the method that defines the relationship
         $definingFunctionName = $this->model_attributes[$attributeName]['using'];
         
         $query = $this->$definingFunctionName($query);
         
         return $repo->findAll($query);
     }


    public function getModelFromCustomRelationship($attributeName, $objName)
    {
        return $this->getModelsFromCustomRelationship($attributeName, $objName)->get(0);
    }


    public function getClassName($class = false)
    {
        if ($class == false) { $class = $this; }
        return strtolower((new \ReflectionClass($class))->getShortName());
    }


    /**
     *  Not intended to replace get_class! This assumes your model namespace starts with "models"
     *  and you want the classname minus the Models part. 
     *  If get_class returns "Models\Tests\User", this would return "Tests\User".
     */
    function getFullClassName($class = false)
    {
        if ($class == false) { $class = $this; }
        $className = get_class($class);
        if ($pos = strpos($className, '\\')) return substr($className, $pos + 1);
        return $className;
    }


    public function fetchRelatedObj($objFullName)
    {
        // Load and set cora config.
        require(dirname(__FILE__).'/../config/config.php');

        // Load custom app config
        include($config['basedir'].'cora/config/config.php');

        $objType = CORA_MODEL_NAMESPACE.$objFullName;
        return new $objType();
    }


    protected function fetchData($name)
    {
        $gateway = new \Cora\Gateway($this->getDbAdaptor(), $this->getTableName(), $this->getPrimaryKey());
        return $gateway->fetchData($this->getFieldName($name), $this);
    }


    public function returnExistingConnectionIfExists($connectionName)
    {
        if (isset($GLOBALS['coraAdaptorsForCurrentSave'][$connectionName])) {
            return $GLOBALS['coraAdaptorsForCurrentSave'][$connectionName];
        }
        return false;
    }


    public function getDbAdaptor($freshAdaptor = false)
    {
        // This is for checking if any existing DB connection exists that the adaptor getting created can use. 
        // In order to support transactions and optimistic locking When doing a bunch of ORM actions, 
        // connections are temporarily stored globally until the transaction is over.
        $existingConnection = false;
        
        // If a custom DB object was passed in, use that and return.
        if ($this->model_db) {
            return $this->model_db;
        }

        // If a specific DB Connection is defined for this model, use it.
        else if (isset($this->model_connection)) {
            $existingConnection = $this->returnExistingConnectionIfExists($this->model_connection);
            return \Cora\Database::getDb($this->model_connection, $existingConnection);
        }

        // If no DB Connection is specified...
        else {
            $existingConnection = $this->returnExistingConnectionIfExists(\Cora\Database::getDefaultConnectionName());

            // If specified that we need to return a new adaptor instance, do it.
            // This becomes necessary when saving an object that has related objects attached to it.
            // The way Cora's Database class is built, it only handles one query at a time.
            // So if the Gateway starts building the query to save say a User to the database, but while
            // doing so encounters an Article owned by that user which needs to be saved, initiating a save
            // on that child object if it also uses the default DB adaptor will be problematic because it
            // will end up altering the query getting built up for the parent. The solution is to set
            // this fresh option and get a new Database instance.
            if ($freshAdaptor) {
                return \Cora\Database::getDefaultDb(true, $existingConnection);
            }

            // else use the default defined in the config.
            // This references a static object for efficiency.
            else {
                return \Cora\Database::getDefaultDb(false, $existingConnection);
            }
        }
    }


    public function getRepository($fresh = false, $modelFullName = false)
    {
        if ($modelFullName) {
            return \Cora\RepositoryFactory::make($modelFullName, false, false, $fresh);
        }
        return \Cora\RepositoryFactory::make('\\'.get_class($this), false, false, $fresh);
    }


    public function getTableName()
    {
        // Uses the class name to determine table name if one isn't given.
        // If value of $class is 'WorkOrder\\Note' then $tableName will be 'work_orders_notes'.
        $tableName = false;

        // See if a custom table name is defined.
        if (isset($this->model_table)) {
            $tableName = $this->model_table;
        }

        // Otherwise determine table name from class path+name.
        else {
            $class = get_class($this);
            $tableName = $this->getTableNameFromNamespace($class);
        }
        return $tableName;
    }


    public function getTableNameFromNamespace($classNamespace)
    {
        // Uses the class name to determine table name if one isn't given.
        // If value of $class is 'WorkOrder\\Note' then $tableName will be 'work_order_notes'.
        $namespaces = explode('\\', $classNamespace);

        // Remove type of class from namespace (i.e. remove 'models')
        array_shift($namespaces);

        $tableName = '';
        $length = count($namespaces);
        for ($i = 0; $i < $length; $i++) {
            $namespace = $namespaces[$i];
            if ($i == $length-1) {
                $tableName .= strtolower(preg_replace('/\B([A-Z])/', '_$1', str_replace('\\', '', $namespace))).'s_';
            }
            else {
                $tableName .= strtolower(preg_replace('/\B([A-Z])/', '_$1', str_replace('\\', '', $namespace))).'_';
            }
        }
//        foreach ($namespaces as $namespace) {
//            $tableName .= strtolower(preg_replace('/\B([A-Z])/', '_$1', str_replace('\\', '', $namespace))).'s_';
//        }
        $tableName = substr($tableName, 0, -1);
        return $tableName;
    }


    /**
     *  Given a related object, a model attribute (on this model) and the attribute definition,
     *  return the name of the relation table connecting this model and the given object. 
     *
     *  The relation table is named by:
     *  Taking the table name for model 1, 
     *  Concatenating the relationship name or key name connecting them,
     *  Then concatenated the table name for model 2.
     *  
     *  If a User model in the root models directory is being related to another User and the relationship name is "motherChild",
     *  the table will be user__motherchild__user
     */
    public function getRelationTableName($relatedObj, $attribute, $attributeDef)
    {
        $result = '';

        // Check if a custom relation table name is defined.
        if (isset($attributeDef['relTable'])) {
            $result = $attributeDef['relTable'];
        }

        // Otherwise determine the relation table by conjoining the two namespaces.
        else {
            $table1 = $this->getTableName();
            $table2 = $relatedObj->getTableName();
            $alphabeticalComparison = strcmp($table1, $table2);

            // Check if a relationship name is set, otherwise just use the attribute as the relationship identifier
            if (isset($attributeDef['relName'])) {
                $attribute = $attributeDef['relName'];
            }
            $attribute = strtolower(preg_replace('/\B([A-Z])/', '_$1', $attribute));

            if ($alphabeticalComparison > 0) {
                $result = 'ref_'.$table1.'__'.$attribute.'__'.$table2;
            }
            else {
                $result = 'ref_'.$table2.'__'.$attribute.'__'.$table1;
            }
        }
        return substr($result, 0, 64);
    }


    public function usesRelationTable($relatedObj, $attribute)
    {
        $def = $this->model_attributes[$attribute];
        if (isset($def['models']) && !isset($def['via']) && !isset($def['using'])) {
            return $this->getRelationTableName($relatedObj, $attribute, $def);
        }
        else if (isset($def['model']) && isset($def['usesRefTable'])) {
            return $this->getRelationTableName($relatedObj, $attribute, $def);
        }
        return false;
    }


    public function usesViaColumn($relatedObj, $attribute)
    {
        $def = $this->model_attributes[$attribute];
        if (isset($def['models']) && isset($def['via'])) {
            return $def['via'];
        }
        return false;
    }


    public function getPrimaryKey()
    {
        // Search the model definition for its primary key.
        // Return the name of that field.
        foreach ($this->model_attributes as $key => $def) {
            if (isset($def['primaryKey'])) {
                if ($def['primaryKey'] == true) {
                    return $key;
                }
            }
        }

        // If no primary key is defined (BAD DEVELOPER! BAD!)
        // Then try returning 'id' and hope that works.
        return 'id';
    }


    /**
     *  By default, an attribute is stored in a DB field with matching name. 
     *  So lastModified would be stored in a column named lastModified. 
     *  However, there may be situations where a developer wants the attribute name 
     *  in the model to be different than the DB. I.E. lastModified => last_modified. 
     *
     *  This method, when given an attribute on the model, should read the model definition 
     *  and return the field name in the DB.
     */
    public function getFieldName($attributeName)
    {
        if (isset($this->model_attributes[$attributeName]['field'])) {
            return $this->model_attributes[$attributeName]['field'];
        }
        return $attributeName;
    }


    /**
     *  "Touches" any related models to force them to be grabbed from the persistance layer.
     */
    public function loadAll()
    {
        $this->data->id = $this->id;
        foreach ($this->model_attributes as $key => $value) {
            $temp = $this->$key;
        }
    }


    public function onLoad()
    {
        //echo __FUNCTION__;
    }


    public function save()
    {
        $repo = $this->getRepository();
        $repo->save($this);
    }


    public function delete()
    {
        return true;
    }

    public function __toString()
    {
        return $this->toJson();
    }

    public function toArray($inputData = '__cora__empty')
    {
        // If nothing was passed in, default to this object. 
        if ($inputData === '__cora__empty') {
            $inputData = $this;
        }
        
        // If input is a Cora model
        if ($inputData instanceof \Cora\Model) {
            $object = new \stdClass;
            foreach ($this->model_data as $key => $data) {
                if ($data instanceof \Cora\Model) {
                    $object->$key = $data->toArray();
                } else {
                    $object->$key = $this->toArray($data);
                }
            }
            foreach ($this->data as $key => $data) {
                if ($data instanceof \Cora\Model) {
                    $object->$key = $data->toArray();
                } else {
                    $object->$key = $this->toArray($data);
                }
            }
            return (array) $object;
        }

        // If input is iterable
        else if (is_array($inputData) || $inputData instanceof \Traversable) {
            $object = new \stdClass;
            foreach ($inputData as $key => $data) {
                if ($data instanceof \Cora\Model) {
                    $object->$key = $data->toArray();
                } else {
                    $object->$key = $this->toArray($data);
                }
            }
            return (array) $object;
        }

        return $inputData;
    }

    public function toJson($inputData = false)
    {
        // If nothing was passed in, default to this object. 
        if ($inputData === false) {
            $inputData = $this;
        }
        
        return json_encode($this->toArray($inputData));
    }


    public function beforeCreate()
    {
        //echo __FUNCTION__;
    }


    public function afterCreate()
    {
        //echo __FUNCTION__;
    }


    public function beforeSave()
    {
        //echo __FUNCTION__;
    }


    public function afterSave()
    {
        //echo __FUNCTION__;
    }


    public function beforeSet($prop, $value)
    {
        //echo __FUNCTION__;
    }


    public function afterSet($prop, $value)
    {
        //echo __FUNCTION__;
    }


    public function beforeGet($prop)
    {
        //echo __FUNCTION__;
    }


    public function afterGet($prop, &$value)
    {
        //echo __FUNCTION__;
    }

    public static function model_constraints($query) 
    {
        return $query;
    }
}
