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

	public function __construct(Database $db, $tableName, $id)
	{
		$this->db = $db;
        $this->tableName = $tableName;
        
        if($id == false) {
            $id = 'id';
        }
        $this->idName = $id;
	}
    
    
    public function getDb()
    {
        return $this->db;
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
        	return $this->_update($model, $table, $id_name);
        }
        return $this->_create($model, $table, $id_name);
	}

    
	public function fetch($id)
	{
        $this->db   ->select('*')
                    ->from($this->tableName)
                    ->where($this->idName, $id);
        
        return $this->db->fetch();           
	}

    
	public function fetchAll()
	{
		$this->db->query("SELECT * FROM {$this->tableName}");
		return $this->db->resultset();
        
        $this->db   ->select('*')
                    ->from($this->tableName);
        
        return $this->db->fetchAll();
	}
    
    
    public function fetchBy($key, $value, $options)
	{
        $this->db   ->select('*')
                    ->from($this->tableName);
        
        if (isset($options['order_by'])) {
            $this->db->orderBy($options['orderBy'], $options['order']);
        }

        if (isset($options['limit'])) {
            $this->db->limit($options['limit']);
            if (isset($options['offset'])) {
                $this->db->offset($options['offset']);
            }
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
        
        return $query->fetchAll();
	}


	public function delete($id)
	{
        $this->db   ->delete()
                    ->from($this->tableName)
                    ->where($this->idName, $id);
        
        return $this->db->exec();
	}

    
	protected function _update($model, $table, $id_name)
	{
        $this->db   ->update($table)
                    ->where($id_name, $model->{$id_name});
        
        foreach ($model->model_attributes as $key => $prop) {
            $modelValue = $model->getAttributeValue($key);
            if (isset($modelValue)) {
                
                // If the data is a Cora model object, then we need to create a new repository to
                // handle saving that object.
                if (
                        is_object($modelValue) && 
                        $modelValue instanceof \Cora\Model &&
                        $model->attributeIsCollection($key)
                   ) 
                {
                    $relatedObj = $modelValue;
                    $repo = \Cora\RepositoryFactory::make(get_class($relatedObj), false, false, true);
                    $id = $repo->save($relatedObj);
                    
                    // If no new object was inserted into the DB, then that means we already had an ID.
                    if ($id == 0) {
                        $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                    }
                    
                    if ($model->usesRelationTable($relatedObj, $key)) {
                        $db = $repo->getDb();

                        // If a plural reference
                        if (isset($prop['models'])) {
                            throw new \Exception('Trying to assign a single object to what is supposed to be a collection of objects. Either add the object to the existing collection or make a new collection with just this item before doing assignment!');
//                            $relTable = $model->getRelationTableName($relatedObj, $prop);
//                            $modelId = $model->{$model->getPrimaryKey()};
//                            $modelName = $model->getClassName();
//                            $relatedObjName = $relatedObj->getClassName();
//                            
//                            // Unassign all existing relation table entries that match,
//                            $db ->update($relTable)
//                                ->set($relatedObjName, 0)
//                                ->where($modelName, $modelId)
//                                ->exec();
//                            
//                            // and then insert this new object as the sole entry.
//                            $db ->insert([$modelName, $relatedObjName])
//                                ->into($relTable)
//                                ->values($modelId, $id)
//                                ->exec();
                        }

                        // If a singular reference
                        else {
                            // Update the existing relation table entry to point to the new obj.
                            $relTable = $model->getRelationTableName($relatedObj, $prop);
                            $db ->update($relTable)
                                ->set($relatedObj->getClassName(), $id)
                                ->where($model->getClassName(), $model->{$model->getPrimaryKey()})
                                ->exec();
                        }           
                    }
                    else if ($model->usesViaColumn($modelValue, $key)) {
                        // Delete all existing table entries with this owner and insert
                        // a new row for this item.
                        throw new \Exception('Trying to assign a single object to what is supposed to be a collection of objects. Either add the object to the existing collection or make a new collection with just this item before doing assignment!');
                    }
                    else {
                        // The reference must be stored in the parent's table.
                        // So we just set the column to the new ID.
                        $this->db->set($key, $id);
                    }
                }
                
                // If the data is a set of objects.
                else if (
                            is_object($modelValue) && 
                            ($modelValue instanceof \Cora\ResultSet) &&
                            $model->attributeIsCollection($key)
                        ) 
                {
                    echo 'Is Collection';
                    $collection = $modelValue;
                    
                    // Create a repository for whatever objects are supposed to make up this resultset
                    // based on the model definition.
                    $objPath = isset($prop['models']) ? $prop['models'] : $prop['model'];
                    $relatedObjBlank = $model->fetchRelatedObj($objPath);
                    $repo = \Cora\RepositoryFactory::make(get_class($relatedObjBlank), false, false, true);
                    
                    
                    foreach ($collection as $relatedObj) {
                        $id = $repo->save($relatedObj);
                    
                        // If no new object was inserted into the DB, then that means we already had an ID.
                        if ($id == 0) {
                            $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                        }
                    }
                    
                    
                    
                    if ($model->usesRelationTable($relatedObj, $key)) {
                        $db = $repo->getDb();

                        // If a plural reference
                        if (isset($prop['models'])) {
                            $relTable = $model->getRelationTableName($relatedObj, $prop);
                            $modelId = $model->{$model->getPrimaryKey()};
                            $modelName = $model->getClassName();
                            $relatedObjName = $relatedObj->getClassName();
                            
                            // Unassign all existing relation table entries that match,
                            $db ->update($relTable)
                                ->set($relatedObjName, 0)
                                ->where($modelName, $modelId)
                                ->exec();
                            
                            // and then insert this new object as the sole entry.
                            $db ->insert([$modelName, $relatedObjName])
                                ->into($relTable)
                                ->values($modelId, $id)
                                ->exec();
                        }

                        // If a singular reference
                        else {
                            // Update the existing relation table entry to point to the new obj.
                            throw new \Exception('Trying to save a collection of objects to an attribute ('.$key.' in '.$model->getClassName().') that is supposed to be a single object! Refer to your Cora model definition. Attribute defined to be a single object stored in a reference table.');
                        }           
                    }
                    else if ($model->usesViaColumn($modelValue, $key)) {
                        // Delete all existing table entries with this owner and insert
                        // a new row for this item.
                    }
                    else {
                        // A single reference stored in the parent's table.
                        throw new \Exception('Trying to save a collection of objects to an attribute ('.$key.' in '.$model->getClassName().') that is supposed to be a single object! Refer to your Cora model definition. Attribute defined to be a single object reference stored in a column on the parent.');
                    }
                }
                
                // If the data is an array, then we need to serialize it for storage.
                else if (is_array($modelValue) || is_object($modelValue)) {
                    $str = serialize($modelValue);
                    $this->db->set($key, $str);
                }
                
                // If just some plain data.
                // OR an abstract data reference (such as 'articles' => 1)
                else {
                    // Check that this is actually a value that needs to be saved.
                    // It might just be a placeholder value for a model reference.
                    if (!$model->isPlaceholder($key)) {
                        $this->db->set($key, $modelValue);
                    }
                }  
            }
        }
        
        return $this->db->exec()->lastInsertId();    
	}

    protected function _create($model, $table, $id_name)
	{
        $columns = array();
        $values = array();
        
        $this->db->into($table);
        
        foreach ($model->model_attributes as $key => $prop) {
            //$modelValue = $model->$key;
            $modelValue = $model->getAttributeValue($key);
            if (isset($modelValue)) {
                // If the data is a Cora model object, then we need to create a new repository to
                // handle saving that object.
                if (is_object($modelValue) && ($modelValue instanceof \Cora\Model)) {
                    $repo = \Cora\RepositoryFactory::make(get_class($modelValue), false, false, true);
                    $id = $repo->save($modelValue);
                    
                    // If a new object was inserted into the DB, then need to update the reference on the 
                    // parent with the new item's ID.
                    if ($id != 0) {
                        $columns[]  = $key;
                        $values[]   = $id;
                    }
                }
                
                // If the data is an array, then we need to serialize it for storage.
                else if (is_array($modelValue) || is_object($modelValue)) {
                    $str = serialize($modelValue);
                    $columns[]  = $key;
                    $values[]   = $str;
                }
                
                // If just some plain data.
                // OR an abstract data reference (such as 'articles' => 1)
                else {
                    // Check that this is actually a value that needs to be saved.
                    // It might just be a placeholder value for a model reference.
                    if (!$model->isPlaceholder($key)) {
                        $columns[]  = $key;
                        $values[]   = $modelValue;
                    }
                }  
            }
        }
        $this->db->insert($columns);
        $this->db->values($values);
        
        return $this->db->exec()->lastInsertId();
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
}
