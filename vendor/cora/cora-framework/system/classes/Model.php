<?php
namespace Cora;

/**
 *
 */
class Model 
{
    protected $model_data;
        
    public function _populate($record = null)
    {
        if($record) {
            foreach ($this->model_attributes as $key => $def) {
                
                // If the data is present in the DB, assign to model.
                // Otherwise ignore any data returned from the DB that isn't defined in the model.
                if (isset($record[$key])) {
                    if (\Cora\Gateway::is_serialized($record[$key])) {
                        $this->model_data[$key] = unserialize($record[$key]);
                    }
                    else {
                        $this->model_data[$key] = $record[$key];
                    }   
                }
                else if (isset($def['models']) || (isset($def['model']) && isset($def['usesRefTable']))) {
                    $this->model_data[$key] = 1;
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
    
    
    public function __get($name)
    {
        ///////////////////////////////////////////////////////////////////////
        // If the model DB data is already set.
        ///////////////////////////////////////////////////////////////////////
        if (isset($this->model_data[$name])) {
            
            // Check if the stored data is numeric.
            // If it's not, then we don't need to worry about it being a
            // class reference that we need to fetch.
            if (is_numeric($this->model_data[$name])) {
                
                // Ref this model's attributes in a shorter variable.
                $def = $this->model_attributes[$name];

                // If desired data is a reference to a singular object.
                if (isset($def['model'])) {
                    
                    // In the rare case that we need to fetch a single related object, and the developer choose 
                    // to use a relation table to represent the relationship.
                    if (isset($def['usesRefTable'])) {
                        //$refTable = isset($def['refTable']) ? $def['refTable'] : false;
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
                        $relatedObj->id = $this->model_data[$name];
                        $this->$name = $relatedObj;
                    }
                }
                
                // If desired data is a reference to a collection of objects.
                else if (isset($def['models'])) {
                    
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
            return $this->model_data[$name];
        }
        
        ///////////////////////////////////////////////////////////////////////
        // If the model DB data is defined, but not grabbed from the database,
        // then we need to dynamically fetch it.
        ///////////////////////////////////////////////////////////////////////
        else if (isset($this->model_attributes[$name])) {
            if ($name != $this->getPrimaryKey()) {
                $this->$name = $this->fetchData($name);       
                return $this->model_data[$name];
            }
            else {
                $this->$name = false;
                return false;
            }
        }
        
        ///////////////////////////////////////////////////////////////////////
        // If there is a defined property (non-DB related), return the data.
        ///////////////////////////////////////////////////////////////////////
        $class = get_class($this);
        if (property_exists($class, $name)) {
            echo $name;
            echo $this->{$name};
            return $this->{$name};
        }
        
        ///////////////////////////////////////////////////////////////////////
        // IF NONE OF THE ABOVE WORKED BECAUSE TRANSLATION FROM 'ID' TO A CUSTOM ID NAME
        // NEEDS TO BE DONE:
        // If your DB id's aren't 'id', but instead something like "note_id",
        // but you always want to be able to refer to 'id' within a class.
        ///////////////////////////////////////////////////////////////////////
        if ($name == 'id' && property_exists($class, 'id_name')) {
            if (isset($this->model_data[$this->id_name])) {
                return $this->model_data[$this->id_name];
            }
            return $this->{$this->id_name};
        }
        return null;
    }
    
    
    public function __set($name, $value)
    {
        // If a matching DB attribute is defined for this model.
        if (isset($this->model_attributes[$name])) {
            $this->model_data[$name] = $value;
        }
        
        // Otherwise if a plain model attribute is defined.
        else {
            $class = get_class($this);
            if (property_exists($class, $name)) {
                $this->{$name} = $value;
            }
        }
 
        // If your DB id's aren't 'id', but instead something like "note_id",
        // but you always want to be able to refer to 'id' within a class.
        if ($name == 'id' && property_exists(get_class($this), 'id_name')) {
            $id_name = $this->id_name;
            if (isset($this->model_attributes[$id_name])) {
                $this->model_data[$id_name] = $value;
            }
            else {
                $id_name = $this->id_name;
                $this->{$id_name} = $value;
            }
        }
    }
    
    /**
     *  For getting model data without triggering dynamic data fetching.
     */
    public function getAttributeValue($name)
    {
        if (isset($this->model_data[$name])) {
            return $this->model_data[$name];
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
        $repo = \Cora\RepositoryFactory::make($relatedClassName, false, $relTable);

        // Define custom query for repository.
        $db = $relatedObj->getDbAdaptor();
        $db ->select($relatedClassName.' as '.$classId)
            ->where($className, $this->$classId);
        return $repo->findByQuery($db);
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
        $repo = \Cora\RepositoryFactory::make($objName);
                        
        $db = $relatedObj->getDbAdaptor();
        $db->where($relationColumnName, $this->{$this->getPrimaryKey()});
        $db->select($idField);
        return $repo->findByQuery($db);
    }
    
    
    public function getClassName($class = false)
    {
        if ($class == false) { $class = $this; }
        return strtolower((new \ReflectionClass($class))->getShortName());
    }
    
    
    public function fetchRelatedObj($objFullName)
    {
        $objType = '\\'.$objFullName;
        return new $objType();
    }
    
    
    protected function fetchData($name)
    {   
        $gateway = new \Cora\Gateway($this->getDbAdaptor(), $this->getTableName(), $this->getPrimaryKey());
        return $gateway->fetchData($name, $this);
        
//        // If this model has no DB ID associated with it, then it's obviously not possible
//        // to dynamically fetch this value from the DB.
//        $primaryIdentifier = $this->getPrimaryKey();
//        if ($this->$primaryIdentifier == null) {
//            return null;
//        }
//        
//        $table = $this->getTableName();
//        $db = $this->getDbAdaptor();  
//        $db ->select($name)
//            ->from($this->getTableName())
//            ->where($primaryIdentifier, $this->{$primaryIdentifier});
//        $result = $db->fetch();
//        return $result[$name];
    }
    
    
    public function getDbAdaptor($freshAdaptor = false)
    {
        // If a specific DB Connection is defined for this model, use it.
        if (isset($this->model_connection)) {
            $dbAdaptor = '\\Cora\\Db_'.$this->model_connection;
            return new $dbAdaptor();
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
        return \Cora\RepositoryFactory::make(get_class($this));
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
        // If value of $class is 'WorkOrder\\Note' then $tableName will be 'work_orders_notes'.
        $namespaces = explode('\\', $classNamespace);
        $tableName = '';
        foreach ($namespaces as $namespace) {
            $tableName .= strtolower(preg_replace('/\B([A-Z])/', '_$1', str_replace('\\', '', $namespace))).'s_';
        }
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
                $result = $table1.'_'.$table2;
            }
            else {
                $result = $table2.'_'.$table1;
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
    
    
    public function delete()
    {
        return true;
    }
    
    
    public function save()
    {
        $repo = $this->getRepository();
        $repo->save($this);
    }
    
}