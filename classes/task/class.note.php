<?php 
namespace Task;
/**
* 
*/
class Note extends \Note {
    
    public function __construct()
    {
        echo 'This is a task note.<br>';
    }

    public function foo()
    {
        return 'This is a task note.<br>';
    }

}