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
            $modelValue = $model->$key;
            if (!empty($modelValue)) {
                $this->db->set($key, $modelValue);   
            }
        }
        
        return $this->db->exec();    
	}

    protected function _create($model, $table, $id_name)
	{
        $columns = array();
        $values = array();
        
        $this->db->into($table);
        
        foreach ($model->model_attributes as $key => $prop) {
            $modelValue = $model->$key;
            if (!empty($modelValue)) {
                $columns[]  = $key;
                $values[]   = $modelValue;
            }
        }
        $this->db->insert($columns);
        $this->db->values($values);
        return $this->db->exec();
	}
}
