<?php
namespace Cora;

class Database
{
    protected $tables;
    protected $selects;
    protected $updates;
    protected $delete;
    protected $wheres;
    protected $limit;
    protected $offset;
    protected $groupBys;
    protected $orderBys;
    protected $havings;
    protected $joins;
    protected $inserts;
    protected $query;
    
    public function __construct()
    {
        $this->reset();
    }
    
    
    public function table($tables)
    {
        $this->store('value', 'tables', $tables);
        return $this;
    }
    
    
    public function from($tables)
    {
        $this->table($tables);
        return $this;
    }
    
    
    public function select($fields)
    {
        $this->store('value', 'selects', $fields);
        return $this;
    }
    
    
    public function update($fields, $value = false)
    {
        $this->store('keyValue', 'updates', $fields, $value);
        return $this;
    }
    
    
    public function delete()
    {
        $this->delete = true;
        return $this;
    }
    
    
    public function where($conditions, $value = false, $comparison = false)
    {
        $this->store('keyValue', 'wheres', $conditions, $value, $comparison);
        return $this;
    }
    
    
    public function limit($num)
    {
        $this->limit = $num;
        return $this;
    }
    
    
    public function offset($num)
    {
        $this->offset = $num;
        return $this;
    }
    
    
    public function groupBy($fields)
    {
        $this->store('value', 'groupBys', $fields);
        return $this;
    }
    
    
    public function orderBy($field, $direction = 'DESC')
    {
        $this->store('keyValue', 'orderBys', $field, $direction);
        return $this;
    }
    
    
    public function having($fields, $value = false)
    {
        $this->store('keyValue', 'havings', $fields, $value);
        return $this;
    }
    
    
    public function set($field, $value)
    {
        $this->store('keyValue', 'updates', $field, $value);
        return $this;
    }
    
    
    public function join($tables)
    {
        $this->store('value', 'joins', $tables);
        return $this;
    }
    
    
    public function insert($data)
    {
        $this->store('keyValue', 'inserts', $data);
        return $this;
    }
    
    
    public function reset()
    {
        $this->tables   = array();
        $this->selects  = array();
        $this->updates  = array();
        $this->wheres   = array();
        $this->groupBys = array();
        $this->orderBys = array();
        $this->havings  = array();
        $this->joins    = array();
        $this->inserts  = array();
        
        $this->delete   = false;
        $this->limit    = false;
        $this->offset   = false;
        $this->query    = '';
    }
    
    
    public function getQuery()
    {
        $this->calculate();
        return $this->query;
    }
    
    
    protected function store($type, $dataMember, $fields, $value = false, $comparison = '=')
    {
        // If data being stored doesn't need its key.
        // E.g. adding 'tables' to a table array.
        if ($type == 'value') {
            $this->storeValue($dataMember, $fields);
        }
        
        // If the data being stored DOES need its key.
        // E.g. adding WHERE field = value pairs to wheres array.
        else if ($type == 'keyValue') {
            if ($value) {
                $key = $fields;
                $this->storeKeyValue($dataMember, $value, $key, $comparison);
            }
            else {
                $this->storeKeyValue($dataMember, $fields);
            }
        }
    }
    
    
    protected function storeValue($type, $data)
    {
        $dataMember = &$this->$type;
        // If array or object full of data was passed in, add all data
        // to appropriate data member.
        if (is_array($data) || is_object($data)) {
            foreach ($data as $value) {
                array_push($dataMember, $value);
            }
        }
        
        // Add singular data item to data member.
        else {
            array_push($dataMember, $data);
        }
    }
    
    
    protected function storeKeyValue($type, $data, $key = false, $comparison = false)
    {
        $dataMember = &$this->$type;
        // If array or object full of data was passed in, add all data
        // to appropriate data member.
        if (is_array($data) || is_object($data)) {
            foreach ($data as $item) {
                array_push($dataMember, $item);
            }
        }
        
        // Add singular data item to data member.
        else {
            $item = array($key, $comparison, $data);
            array_push($dataMember, $item);
        }
    }
    
    
//    protected function storeKeyValue($type, $data, $key = false, $comparison = false)
//    {
//        $dataMember = &$this->$type;
//        // If array or object full of data was passed in, add all data
//        // to appropriate data member.
//        if (is_array($data) || is_object($data)) {
//            foreach ($data as $key => $value) {
//                $dataMember[$key] = $value;
//            }
//        }
//        
//        // Add singular data item to data member.
//        else {
//            $dataMember[$key] = $data;
//        }
//    }
    
    
    public function exec()
    {
        // To be implemented by specific DB adaptor.
        throw new Exception('exec() needs to be implemented by a specific database adaptor!');
    }
    
    
    protected function calculate()
    {
        // To be implemented by specific DB adaptor.
        throw new Exception('getQuery() calls calculate(), which needs to be implemented by a specific database adaptor!');
    }
    
}