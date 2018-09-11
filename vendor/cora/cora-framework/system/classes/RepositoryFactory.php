<?php
namespace Cora;
/**
*
*/
class RepositoryFactory
{

    public static function make($class, $idField = false, $table = false, $freshAdaptor = false, $db = false)
    {
        // Load and set cora config.
        require(dirname(__FILE__).'/../config/config.php');

        // Load custom app config
        include($config['basedir'].'cora/config/config.php');

        // ---------------------------------------------------------------------------------
        // Create an instance of the class desired for this repo.
        // ---------------------------------------------------------------------------------

        // If the class is specified like '/Models/User' then do nothing special.
        if ($class[0] == '\\') {
            $className = $class;
        }

        // If the class starts with 'Models\something' but lacks leading slash.
        else if ('\\'.substr($class,0,6).'\\' == CORA_MODEL_NAMESPACE) {
            $className = '\\'.$class;
        }

        // If the class is specified like 'User', then prepend the model namespace to it.
        else {
            $className = CORA_MODEL_NAMESPACE.ucfirst($class);
        }

        //echo $className.'<br>';
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
            $factory = new Factory($className);
        }
        else {
            $factory = new Factory($className, $db);
        }

        // If no specific ID field was passed in, then grab from model.
        if ($idField == false) {
            $idField = $classObj->getPrimaryKey();
        }

        // Creates the Gateway the repository will use.
        $gateway = new Gateway($db, $tableName, $idField);

        //echo print_r($GLOBALS['savedModelsList']);
        return new Repository($gateway, $factory, $classObj);
    }
}
