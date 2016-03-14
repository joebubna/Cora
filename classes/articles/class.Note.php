<?php 
namespace Articles;
/**
* 
*/
class Note extends \Note {
    
    public function __construct() 
    {
        echo 'This is a website note.<br>';
    }

    public function foo()
    {
        return 'This is a task note.<br>';
    }

}