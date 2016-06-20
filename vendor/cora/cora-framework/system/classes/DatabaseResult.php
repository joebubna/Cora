<?php
namespace Cora;

class DatabaseResult 
{
    protected $records;
    
    public function __construct($records)
    {
        $this->records = $records;
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
}