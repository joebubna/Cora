<?php
namespace Cora;

class DatabaseResult 
{
    protected $records;
    protected $db;
    
    public function __construct($records, $dbObj)
    {
        $this->records = $records;
        $this->db = $dbObj;
    }
    
    // Must be implemented by an adaptor.
    public function fetch()
    {
        return false;
    }
    
    // Must be implemented by an adaptor.
    public function fetchAll()
    {
        return false;
    }
    
    // Must be implemented by an adaptor.
    // Must return 0 if no new record insert occured.
    public function lastInsertId()
    {
        return false;
    }
    
    public function rowCount()
    {
        return false;
    }
    
    // If there's any kind of PDO object that contains the result, return that.
    public function result()
    {
        return false;
    }
}