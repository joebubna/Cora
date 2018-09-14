<?php
namespace Cora;
/**
*
*/
class Gateway
{
	protected $db;
    protected $tableName;
    protected $idName;
    protected $app;
    protected $idFallback;
    protected $savedModelsList = [];
    protected $viewQuery = 0;

	public function __construct(Database $db, $tableName, $id)
	{
		$this->db = $db;
        $this->tableName = $tableName;

        if($id == false) {
            $id = 'id';
        }
        $this->idName = $id;

        $this->savedModelsList = &$GLOBALS['savedModelsList'];
	}


    public function viewQuery($bool)
    {
        $this->viewQuery = $bool;
    }


    public function fetchData($name, $object) {
        // If this model has no DB ID associated with it, then it's obviously not possible
        // to dynamically fetch this value from the DB.
        $primaryIdentifier = $this->idName;
        if ($object->$primaryIdentifier == null) {
            return null;
        }

        $db = $object->getDbAdaptor();
        $db ->select($name, '`')
            ->from($this->tableName)
            ->where($primaryIdentifier, $object->{$primaryIdentifier});

        if ($this->viewQuery) {
            echo $db->getQuery();
        }

        $result = $db->fetch();
        return $result[$name];
    }


    public function getDb()
    {
        return $this->db;
    }


    public function getTable()
    {
        return $this->tableName;
    }


	public function persist($model, $table = null, $id_name = null)
	{
        if (!$table) {
            $table = $this->tableName;
        }

        if (!$id_name) {
            $id_name = $this->idName;
        }

        if (is_numeric($model->{$id_name})) {
            $query = $this->getDb();
            $query->where($model->getFieldName($id_name), $model->{$id_name});
            if ($this->count($query)) {
                return $this->_update($model, $table, $id_name);
            }
        }
        return $this->_create($model, $table, $id_name);
	}


	public function fetch($id)
	{
        $this->db   ->select('*')
                    ->from($this->tableName)
                    ->where($this->idName, $id);

        if ($this->viewQuery) {
            echo $this->db->getQuery();
        }

        return $this->db->fetch();
	}


	public function fetchAll()
	{
        $this->db   ->select('*')
                    ->from($this->tableName);

        if ($this->viewQuery) {
            echo $this->db->getQuery();
        }
        
        return $this->db->fetchAll();
	}


    public function fetchBy($key, $value, $options)
	{
        $this->db   ->select('*')
                    ->from($this->tableName)
                    ->where($key, $value);

        if (isset($options['order_by'])) {
            $this->db->orderBy($options['orderBy'], $options['order']);
        }

        if (isset($options['limit'])) {
            $this->db->limit($options['limit']);
            if (isset($options['offset'])) {
                $this->db->offset($options['offset']);
            }
        }

        if ($this->viewQuery) {
            echo $this->db->getQuery();
        }

		return $this->db->fetchAll();
	}


    /**
     *  $query is an instance of a Cora database.
     */
	public function fetchByQuery($query)
	{
        if(!$query->isSelectSet()) {
            $query->select('*');
        }
        $query->from($this->tableName);
        
        if ($this->viewQuery) {
            echo $query->getQuery();
        }

        return $query->fetchAll();
	}


    /**
     *  $query is an instance of a Cora database.
     */
	public function count($query)
	{
        // If no partial query was passed in, use data-member db instance.
        if (!isset($query)) {
            $query = $this->db;
        }

        // Clear out any existing SELECT parameters.
        $query->resetSelect();

        // Establish COUNT
        $query->select('COUNT(*)');
        $query->from($this->tableName);

        if ($this->viewQuery) {
            echo $query->getQuery();
        }

        $result = $query->fetch();
        return array_pop($result);
	}


	public function countPrev()
	{
        $query = $this->db;

        // Restore last query's parameters.
        $query->resetToLastMinusLimit();

        // Establish COUNT
        $query->select('COUNT(*)');
        $query->from($this->tableName);

        if ($this->viewQuery) {
            echo $query->getQuery();
        }

        $result = $query->fetch();
        return array_pop($result);
	}


	public function delete($id)
	{
        $this->db   ->delete()
                    ->from($this->tableName)
                    ->where($this->idName, $id);

        if ($this->viewQuery) {
            echo $this->db->getQuery();
        }

        return $this->db->exec();
	}


	protected function _update($model, $table, $id_name)
	{
        $model->beforeSave(); // Lifecycle callback

        $this->db   ->update($table)
                    ->where($id_name, $model->{$id_name});

        // Mark this model as saved.
        if (!$this->getSavedModel($model)) {
            $this->addSavedModel($model);
        }

        // Define that no lock has been set by default. 
        $lockSet = false;
        $versionUpdateArray = &$GLOBALS['coraVersionUpdateArray'];
        
        foreach ($model->model_attributes as $key => $prop) {
            $modelValue = $model->getAttributeValue($key);
            $fieldName = $model->getFieldName($key);

            // Check if a lock is set on the currently iterated attribute. 
            // If so, add a new where condition to the update. 
            if (isset($prop['lock']) && $prop['lock'] == true) {
                if (strtolower($prop['type']) == 'int' || strtolower($prop['type']) == 'integer') {
                    // Add lock condition to update.
                    $this->db->where($fieldName, $modelValue, '<=');
                    $lockSet = true;

                    // Increase the value of the version field.
                    $modelValue += 1;
                    
                    // Update the existing object, so that if it's passed around and tries to get saved a 2nd time, 
                    // that an exception doesn't get thrown. 
                    $versionUpdateArray[] = [$model, $key, $modelValue];
                }
                else if (strtolower($prop['type']) == 'datetime') {
                    // Add lock condition to update.
                    $this->db->where($fieldName, $modelValue, '<=');
                    $lockSet = true;

                    // Increase the value of the Last Modified'ish timestamp to the current time.
                    $modelValue = new \DateTime("Y-m-d");

                    // Update the existing object, so that if it's passed around and tries to get saved a 2nd time, 
                    // that an exception doesn't get thrown. 
                    $versionUpdateArray[] = [$model, $key, $modelValue];
                }
            }

            if (isset($modelValue)) {

                /////////////////////////////////////////////////////////////////////////////////////////
                // If the data is a single Cora model object, then we need to create a new repository to
                // handle saving that object.
                /////////////////////////////////////////////////////////////////////////////////////////
                if (
                        is_object($modelValue) &&
                        $modelValue instanceof \Cora\Model &&
                        !isset($prop['models'])
                   )
                {
                    $relatedObj = $modelValue;
                    $repo = \Cora\RepositoryFactory::make('\\'.get_class($relatedObj), false, false, true);

                    // Check if this object has already been saved during this recursive call series.
                    // If not, save it.
                    $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                    if (!$this->getSavedModel($relatedObj)) {
                        if ($id) {
                            $this->addSavedModel($relatedObj);
                            $repo->save($relatedObj);
                        }
                        else {
                            $id = $repo->save($relatedObj);
                            $this->addSavedModel($relatedObj);
                        }
                    }

                    if ($model->usesRelationTable($relatedObj, $key)) {
                        $db = $repo->getDb();
                        $db2 = $model->getDbAdaptor(true);
                        $relTable = $model->getRelationTableName($relatedObj, $key, $prop);
                        $modelId = $model->{$model->getPrimaryKey()};
                        $modelName = $model->getClassName();
                        $relatedObjName = $relatedObj->getClassName();

                        // Delete the existing relation table entry if set 
                        $this->refDelete($key, $model, $relatedObj, $db, $db2, $relTable, $modelName, $modelId, $relatedObjName);

                        // Insert reference to this object in ref table
                        $this->refInsert($key, $model, $relatedObj, $db, $db2, $relTable, $modelName, $modelId, $relatedObjName, $id);

                    }
                    else if (!isset($prop['using'])) {
                        // The reference must be stored in the parent's table.
                        // So we just set the column to the new ID.
                        $this->db->set($fieldName, $id);
                    }
                }

                /////////////////////////////////////////////////////////////////////////////////////////
                // If the data is a set of objects and the definition in the model calls for a collection
                /////////////////////////////////////////////////////////////////////////////////////////
                else if (
                            is_object($modelValue) &&
                            ($modelValue instanceof \Cora\Collection) &&
                            isset($prop['models'])
                        )
                {
                    $collection = $modelValue;

                    // Create a repository for whatever objects are supposed to make up this resultset
                    // based on the model definition.
                    $objPath = isset($prop['models']) ? $prop['models'] : $prop['model'];
                    $relatedObjBlank = $model->fetchRelatedObj($objPath);
                    $repo = \Cora\RepositoryFactory::make('\\'.get_class($relatedObjBlank), false, false, true);

                    // If uses relation table
                    if ($model->usesRelationTable($relatedObjBlank, $key)) {
                        $db = $repo->getDb();
                        $db2 = $model->getDbAdaptor(true);
                        $relTable = $model->getRelationTableName($relatedObjBlank, $key, $prop);
                        $modelId = $model->{$model->getPrimaryKey()};
                        $modelName = $model->getClassName();
                        $relatedObjName = $relatedObjBlank->getClassName();

                        // Delete all existing relation table entries that match,
                        $this->refDelete($key, $model, $relatedObjBlank, $db, $db2, $relTable, $modelName, $modelId, $relatedObjName);

                        // Save each object in the collection
                        foreach ($collection as $relatedObj) {

                            // Check if this object has already been saved during this recursive call series.
                            // If not, save it.
                            $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                            if (!$this->getSavedModel($relatedObj)) {
                                if ($id) {
                                    // It has an ID, so add it to the savedModelsList before saving.
                                    $this->addSavedModel($relatedObj);
                                    $repo->save($relatedObj);
                                }
                                else {
                                    // It doesn't have an ID, so save first to get it an ID, then add to list.
                                    //$id = $repo->save($relatedObj);
                                    $id = $relatedObj->getRepository(true)->save($relatedObj);
                                    $this->addSavedModel($relatedObj);
                                }
                            }

                            // Insert reference to this object in ref table.
                            $this->refInsert($key, $model, $relatedObjBlank, $db, $db2, $relTable, $modelName, $modelId, $relatedObjName, $id);
                        }
                    }

                    // If uses Via column
                    else if ($model->usesViaColumn($relatedObjBlank, $key)) {
                        $db = $repo->getDb();
                        $objTable = $relatedObjBlank->getTableName();
                        $modelId = $model->{$model->getPrimaryKey()};

                        // Set all existing table entries to blank owner.
                        $db ->update($objTable)
                            ->set($prop['via'], 0)
                            ->where($prop['via'], $modelId)
                            ->exec();

                        // Save each object in the collection
                        foreach ($collection as $relatedObj) {

                            // Check if this object has already been saved during this recursive call series.
                            // If not, save it.
                            $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                            if (!$this->getSavedModel($relatedObj)) {
                                if ($id) {
                                    $this->addSavedModel($relatedObj);
                                    $repo->save($relatedObj);
                                }
                                else {
                                    $id = $repo->save($relatedObj);
                                    $this->addSavedModel($relatedObj);
                                }
                            }

                            // Update the object to have correct relation
                            $db ->update($objTable)
                                ->set($prop['via'], $modelId)
                                ->where($relatedObj->getPrimaryKey(), $id);

                            if ($this->viewQuery) {
                                echo $db->getQuery();
                            }

                            $db->exec();
                        }
                    }

                    // If is some sort of Abtract relationship
                    else {
                        // Save each object in the collection
                        foreach ($collection as $relatedObj) {
                            // Check if this object has already been saved during this recursive call series.
                            // If not, save it.
                            $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                            if (!$this->getSavedModel($relatedObj)) {
                                if ($id) {
                                    $this->addSavedModel($relatedObj);
                                    $repo->save($relatedObj);
                                }
                                else {
                                    $id = $repo->save($relatedObj);
                                    $this->addSavedModel($relatedObj);
                                }
                            }
                        }
                    }
                }

                /////////////////////////////////////////////////////////////////////////////////////////
                // If the data is an array, or object that got past the first two IFs,
                // then we need to serialize it for storage.
                /////////////////////////////////////////////////////////////////////////////////////////
                else if (is_array($modelValue) || is_object($modelValue)) {
                    $str = serialize($modelValue);
                    $this->db->set($fieldName, $str);
                }

                /////////////////////////////////////////////////////////////////////////////////////////
                // If just some plain data.
                // OR an abstract data reference (such as 'articles' => 1)
                /////////////////////////////////////////////////////////////////////////////////////////
                else {
                    // Check that this is actually a value that needs to be saved.
                    // It might just be a placeholder value for a model reference.
                    // If it's a placeholder, we don't want to do anything here.
                    if (!$model->isPlaceholder($key)) {
                        $this->db->set($fieldName, $modelValue);
                    }
                }
            }
        }

        $model->afterSave(); // Lifecycle callback

        if ($this->viewQuery) {
            echo $this->db->getQuery();
            echo "\n";
        }

        // execute statement.
        $result = $this->db->exec();

        // If affected rows was zero, then assume lock error. 
        if ($result->rowCount() == 0 && $lockSet == true) {
            throw new \Cora\LockException();
        }

        return $result->lastInsertId();
	}

    protected function _create($model, $table, $id_name)
	{
        $model->beforeCreate(); // Lifecycle callback
        $model->beforeSave(); // Lifecycle callback

        $columns = array();
        $values = array();

        $this->db->into($table);


        /////////////////////////////////////////////////////////////////////////////////////////////////
        // FIRST PASS
        // Determine data being stored directly in this objects table and construct its insert query.
        /////////////////////////////////////////////////////////////////////////////////////////////////
        foreach ($model->model_attributes as $key => $prop) {
            $modelValue = $model->getAttributeValue($key);

            // If the field in question is handling optimistic locking, make sure value isn't null.
            if (isset($prop['lock']) && $prop['lock'] == true) {
                if (strtolower($prop['type']) == 'int' || strtolower($prop['type']) == 'integer') {
                    if ($modelValue == null) {
                        $modelValue = 0;
                    }
                }
                else if (strtolower($prop['type']) == 'datetime') {
                    if ($modelValue == null) {
                        $modelValue = new \DateTime('Y-m-d');
                    }
                }
            }

            if (isset($modelValue)) {
                $fieldName = $model->getFieldName($key);

                /////////////////////////////////////////////////////////////////////////////////////////
                // If the data is a single Cora model object, skip handling it this pass.
                /////////////////////////////////////////////////////////////////////////////////////////
                if (
                        is_object($modelValue) &&
                        $modelValue instanceof \Cora\Model &&
                        !isset($prop['models'])
                   )
                {
                    // Do nothing.
                }
                /////////////////////////////////////////////////////////////////////////////////////////
                // If the data is a set of objects and the definition in the model calls for a collection
                /////////////////////////////////////////////////////////////////////////////////////////
                else if (
                            is_object($modelValue) &&
                            ($modelValue instanceof \Cora\Collection) &&
                            isset($prop['models'])
                        )
                {
                    // Do nothing.
                }

                // If the data is an array, then we need to serialize it for storage.
                else if (is_array($modelValue) || is_object($modelValue)) {
                    $str = serialize($modelValue);
                    $columns[]  = $fieldName;
                    $values[]   = $str;
                }

                // If just some plain data.
                // OR an abstract data reference (such as 'articles' => 1)
                else {
                    // Check that this is actually a value that needs to be saved.
                    // It might just be a placeholder value for a model reference.
                    if (!$model->isPlaceholder($key)) {

                        // Set data
                        $columns[]  = $fieldName;
                        $values[]   = $modelValue;
                    }
                }
            }
        }
        $this->db->insert($columns);
        $this->db->values($values);

        if ($this->viewQuery) {
            echo $this->db->getQuery();
        }
        //echo $this->db->getQuery()."<br>";
        $modelId = $this->db->exec()->lastInsertId();

        // Assign the database ID to the model if necessary
        if ($modelId) {
            $record['id'] = $modelId;
            $model->_populate($record);
        }
        

        // Mark this object as saved.
        if (!$this->getSavedModel($model)) {
            $this->addSavedModel($model);
        }


        /////////////////////////////////////////////////////////////////////////////////////////////////
        // SECOND PASS
        // Determine associated data stored in seperate tables. Now that the parent object was inserted
        // (thus giving us an ID for it), we can add the references that we weren't able to handle in the
        // first pass through.
        /////////////////////////////////////////////////////////////////////////////////////////////////
        foreach ($model->model_attributes as $key => $prop) {
            $modelValue = $model->getAttributeValue($key);
            if (isset($modelValue)) {
                $fieldName = $model->getFieldName($key);

                /////////////////////////////////////////////////////////////////////////////////////////
                // If the data is a single Cora model object, then we need to create a new repository to
                // handle saving that object.
                /////////////////////////////////////////////////////////////////////////////////////////
                if (
                        is_object($modelValue) &&
                        $modelValue instanceof \Cora\Model &&
                        !isset($prop['models'])
                   )
                {
                    $relatedObj = $modelValue;
                    $repo = \Cora\RepositoryFactory::make('\\'.get_class($relatedObj), false, false, true);

                    // Check if this object has already been saved during this recursive call series.
                    // If not, save it.
                    $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                    if (!$this->getSavedModel($relatedObj)) {
                        if ($id) {
                            $this->addSavedModel($relatedObj);
                            $repo->save($relatedObj);
                        }
                        else {
                            $id = $repo->save($relatedObj);
                            $this->addSavedModel($relatedObj);
                        }
                    }

                    if ($model->usesRelationTable($relatedObj, $key)) {
                        $db = $repo->getDb();
                        $db2 = $model->getDbAdaptor(true);
                        $relTable = $model->getRelationTableName($relatedObj, $key, $prop);
                        $modelName = $model->getClassName();
                        $relatedObjName = $relatedObj->getClassName();

                        // Delete the existing relation table entry if set 
                        $this->refDelete($key, $model, $relatedObj, $db, $db2, $relTable, $modelName, $modelId, $relatedObjName);

                        // Insert reference to this object in ref table. 
                        $this->refInsert($key, $model, $relatedObj, $db, $db2, $relTable, $modelName, $modelId, $relatedObjName, $id);
                    }
                    else if (!isset($prop['using'])) {
                        $this->db   ->update($table)
                                    ->set($fieldName, $id)
                                    ->where($model->getPrimaryKey(), $model->{$model->getPrimaryKey()});
                        $this->db->exec();
                    }

                }


                /////////////////////////////////////////////////////////////////////////////////////////
                // If the data is a set of objects and the definition in the model calls for a collection
                /////////////////////////////////////////////////////////////////////////////////////////
                else if (
                            is_object($modelValue) &&
                            ($modelValue instanceof \Cora\Collection) &&
                            isset($prop['models'])
                        )
                {
                    $collection = $modelValue;

                    // Create a repository for whatever objects are supposed to make up this resultset
                    // based on the model definition.
                    $objPath = isset($prop['models']) ? $prop['models'] : $prop['model'];
                    $relatedObjBlank = $model->fetchRelatedObj($objPath);
                    $repo = \Cora\RepositoryFactory::make('\\'.get_class($relatedObjBlank), false, false, true);

                    // If uses relation table
                    if ($model->usesRelationTable($relatedObjBlank, $key)) {
                        $db = $repo->getDb();
                        $db2 = $model->getDbAdaptor(true);
                        $relTable = $model->getRelationTableName($relatedObjBlank, $key, $prop);
                        $modelName = $model->getClassName();
                        $relatedObjName = $relatedObjBlank->getClassName();

                        // Delete all existing relation table entries that match 
                        $this->refDelete($key, $model, $relatedObjBlank, $db, $db2, $relTable, $modelName, $modelId, $relatedObjName);

                        // Save each object in the collection
                        foreach ($collection as $relatedObj) {

                            // Check if this object has already been saved during this recursive call series.
                            // If not, save it.
                            $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                            if (!$this->getSavedModel($relatedObj)) {
                                if ($id) {
                                    $this->addSavedModel($relatedObj);
                                    $repo->save($relatedObj);
                                }
                                else {
                                    $id = $repo->save($relatedObj);
                                    $this->addSavedModel($relatedObj);
                                }
                            }

                            // Insert reference to this object in ref table. 
                            $this->refInsert($key, $model, $relatedObjBlank, $db, $db2, $relTable, $modelName, $modelId, $relatedObjName, $id);
                        }
                    }

                    // If uses Via column
                    else if ($model->usesViaColumn($relatedObjBlank, $key)) {
                        $db = $repo->getDb();
                        $objTable = $relatedObjBlank->getTableName();
                        $modelId = $model->{$model->getPrimaryKey()};

                        // Set all existing table entries to blank owner.
                        $db ->update($objTable)
                            ->set($prop['via'], 0)
                            ->where($prop['via'], $modelId)
                            ->exec();

                        // Save each object in the collection
                        foreach ($collection as $relatedObj) {

                            // Check if this object has already been saved during this recursive call series.
                            // If not, save it.
                            $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                            if (!$this->getSavedModel($relatedObj)) {
                                if ($id) {
                                    $this->addSavedModel($relatedObj);
                                    $repo->save($relatedObj);
                                }
                                else {
                                    $id = $repo->save($relatedObj);
                                    $this->addSavedModel($relatedObj);
                                }
                            }

                            // Update the object to have correct relation
                            $db ->update($objTable)
                                ->set($prop['via'], $modelId)
                                ->where($relatedObj->getPrimaryKey(), $id);

                            if ($this->viewQuery) {
                                echo $db->getQuery();
                            }
                            //echo $db->getQuery();
                            $db->exec();
                        }
                    }

                    // If is some sort of Abtract relationship
                    else {
                        // Save each object in the collection
                        foreach ($collection as $relatedObj) {
                            // Check if this object has already been saved during this recursive call series.
                            // If not, save it.
                            $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                            if (!$this->getSavedModel($relatedObj)) {
                                if ($id) {
                                    $this->addSavedModel($relatedObj);
                                    $repo->save($relatedObj);
                                }
                                else {
                                    $id = $repo->save($relatedObj);
                                    $this->addSavedModel($relatedObj);
                                }
                            }
                        }
                    }
                }
            }
        }

        $model->afterCreate(); // Lifecycle callback
        $model->afterSave(); // Lifecycle callback

        // Return the ID of the created record in the db.
        return $modelId;
	}

    public static function is_serialized($value)
    {
        $unserialized = @unserialize($value);
        if ($value === 'b:0;' || $unserialized !== false) {
            return true;
        }
        else {
            return false;
        }
    }

    protected function refDelete($key, $model, $relatedObj, $db, $db2, $relTable, $modelName, $modelId, $relModelName)
    {
        //$dba = $db->tableExists($relTable) ? $db : $db2;

        // Set defaults
        $customFields = false;
        $primaryModel = $relatedObj;
        $dba = $db;
        $className = $relModelName;
        $relClassName = $modelName;

        // Check if passive side of relationship  
        // Adjust primary model if necessary
        if (isset($relatedObj->model_attributes[$key]['passive']) || isset($relatedObj->model_attributes[$key]) == false) {
            $primaryModel = $model;
            $dba = $db2;
            $className = $modelName;
            $relClassName = $relModelName;
        }

        // Check if custom field names set for relation table
        if (isset($primaryModel->model_attributes[$key]['relThis']) && isset($primaryModel->model_attributes[$key]['relThat'])) {
            $customFields = true;
            $className = $primaryModel->model_attributes[$key]['relThis'];
            $relClassName = $primaryModel->model_attributes[$key]['relThat'];
        }

        // GENERAL CASE
        // Delete related objects.
        $dba->delete()
            ->from($relTable)
            ->where($className, $modelId)
            ->exec();

        // EDGE CASE 
        // If the objects being related ARE the same type. I.E. a User being related to another User...
        // Then the reference might be in $modelName or $modelName.'2'...
        if ($modelName == $relModelName && $customFields == false) {
            $dba->delete()
                ->from($relTable)
                ->where($modelName.'2', $modelId)
                ->exec();
        }
    }

    protected function refInsert($key, $model, $relatedObj, $db, $db2, $relTable, $modelName, $modelId, $relModelName, $relModelId)
    {
        //$dba = $db->tableExists($relTable) ? $db : $db2;

        // Set defaults
        $customFields = false;
        $primaryModel = $relatedObj;
        $dba = $db;
        $className = $relModelName;
        $relClassName = $modelName;

        // Check if passive side of relationship  
        // Adjust primary model if necessary
        if (isset($relatedObj->model_attributes[$key]['passive']) || isset($relatedObj->model_attributes[$key]) == false) {
            $primaryModel = $model;
            $dba = $db2;
            $className = $modelName;
            $relClassName = $relModelName;
        }

        // Check if custom field names set for relation table
        if (isset($primaryModel->model_attributes[$key]['relThis']) && isset($primaryModel->model_attributes[$key]['relThat'])) {
            $customFields = true;
            $className = $primaryModel->model_attributes[$key]['relThis'];
            $relClassName = $primaryModel->model_attributes[$key]['relThat'];
        }

        // SIMPLE CASE
        // If the objects that are related aren't the same type of object...
        if ($modelName != $relModelName || $customFields == true) {
            $dba->insert([$className, $relClassName])
                ->into($relTable)
                ->values([$modelId, $relModelId])
                ->exec();
        }

        // EDGE CASE 
        // If the objects being related ARE the same type. I.E. a User being related to another User...
        else {
            $dba->insert([$modelName, $relModelName.'2'])
                ->into($relTable)
                ->values([$modelId, $relModelId])
                ->exec();
        }
    }

    protected function getModelSavedId($model)
    {
        $modelId    = $model->{$model->getPrimaryKey()};
        $modelName  = get_class($model);
        return $modelName.$modelId;
    }

    protected function getSavedModel($model)
    {
        return isset($this->savedModelsList[$this->getModelSavedId($model)]);
    }

    protected function addSavedModel($model)
    {
        $this->savedModelsList[$this->getModelSavedId($model)] = true;
    }
}
