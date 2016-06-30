<?php
namespace Cora;

class Db_MySQLResult extends DatabaseResult 
{
    public function fetch()
    {
        return $this->records->fetch(\PDO::FETCH_ASSOC);
    }
    
    public function fetchAll()
    {
        return $this->records->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }
}