<?php 
namespace Cora;
/**
* 
*/
class RepositoryFactory
{

    public static function make($class, $idField = false, $table = false, $freshAdaptor = false, $db = false)
    {      
        // Create an instance of the class desired for this repo.
        $className = '\\'.$class;
        $classObj = new $className();
        
        // ---------------------------------------------------------------------------------
        // If no table name was passed in, then grab it as determined by the object.
        // ---------------------------------------------------------------------------------
        // Situations where the passed in table won't match the object's table are when
        // you are using a relation table to populate an object with just its ID.
        // I.E. creating an empty 'article' object by grabbing it's ID from 'users_articles'
        //
        $tableName = $table;
        if ($tableName == false) {
            $tableName = $classObj->getTableName();
        }
        
        // If a specific DB Adaptor was passed in (maybe for testing purposes), use that.
        // Otherwise, grab the correct DB Adaptor as defined for this model.
        // Also create the factory here as we don't want to pass anything in to it if no
        // specific database object was specified.
        if ($db == false) {
            $db = $classObj->getDbAdaptor($freshAdaptor);
            $factory = new Factory($class);
        }
        else {
            $factory = new Factory($class, $db);
        }
        
        // Creates the Gateway the repository will use.
        $gateway = new Gateway($db, $tableName, $idField);

        return new Repository($gateway, $factory);
    }
}