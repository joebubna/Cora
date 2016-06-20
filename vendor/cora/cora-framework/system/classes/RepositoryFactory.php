<?php 
namespace Cora;
/**
* 
*/
class RepositoryFactory
{

    public static function make($db, $class, $idField = false, $table = false)
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
        
        // Creates Factory and Gateway the repository will use.
        $factory = new Factory($class);
        $gateway = new Gateway($db, $tableName, $idField);

        return new Repository($gateway, $factory);
    }
}