<?php 
namespace Cora;
/**
* 
*/
class RepositoryFactory
{

    public static function make($class, $idField = false, $table = false, $freshAdaptor = false)
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
        
        // Grab the correct DB Adaptor as defined for this model.
        $db = $classObj->getDbAdaptor($freshAdaptor);
        
        // Creates Factory and Gateway the repository will use.
        $factory = new Factory($class);
        $gateway = new Gateway($db, $tableName, $idField);

        return new Repository($gateway, $factory);
    }
}