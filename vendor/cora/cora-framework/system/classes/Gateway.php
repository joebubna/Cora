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
                    $repo = \Cora\RepositoryFactory::make(get_class($relatedObj), false, false, true);
                    $id = $repo->save($relatedObj);
                    
                    // If no new object was inserted into the DB, then that means we already had an ID.
                    if ($id == 0) {
                        $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                    }
                    
                    if ($model->usesRelationTable($relatedObj, $key)) {
                        $db = $repo->getDb();
                        
                        // Update the existing relation table entry to point to the new obj.
                        $relTable = $model->getRelationTableName($relatedObj, $prop);
                        $db ->update($relTable)
                            ->set($relatedObj->getClassName(), $id)
                            ->where($model->getClassName(), $model->{$model->getPrimaryKey()})
                            ->exec();       
                    }
                    else {
                        // The reference must be stored in the parent's table.
                        // So we just set the column to the new ID.
                        $this->db->set($key, $id);
                    }
                }
                
                /////////////////////////////////////////////////////////////////////////////////////////
                // If the data is a set of objects and the definition in the model calls for a collection
                /////////////////////////////////////////////////////////////////////////////////////////
                else if (
                            is_object($modelValue) && 
                            ($modelValue instanceof \Cora\ResultSet) &&
                            isset($prop['models'])
                        ) 
                {
                    $collection = $modelValue;
                    
                    // Create a repository for whatever objects are supposed to make up this resultset
                    // based on the model definition.
                    $objPath = isset($prop['models']) ? $prop['models'] : $prop['model'];
                    $relatedObjBlank = $model->fetchRelatedObj($objPath);
                    $repo = \Cora\RepositoryFactory::make(get_class($relatedObjBlank), false, false, true);
                    
                    // If uses relation table
                    if ($model->usesRelationTable($relatedObjBlank, $key)) {
                        $db = $repo->getDb();
                        $relTable = $model->getRelationTableName($relatedObjBlank, $prop);
                        $modelId = $model->{$model->getPrimaryKey()};
                        $modelName = $model->getClassName();
                        $relatedObjName = $relatedObjBlank->getClassName();

                        // Delete all existing relation table entries that match,
                        $db ->delete()
                            ->from($relTable)
                            ->where($modelName, $modelId)
                            ->exec();
                        
                        // Save each object in the collection
                        foreach ($collection as $relatedObj) {
                            
                            // If no new object was inserted into the DB, then that means the object 
                            // already had an ID.
                            $id = $repo->save($relatedObj);
                            if ($id == 0) {
                                $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                            }
                            
                            // Insert reference to this object in ref table.
                            $db ->insert([$modelName, $relatedObjName])
                                ->into($relTable)
                                ->values([$modelId, $id])
                                ->exec(); 
                        }    
                    }
                    
                    // If uses Via column
                    else {
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
                            
                            // If no new object was inserted into the DB, then that means the object 
                            // already had an ID.
                            $id = $repo->save($relatedObj);
                            if ($id == 0) {
                                $id = $relatedObj->{$relatedObj->getPrimaryKey()};
                            }
                            
                            // Update the object to have correct relation
                            $db ->update($objTable)
                                ->set($prop['via'], $modelId)
                                ->where($relatedObj->getPrimaryKey(), $id);
                            //echo $db->getQuery();
                            $db->exec();
                        }
                    }
                }
                
                /////////////////////////////////////////////////////////////////////////////////////////
                // If the data is an array, or object that got past the first two IFs,
                // then we need to serialize it for storage.
                /////////////////////////////////////////////////////////////////////////////////////////
                else if (is_array($modelValue) || is_object($modelValue)) {
                    $str = serialize($modelValue);
                    $this->db->set($key, $str);
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
