<?php 
namespace Cora;
/**
* 
*/
class RepositoryFactory
{

    public static function make($class, $idField = false, $table = false)
    {
        // Uses the class name to determine table name if one isn't given.
        // If value of $class is 'WorkOrder\\Note' then $tableName will be 'work_orders_notes'.
        $tableName = $table;
        if ($tableName == false) {
            $namespaces = explode('\\', $class);
            $tableName = '';
            foreach ($namespaces as $namespace) {
                $tableName .= strtolower(preg_replace('/\B([A-Z])/', '_$1', str_replace('\\', '', $namespace))).'s_';
            }
            $tableName = substr($tableName, 0, -1);
        }
        
        // Load Cora DB settings
        require(dirname(__FILE__).'/../config/config.php');
        require(dirname(__FILE__).'/../config/database.php');
        
        // Load app specific DB settings
        if (file_exists($config['basedir'].'cora/config/database.php')) {
            include($config['basedir'].'cora/config/database.php');
        }
        
        $class_vars = get_class_vars($class);
        if (isset($class_vars['model_connection'])) {
            $dbAdaptor = '\\Cora\\Db_'.$class_vars['model_connection'];
            $db = new $dbAdaptor();
        }
        else {
            $dbAdaptor = '\\Cora\\Db_'.$dbConfig['defaultConnection'];
            $db = new $dbAdaptor();
        }
        
        // Creates Factory and Gateway the repository will use.
        $factory = new Factory($class);
        $gateway = new Gateway($db, $tableName, $idField);

        return new Repository($gateway, $factory);
    }
}