<?php
namespace Cora;

/**
 *
 */
class Model
{
    protected $model_data;
    public $model_db = false;
    public $model_dynamicOff;

    public function _populate($record = null, $db = false)
    {
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
                if (isset($record[$key])) {
                    if (\Cora\Gateway::is_serialized($record[$key])) {
                        $value = unserialize($record[$key]);
                        $this->beforeSet($key, $value); // Lifecycle callback
                        $this->model_data[$key] = $value;
                        $this->afterSet($key, $value); // Lifecycle callback
                    }
                    else if (isset($def['type']) && ($def['type'] == 'date' || $def['type'] == 'datetime')) {
                        $value = new \DateTime($record[$key]);
                        $this->beforeSet($key, $value); // Lifecycle callback
                        $this->model_data[$key] = $value;
                        $this->afterSet($key, $value); // Lifecycle callback
                    }
                    else {
                        $value = $record[$key];
                        $this->beforeSet($key, $value); // Lifecycle callback
                        $this->model_data[$key] = $value;
                        $this->afterSet($key, $value); // Lifecycle callback
                    }
                }
                else if (isset($def['models']) || (isset($def['model']) && isset($def['usesRefTable']))) {
                    $this->model_data[$key] = 1;
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
                    $this->$key = $value;
                }
            }
        }
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
        if ($this->$name != null) {
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
        // AmBlend allows fetching of only part of a model's data when fetching
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

                    // In the rare case that we need to fetch a single related object, and the developer choose
                    // to use a relation table to represent the relationship.
                    if (isset($def['usesRefTable'])) {
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
                        $relatedObj->_populate([$relatedObj->getPrimaryKey() => $this->model_data[$name]]);
                        $this->$name = $relatedObj;
                    }
                }

                // If desired data is a reference to a collection of objects.
                else if (isset($def['models']) && !isset($this->model_dynamicOff)) {

                    // If the relationship is one-to-many.
                    if (isset($def['via'])) {
                        $this->$name = $this->getModelsFromTableColumn($def['models'], $def['via']);
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
                $this->$name = $this->fetchData($name);
                $this->beforeGet($name); // Lifecycle callback
                $returnValue = $this->model_data[$name];
                $this->afterGet($name, $returnValue); // Lifecycle callback

                // Ref this model's attributes in a shorter variable.
                $def = $this->model_attributes[$name];

                // If the unset attribute is defined as a collection, return an empty one.
                if (isset($def['models'])) {
                    if ($returnValue == null) {
                        return new \Cora\Container();
                    }
                    else {
                        return $this->__get($name);
                    }
                }
                // If the unset attribute is defined as a single model, return null.
                else if (isset($def['model'])) {
                    if ($returnValue != null) {
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

        // Otherwise if a plain model attribute is defined.
        else {
            $this->{$name} = $value;
//            $class = get_class($this);
//            if (property_exists($class, $name)) {
//                $this->{$name} = $value;
//            }
        }

        // Lifecycle callback
        $this->afterSet($name, $value);
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
        $relTable = $this->getRelationTableName($relatedObj, $this->model_attributes[$attributeName]);
        $className = strtolower((new \ReflectionClass($this))->getShortName());
        $classId = $this->getPrimaryKey();
        $relatedClassName = strtolower((new \ReflectionClass($relatedObj))->getShortName());

        // Create repo that uses the relationtable, but returns models populated
        // with their IDs.
        $repo = \Cora\RepositoryFactory::make('\\'.get_class($relatedObj), false, $relTable, false, $this->model_db);

        // Define custom query for repository.
        $db = $relatedObj->getDbAdaptor();
        $db ->select($relatedClassName.' as '.$classId)
            ->where($className, $this->$classId);
        return $repo->findAll($db);
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
        $db->select($idField);
        return $repo->findAll($db);
    }


    public function getClassName($class = false)
    {
        if ($class == false) { $class = $this; }
        return strtolower((new \ReflectionClass($class))->getShortName());
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
        return $gateway->fetchData($name, $this);
    }


    public function getDbAdaptor($freshAdaptor = false)
    {
        // If a custom DB object was passed in, use that.
        if ($this->model_db) {
            return $this->model_db;
        }

        // else if a specific DB Connection is defined for this model, use it.
        else if (isset($this->model_connection)) {
            //$dbAdaptor = '\\Cora\\Db_'.$this->model_connection;
            //return new $dbAdaptor();
            return \Cora\Database::getDb($this->model_connection);
        }

        // If no DB Connection is specified...
        else {

            // If specified that we need to return a new adaptor instance, do it.
            // This becomes necessary when saving an object that has related objects attached to it.
            // The way Cora's Database class is built, it only handles one query at a time.
            // So if the Gateway starts building the query to save say a User to the database, but while
            // doing so encounters an Article owned by that user which needs to be saved, initiating a save
            // on that child object if it also uses the default DB adaptor will be problematic because it
            // will end up altering the query getting built up for the parent. The solution is to set
            // this fresh option and get a new Database instance.
            if ($freshAdaptor) {
                return \Cora\Database::getDefaultDb(true);
            }

            // else use the default defined in the config.
            // This references a static object for efficiency.
            else {
                return \Cora\Database::getDefaultDb();
            }
        }
    }


    public function getRepository()
    {
        return \Cora\RepositoryFactory::make('\\'.get_class($this));
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


    public function getRelationTableName($relatedObj, $attributeDef)
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

            if ($alphabeticalComparison > 0) {
                $result = 'ref_'.$table1.'_'.$table2;
            }
            else {
                $result = 'ref_'.$table2.'_'.$table1;
            }
        }
        return $result;
    }


    public function usesRelationTable($relatedObj, $attribute)
    {
        $def = $this->model_attributes[$attribute];
        if (isset($def['models']) && !isset($def['via'])) {
            return $this->getRelationTableName($relatedObj, $def);
        }
        else if (isset($def['model']) && isset($def['usesRefTable'])) {
            return $this->getRelationTableName($relatedObj, $def);
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


    public function save()
    {
        $repo = $this->getRepository();
        $repo->save($this);
    }


    public function delete()
    {
        return true;
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


    public function afterGet($prop, $value)
    {
        //echo __FUNCTION__;
    }
}
