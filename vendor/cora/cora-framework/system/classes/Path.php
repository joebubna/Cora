<?php
namespace Cora;
/**
* 
*/
class Path
{
    ///////////////////////////////////////////////
    // Path Template
    ///////////////////////////////////////////////
    /** 
    *  This variable defines the user-friendly format for specifying custom URL paths.
    *  URL variables should be a name between brackets.
    *
    *  Example: $this->url = 'users/{action}-{subaction}/{id}'
    */
    public $url = '';

    ///////////////////////////////////////////////
    // Template variables definition
    ///////////////////////////////////////////////
    /** 
    *   This is for specifying custom rules for the bracket variables defined in a custom path template. 
    *   Rules should use regex syntax.
    *  
    *   Example: $this->def['{id}'] = '[0-9]+';
    */
    public $def = [];

    ///////////////////////////////////////////////
    // Route to execute if URL matches format
    ///////////////////////////////////////////////
    /** 
    *   Below is route to execute if the URL matches the defined pattern.
    *   Can be left undefined if you just want to use the preExec function and 
    *   execute automatic routing if the preExec returns true.
    */
    public $route = false;

    ///////////////////////////////////////////////
    // Pre-execution function
    ///////////////////////////////////////////////
    /** 
    *   If you want to do some permission checking before executing a route. 
    *   If this returns TRUE, then route executes, if FALSE, 
    *   then returns access denied.
    */
    //public $preExec;

    public function __construct() 
    {
        // Setting default preExec function.
        $this->preExec = function() {
            return true;
        };
    }

    public function preExecCheck($c = false) 
    {
        $preExec = $this->preExec;
        return $preExec();
    }
}