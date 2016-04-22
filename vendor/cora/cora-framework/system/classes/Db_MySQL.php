<?php

class Db_MySQL extends DbAdaptor
{
    public function exec()
    {
        var_dump($this->updates);
    }
    
    
    protected function calculate()
    {
        // To be implemented by specific DB adaptor.
        throw new Exception('getQuery() calls calculate(), which needs to be implemented by a specific database adaptor!');
    }
}