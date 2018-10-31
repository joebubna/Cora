<?php
namespace Cora;

/**
 *
 */
class Model
{
    public $model_data;
    protected $model_hydrating = false;
    protected $model_loadMapsEnabled = true;
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


    /**
     *  Hydrates this model with data.
     * 
     *  @param array  $record   (optional) An associative array (like a row of data from a database).  
     *  @param object $db       (optional) A Database object for fetching data.  
     *  @param object $loadMap  (optional) An object that defines which data should be loaded and how.
     */
    public function _populate($record = null, $db = false, $loadMap = false)
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
        $this->_populateAttributes($record);

        // Populate non-model related data.
        $this->_populateNonModelData($record);
      }

      // Populate data as mapped
      if ($this->model_loadMapsEnabled) {
        $this->_populateLoadMap($record, $loadMap);
      }

      // Call onLoad method 
      $this->onLoad();

      // If a custom onLoad function was given as part of a loadMap
      // Also call that
      if ($loadMap instanceof \Cora\Adm\LoadMap && $func = $loadMap->getOnLoadFunction()) {
        // Fetch any array of args passed along with the LoadMap
        $args = $loadMap->getOnLoadArgs();

        // Add a reference to this model as the first argument
        array_unshift($args, $this);

        // Call user provided onLoad closure
        call_user_func_array($func, $args); 
      }

      $this->model_hydrating = false;

      return $this;
    }


    protected function _populateLoadMap($record, $loadMap) 
    {
      // If no loadMap is given, don't do anything
      if (!$loadMap instanceof \Cora\Adm\LoadMap) return;
      
      // Map data from the data record to attributes on this model
      foreach ($loadMap->getLocalMapping() as $recordKey => $mapToAttribute) {
        if (array_key_exists($recordKey, $record)) {

          // If specifying that the record key should not be matched for this model,
          // then unset that attribute.
          if ($mapToAttribute[0] == '!') {
            $this->{substr($mapToAttribute, 1)} = null;
          } 
          
          // Map an offset in the record to the attribute
          else {
            $this->model_data[$mapToAttribute] = $record[$recordKey];
          }
        }
      }
      
      // Load specified relationships
      foreach ($loadMap->getRelationsMapping() as $attributeToLoad => $mapping) {
        if (isset($this->model_attributes[$attributeToLoad])) {

          // If this attribute is defined as a singular model, AND a mapping file was given for it, AND the LoadMap doesn't explicitly say we 
          // need to fetch the data, then use any data passed in rather than dynamically fetching the data.
          if (isset($this->model_attributes[$attributeToLoad]['model']) && $mapping instanceof \Cora\Adm\LoadMap && !$mapping->fetchData()) {
            // Fetch an object of the correct type
            $relatedObj = $this->fetchRelatedObj($this->model_attributes[$attributeToLoad]['model']);

            // Before we pass the record down to any related objects, we need to unset "id" 
            // if it is set in the data. ID directly as a class attribute takes presidence 
            // over $id_name, which causes problems. 
            unset($record['id']);
            
            // Populate the related model with any record data that is relevant to it.
            $relatedObj->_populate($record, false, $mapping);

            // Store object
            $this->$attributeToLoad = $relatedObj;
          }
          
          // Otherwise just make sure the data for the attribute gets dynamically loaded.
          else {
            $this->_getAttributeData($attributeToLoad, false, $mapping, $record);
          }

          // After the loading above, if an attribute defined as a model is still not an object, generate an empty object.
          if (isset($this->model_attributes[$attributeToLoad]['model']) && !is_object($this->$attributeToLoad)) {

            // Iterate over the attributes on a model and generate an empty array to populate a dummy model with
            $dataToLoad = array_map(function($item) { 
              if (isset($item['model'])) {
                return 0;
              }
              else if (isset($item['models'])) {
                return [];
              }
              else if (isset($item['primaryKey'])) {
                return null;
              }
              return ''; 
            }, $this->model_attributes);
            
            // Fetch an EMPTY object of the correct type and populate it with our dummy data.
            $this->$attributeToLoad = $this->_loadAttributeObject($attributeToLoad, $dataToLoad, $mapping);
          }

        }
      }
    }


    /**
     *  
     */
    protected function _loadAttributeObject($attributeToLoad, $dataForPopulation = [], $mapping = false)
    {
      // Fetch an object of the correct type
      $relatedObj = $this->fetchRelatedObj($this->model_attributes[$attributeToLoad]['model']);

      // Before we pass the record down to any related objects, we need to unset "id" 
      // if it is set in the data. ID directly as a class attribute takes presidence 
      // over $id_name, which causes problems. 
      unset($dataForPopulation['id']);
      
      // Populate the related model with any record data that is relevant to it.
      $relatedObj->_populate($dataForPopulation, false, $mapping);
      
      return $relatedObj;
    }


    /**
     *  For handling data which is defined as being on this model.
     * 
     *  @param array $record An associative array (like a row of data from a database).  
     *  @return void
     */
    protected function _populateAttributes($record)
    {
      foreach ($this->model_attributes as $key => $def) {

        // If the data is present in the DB, assign to model.
        // Otherwise ignore any data returned from the DB that isn't defined in the model.
        if (isset($record[$this->getFieldName($key)])) {
          $fieldName = $this->getFieldName($key);
          
          if (\Cora\Gateway::is_serialized($record[$fieldName])) {
            $value = unserialize($record[$fieldName]);
          }
          else if (isset($def['type']) && ($def['type'] == 'date' || $def['type'] == 'datetime')) {
            $value = new \DateTime($record[$fieldName]);
          }
          else {
            $value = $record[$fieldName];
          }
          $this->beforeSet($key, $value); // Lifecycle callback
          $this->model_data[$key] = $value;
          $this->afterSet($key, $value); // Lifecycle callback
        }
        else if (isset($def['models']) || (isset($def['model']) && isset($def['usesRefTable']))) {
          if (!isset($this->model_data[$key])) $this->model_data[$key] = 1;
        }
      }
    }


    /**
     *  For handling data that is not defined on this model...
     *  
     *  If a custom query was passed in to the repository (that had a JOIN or something)
     *  and there was extra data fetched that doesn't directly belong to the model,
     *  we'll assign it to a normal model property here. This data will obviously
     *  NOT be saved if a call is later made to save this object.
     * 
     *  @param array $record An associative array (like a row of data from a database).  
     *  @return void
     */
    protected function _populateNonModelData($record)
    {
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


    /**
     *  Sometimes a placeholder value of "1" will be loaded into models which indicate the data is available,
     *  but must be fetched if needed. This method indicates whether or not a value of 1 means it's a placeholder.
     * 
     *  @param $attributeName string An attribute name who's value in model_data is "1"
     *  @return bool
     */
    public function isPlaceholder($attributeName)
    {
      // Ref this model's attributes in a shorter variable.
      $def = $this->model_attributes[$attributeName];

      if (isset($def['models']) || (isset($def['model']) && isset($def['usesRefTable']))) {
        return true;
      }
      return false;
    }


    /**
     *  Within this model class, isset() is needed to check if things are set. Internally this needs to return false 
     *  for Cora extended class attributes.
     *  However, outside this class, isset() can also be called, but in that case extended (using Cora extends system) 
     *  attributes need to return true.
     *  In order to handle these opposite needed behaviors depending on who is calling, code was added to grab the calling class.
     */
    public function __isset($name)
    {
      // Get the calling class, so we can determine if isset was called internally or externally.
      $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'];
      if ($caller == self::class) {
          return $this->getAttributeValue($name) != null;
      }
      return $this->getAttributeValueExtended($name) != null;
    }


    /**
     *  Called when data not formally defined on the model class is accessed.
     */
    public function __get($name)
    {
      return $this->_getAttributeData($name);
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
        else if ($name == 'id' && !$this->model_hydrating && property_exists(get_class($this), 'id_name')) {
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


    /**
     *  Intercepts methods calls on this object.
     *  $model->method(arg1, arg2)
     *  OR
     *  $model->pluralObjRelationProperty(argClosure, argParams, argLoadMapping)
     *
     *  @param name The name of a function in this model OR a plural relationship to another model 
     *  @param arguments 1. A closure, 2. An argument (or array of arguments), 3. A loadmap array
     *  @return The function or relationship result
     */
    public function __call($name, $arguments)
    {   
      // Vars
      $def = [];
      
      // If matching attribute is defined, then grab info
      if (isset($this->model_attributes[$name])) {
        $def = $this->model_attributes[$name];

        // Get query object that we can use
        $query = $this->_getQueryObjectForRelation($name);
        
        // Build arguments array for closure as necessary
        $funcArgs = [];
        if (isset($arguments[1])) {
          $funcArgs = is_array($arguments[1]) ? $arguments[1] : [$arguments[1]];
        }
        array_unshift($funcArgs, $query);
        
        // Call the provided function with the query and arguments
        $query = call_user_func_array($arguments[0], $funcArgs);

        // Determine if a LoadMap was passed in
        $loadMap = isset($arguments[2]) ? $arguments[2] : false;
        
        // Fetch data
        $this->$name = $this->_getCustomValue($name, $query, $loadMap);
        return $this->$name;
      }

      // If an attribute relationship wasn't what was called, then assume it's a normal 
      // function call and pass on the request to the matching function (or error out)
      if (method_exists($this, $name)) {
        call_user_func_array(array($this, $name), $arguments);
      } else {
        throw new \Exception('Made a method call on a model when no such method or model relationship exists.');
      }
    }






    /**
     *  Returns a query object for a specific related model.
     * 
     *  @return QueryBuilder
     */
    protected function _getQueryObjectForRelation($attribute)
    {
      // Setup
      $def = $this->model_attributes[$attribute];
      
      // If not a model relationship, just return an adaptor for this model
      if (!isset($def['model']) && !isset($def['models'])) {
        return $this->getDbAdaptor();
      }

      // Get DB adaptor to use for model relationships
      $relatedObj = isset($def['models']) ? $this->fetchRelatedObj($def['models']) : $this->fetchRelatedObj($def['model']);
      $query = $relatedObj->getDbAdaptor();

      // If the relationship is many-to-many and uses a relation table.
      // OR if the relationship is one-to-many and no 'owner' type column is set,
      // meaning there needs to be a relation table.
      // If 'via' or 'using' is not set, then it's assumed the relation utilizes a relation table.
      if (!isset($def['via']) && !isset($def['using'])) {
        
        // Grab relation table name
        $relTable = $this->getRelationTableName($relatedObj, $attribute, $this->model_attributes[$attribute]);

        // In situations where multiple DBs are being used and there's a relation table 
        // between data on different DBs, we can't be sure which DB holds the relation table. 
        // First try the DB the related object is on. If that doesn't contain the relation table,
        // then try the current object's DB.
        if (!$query->tableExists($relTable)) {
          $query = $this->getDbAdaptor();
        }
      }
      return $query;
    }


    /**
     *  Requires a query object and will fetch related models with any parameters defined 
     *  on that query. If the attribute being fetched is not a model relationship, then it will 
     *  just execute the query and return the result.
     * 
     *  @return mixed
     */
    protected function _getCustomValue($attributeName, $query, $loadMap = false)
    {
      $def = $this->model_attributes[$attributeName];
      $result = $this->_getRelation($attributeName, $query, $loadMap); //$this->_getAttributeData($attributeName, $query, $loadMap);

      if (!$result) {
        $result = $query->fetch();
      }
      
      return $result;
    }


    /**
     *  Returns true or false depending on if the attribute specified is a relation to another model(s) or not.
     * 
     *  @return bool
     */
    protected function _isRelation($attributeName) 
    {
      // Grab attribute definition
      $def = $this->model_attributes[$attributeName];
      return isset($def['models']) || isset($def['model']);
    }


    /** 
     *  Returns related models. May return a single model, or a collection depending on if the relationship is 
     *  defined as singular or plural. If attribute is not a relationship, then will return false.
     * 
     *  @return Model or False
     */
    protected function _getRelation($attributeName, $query = false, $loadMap = false, $record = false)
    {
      // Grab attribute definition
      $def = $this->model_attributes[$attributeName];
      $result = false;
      
      if (isset($def['models'])) {
        $result = $this->_getModels($attributeName, $def['models'], $query, $loadMap, $record);
      } 
      
      else if (isset($def['model'])) {
        $result = $this->_getModel($attributeName, $def['model'], $query, $loadMap, $record);
      }

      return $result;
    }


    /**
     *  Uses an attribute's definition to correctly fetch a singular object from a singular relationship. 
     * 
     *  @param $attributeName string An attribute on the model that is defined as a singular model relationship.
     *  @return model
     */
    protected function _getModel($attributeName, $relatedObjName = false, $query = false, $loadMap = false, $record = false)
    {
      $def = $this->model_attributes[$attributeName];
      $result = null;

      if ($relatedObjName) {

        // If a LoadMap is present, and explicit fetching of the data isn't enabled, and some data was passed in,
        // then use the data given.
        if ($loadMap instanceof \Cora\Adm\LoadMap && !$loadMap->fetchData() && $record !== false) {
          
          // Create a blank object of desired type and populate it with the results of the data passed in
          $relatedObj = $this->fetchRelatedObj($def['model']);        
          $result = $relatedObj->_populate($record, $query, $loadMap);
        }

        // If fetching via a defined column on a table.
        else if (isset($def['via'])) {
          $result = $this->_getModelFromTableColumn($attributeName, $def['model'], $def['via'], $query, $loadMap);
        }
        
        // If custom defined relationship for this single model
        else if (isset($def['using'])) {
          $result = $this->getModelFromCustomRelationship($attributeName, $def['model'], $query, $loadMap);
        }

        // In the rare case that we need to fetch a single related object, and the developer choose
        // to use a relation table to represent the relationship.
        // It's abstract in the sense that there's nothing on the current model's table 
        // leading to it. We need to grab it using our method to grab data from a relation table.
        else if (isset($def['usesRefTable'])) {
          $result = $this->_getModelFromRelationTable($attributeName, $def['model'], $query, $loadMap);
        }

        // In the more common case of fetching a single object, where the related object's
        // ID is stored in a column on the parent object.
        // Under this scenario, the value stored in $this->$name is the ID of the related
        // object that was already fetched. So we can use that ID to populate a blank
        // object and then rely on it's dynamic loading to fetch any additional needed info.
        else {
          // Create a blank object of desired type
          $relatedObj = $this->fetchRelatedObj($def['model']);        
          
          // If a custom query was passed in, execute it
          // Then populate a model with the data result
          if ($query && $query->isCustom()) {
            $data = $query->fetch();
            $result = $relatedObj->_populate($data);
          }

          else {
            // If the Identifier is not already loaded from table, then get the ID so we can use it to 
            // fetch the model.
            if (!isset($this->model_data[$attributeName])) {
              $this->model_data[$attributeName] = $this->_fetchData($attributeName);
            }  

            // Fetch related object in whole (The model_data we have on it should be an ID reference)
            if (!is_object($this->model_data[$attributeName])) {
              $relObjRepo = $relatedObj->getRepository(true);
              $result = $relObjRepo->find($this->model_data[$attributeName]);
            } 
            
            // Unless we already have an object (maybe it was added to the model from the main app)
            // Then just use what we have
            else {
              $result = $this->model_data[$attributeName];
            }

            // Incase there's loadMap info that needs to be passed in, call populate
            if ($result) {
              $result->_populate([], false, $loadMap);
            }
          }
        }
      }
      return $result;
    }


    /**
     *  Uses an attribute's definition to correctly fetch a collection of models from a plural relationship. 
     * 
     *  @param $attributeName string An attribute on the model that is defined as a plural model relationship.
     *  @return Collection (populated with Models)
     */
    protected function _getModels($attributeName, $relatedObjName = false, $query = false, $loadMap = false)
    {
      $def = $this->model_attributes[$attributeName];
      $result = [];

      if ($relatedObjName) {
        // If the relationship is one-to-many.
        if (isset($def['via'])) {
          $result = $this->_getModelsFromTableColumn($attributeName, $relatedObjName, $def['via'], $query, $loadMap);
        }

        else if (isset($def['using'])) {
          $result = $this->getModelsFromCustomRelationship($attributeName, $relatedObjName, $query, $loadMap);
        }

        // If the relationship is many-to-many.
        // OR if the relationship is one-to-many and no 'owner' type column is set,
        // meaning there needs to be a relation table.
        else {
          $result = $this->_getModelsFromRelationTable($attributeName, $relatedObjName, $query, $loadMap);
        }
      }

      // If there is no data to return, return an empty collection
      if ($result == null) {
        $this->$attributeName = new \Cora\Collection();
        $result = $this->model_data[$attributeName];
      } 
      return $result;
    }


    /**
     *  Returns the appropriate value for when a model attribute needs to be fetched and there is a 
     *  cooresponding value in the model_data array.
     * 
     *  @requires $this->model_data[$attributeName] must be set.
     *  @param $attributeName string The name of the attribute value to be retrieved.
     *  @return mixed
     */
    protected function _getAttributeDataWhenSet($attributeName, $query = false, $loadMap = false, $record = false) 
    {
      // Check if the stored data is numeric.
      // If it's not, then we don't need to worry about it being a
      // class reference that we need to fetch.
      if (is_numeric($this->model_data[$attributeName])) {

        // If the attribute is defined as a model relationship, then the number is 
        // a placeholder or ID and needs to be converted into a model.
        if ($this->_isRelation($attributeName) && !isset($this->model_dynamicOff)) {
          $this->$attributeName = $this->_getRelation($attributeName, $query, $loadMap, $record);
        }
      }

      $this->beforeGet($attributeName); // Lifecycle callback
      $returnValue = $this->model_data[$attributeName];
      $this->afterGet($attributeName, $returnValue); // Lifecycle callback
      return $returnValue;
    }


    /**
     *  Returns the appropriate value for when a model attribute needs to be fetched and there is  
     *  NOT a cooresponding value in the model_data array.
     * 
     *  @requires $this->model_data[$attributeName] must NOT be set.
     *  @param $attributeName string The name of the attribute value to be retrieved.
     *  @return mixed
     */
    protected function _getAttributeDataWhenUnset($attributeName, $query = false, $loadMap = false, $record = false) 
    {
      // If the attribute isn't the primary key of our current model, do dynamic fetch.
      if ($attributeName != $this->getPrimaryKey()) {
            
        // If the attribute is defined as a model relationship, grab the model(s).
        if ($this->_isRelation($attributeName) && !isset($this->model_dynamicOff)) {
          $this->$attributeName = $this->_getRelation($attributeName, $query, $loadMap, $record);
        }
        
        // If the data is NOT a model and is located on this model's table and needs to be fetched
        else {
          $this->$attributeName = $this->_fetchData($attributeName);
        }
      }

      // If the data isn't set, and it IS the primary key, then need to set data to null
      // This is necessary to make sure that an entry exists in model_data for the field.
      else {
        $this->$attributeName = null;
      }

      $this->beforeGet($attributeName); // Lifecycle callback
      $returnValue = $this->model_data[$attributeName];
      $this->afterGet($attributeName, $returnValue); // Lifecycle callback
      return $returnValue;
    }


    /**
     *  Returns the appropriate value for some data on this model
     * 
     *  @param $name string The name of the data to be retrieved.
     *  @return mixed
     */
    protected function _getAttributeData($name, $query = false, $loadMap = false, $record = false) 
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
        return $this->_getAttributeDataWhenSet($name, $query, $loadMap, $record);
      }

      ///////////////////////////////////////////////////////////////////////
      // If the model DB data is defined, but not grabbed from the database,
      // then we need to dynamically fetch it.
      // OR, we need to return an empty collection or NULL
      // in the case of the attribute pointing to models.
      ///////////////////////////////////////////////////////////////////////
      if (isset($this->model_attributes[$name]) && !isset($this->model_dynamicOff)) {
        return $this->_getAttributeDataWhenUnset($name, $query, $loadMap, $record);
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
      if (isset($this->{$name})) {
        $returnValue = $this->{$name};
      } else {
        $returnValue = null;
      }
      $this->afterGet($name, $returnValue); // Lifecycle callback
      return $returnValue;
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


    protected function _getModelFromRelationTable($attributeName, $objName)
    {
      // Same logic as grabbing multiple objects, we just return the first (and only expected) result.
      return $this->_getModelsFromRelationTable($attributeName, $objName)->get(0);
    }


    protected function _getModelsFromRelationTable($attributeName, $objName, $query = false, $loadMap = false)
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

      // Get DB adaptor to use for relation.
      if (!$query) $query = $this->_getQueryObjectForRelation($attributeName);

      // Setup relation table field names 
      $relThis = isset($this->model_attributes[$attributeName]['relThis']) ? $this->model_attributes[$attributeName]['relThis'] : $className;
      $relThat = isset($this->model_attributes[$attributeName]['relThat']) ? $this->model_attributes[$attributeName]['relThat'] : $relatedClassName;
      
      // Check if both the Relation Table and the model being fetched use the same DB connection.
      // If they do, we can do a join to fetch the data without additional queries
      $relatedObjConnection = $relatedObj->getDbAdaptor()->connection;
      $relationTableConnection = $query->connection;

      // DEFAULT CASE 
      // The objects that are related aren't the same class of object...
      // (or they are, but relThis and relThat definitions were setup)
      if ($relThis != $relThat) {
        $query->select($relTable.'.'.$relThat.' as '.$relatedObj->getPrimaryKey())
              ->where($relThis, $this->$objectId);

        // Optionally JOIN data if on same connection
        if ($relatedObjConnection == $relationTableConnection) {
          $oTable = $relatedObj->getTableName();
          $query->select($oTable.'.*')
                ->join($oTable, [[$oTable.'.'.$relatedObj->getPrimaryKey(), '=', $relTable.'.'.$relThat]]);
        }

        return $repo->findAll($query, false, $loadMap);
      }
      
      // EDGE CASE 
      // The objects that are related ARE the same class of object...
      // If two Users are related to each other, can't have two "user" columns in the ref table. Instead 2nd column gets named "User2" 
      // TABLE: ref_users__example__users
      // COLUMNS:   User      |  User2 
      //            Bob's ID     Bob's relative's ID
      else {
        // Fetch related objects where the subject is the left side reference.
        $query->select($relTable.'.'.$relThat.'2'.' as '.$relatedObj->getPrimaryKey())
              ->where($relThis, $this->$objectId);

        // Optionally JOIN data if on same connection
        if ($relatedObjConnection == $relationTableConnection) {
          $oTable = $relatedObj->getTableName();
          $query->select($oTable.'.*')
                ->join($oTable, [[$oTable.'.'.$relatedObj->getPrimaryKey(), '=', $relTable.'.'.$relThat.'2']]);
        }
        $leftSet = $repo->findAll($query, false, $loadMap);

        // Fetch related objects where the subject is the right side reference.
        $query->select($relTable.'.'.$relThat.' as '.$relatedObj->getPrimaryKey())
              ->where($relThis.'2', $this->$objectId);

        // Optionally JOIN data if on same connection
        if ($relatedObjConnection == $relationTableConnection) {
          $oTable = $relatedObj->getTableName();
          $query->select($oTable.'.*')
                ->join($oTable, [[$oTable.'.'.$relatedObj->getPrimaryKey(), '=', $relTable.'.'.$relThat]]);
        }
        $rightSet = $repo->findAll($query, false, $loadMap);
        $leftSet->merge($rightSet);
        return $leftSet;
      }
    }


    protected function _getModelFromTableColumn($attributeName, $objName, $relationColumnName)
    {
      // Same logic as grabbing multiple objects, we just return the first (and only expected) result.
      return $this->_getModelsFromTableColumn($attributeName, $objName, $relationColumnName)->get(0);
    }


    /**
     *  The Related Obj's table should have some sort of 'owner' column
     *  for us to fetch by.
     */
    protected function _getModelsFromTableColumn($attributeName, $objName, $relationColumnName, $query = false, $loadMap = false)
    {
      // Figure out the unique identifying field of the model we want to grab.
      $relatedObj = $this->fetchRelatedObj($objName);
      $idField = $relatedObj->getPrimaryKey();

      //$relatedClassName = strtolower((new \ReflectionClass($relatedObj))->getShortName());
      $repo = \Cora\RepositoryFactory::make($objName, false, false, false, $this->model_db);

      // If no query object was passed in, then grab an appropriate one.
      if (!$query) $query = $this->_getQueryObjectForRelation($attributeName);

      // Set association condition
      $query->where($relationColumnName, $this->{$this->getPrimaryKey()});

      return $repo->findAll($query, false, $loadMap);
    }


    /**
     *  The Related Obj's are defined by a custom query. A "using" definition states which model 
     *  method defines the relationship. Within that method any query parameters must bet set and 
     *  a Query Builder object returned.
     */
     public function getModelsFromCustomRelationship($attributeName, $objName, $query = false, $loadMap = false)
     {
      // Create a repository for the related object.
      $repo = \Cora\RepositoryFactory::make($objName, false, false, false, $this->model_db);
      
      // Grab a Query Builder object for the connection this related model uses.
      // If no query object was passed in, then grab an appropriate one.
      if (!$query) $query = $this->_getQueryObjectForRelation($attributeName);

      // Grab the name of the method that defines the relationship
      $definingFunctionName = $this->model_attributes[$attributeName]['using'];
      
      // Pass query to the defining function
      $query = $this->$definingFunctionName($query);
      
      return $repo->findAll($query, false, $loadMap);
     }


    public function getModelFromCustomRelationship($attributeName, $objName, $query = false, $loadMap = false)
    {
      return $this->getModelsFromCustomRelationship($attributeName, $objName, $query, $loadMap)->get(0);
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


    /**
     *  Retrieves a single piece of data for the current model that is stored on this model's primary table.
     * 
     *  @return string
     */
    protected function _fetchData($name)
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
     *  Sets a property that controls whether LoadMaps should be taken into account when populating this model.
     *  There are situations when creating dummy objects in the process of saving a model that you want on-model 
     *  LoadMaps disabled.
     * 
     *  @return bool
     */
    public function setLoadMapsEnabled($bool) 
    {
      $this->model_loadMapsEnabled = $bool;
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
        else if (is_array($inputData) || $inputData instanceof \Traversable || $inputData instanceof \DateTime) {
            $object = new \stdClass;
            foreach ($inputData as $key => $data) {
                if ($data instanceof \Cora\Model) {
                    $object->$key = $data->toArray();
                } else {
                    $object->$key = $this->toArray($data);
                }
            }
            $resultArray = (array) $object;

            // If the first array key starts with an underscore such as "_item0"
            // Then assume it's a Cora Collection array and return a keyless array back.
            if (count($resultArray) && array_keys($resultArray)[0][0] == '_') {
              $resultArray = array_values($resultArray);
            }
            return $resultArray;
        }

        return utf8_encode($inputData);
    }

    public function toJson($inputData = false)
    {
        // If nothing was passed in, default to this object. 
        if ($inputData === false) {
            $inputData = $this;
        }
        
        return json_encode($this->toArray($inputData, true));
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

    public static function model_loadMap() 
    {
        return false;
    }

    public static function model_constraints($query) 
    {
        return $query;
    }
}
