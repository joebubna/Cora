<?php
namespace Cora;

class Db_MySQL extends Database
{
    public function exec()
    {
        var_dump($this->updates);
    }
    
    
    protected function calculate()
    {
        // Determine Action
        $action = false;
        $actions = 0;
        if($this->delete) {
            $actions += 1;
            $action = 'DELETE';
        }
        if(!empty($this->inserts)) {
            $actions += 1;
            $action = 'INSERT';
        }
        if(!empty($this->updates)) {
            $actions += 1;
            $action = 'UPDATE';
        }
        if(!empty($this->selects)) {
            $actions += 1;
            $action = 'SELECT';
        }
        if ($actions > 1) {
            throw new Exception("More than one query action specified! When using Cora's query builder class, only one type of query (select, update, delete, insert) can be done at a time.");
        }
        else {
            $this->query .= $action.' ';
        }
            
        
        // If SELECT
        $this->queryStringFromArray('selects', '', ', ');
        
        // Determine Table(s)
        $this->queryStringFromArray('tables', ' FROM ', ', ');
        
        // Where
        $this->queryStringFromArray('wheres', ' WHERE ', 'AND ');
        
    }
    
    protected function queryStringFromArray($dataMember, $opening, $sep)
    {
        $this->query .= $opening;
        $count = count($this->$dataMember);
        for($i=0; $i<$count; $i++) {
            $this->query .= $this->getArrayItem($dataMember, $i);
            if ($count-1 != $i) {
                $this->query .= $sep;
            }
        }
    }
    
    protected function getArrayItem($dataMember, $offset)
    {
        if(is_array($this->$dataMember[$offset])) {
            if (count($this->$dataMember[$offset]) == 3) {
                $item = $this->$dataMember[$offset];
                return $item[0].' '.$item[1]."'".$item[2]."'";
            }
            else {
                throw new Exception("Cora's Query Builder class expects advanced query components to be in an array with form [column, operator, value]");
            }
        }
        else {
            return $this->$dataMember[$offset];
        }
    }
    
//    protected function queryValueStringFromArray($dataMember, $opening, $sep)
//    {
//        $this->query .= $opening;
//        $count = count($this->$dataMember);
//        for($i=0; $i<$count; $i++) {
//            if ($tableCount-1 == $i) {
//                $this->query .= $this->$dataMember[$i];
//            }
//            else {
//                $this->query .= $this->$dataMember[$i].$sep;
//            }
//        }
//    }
}