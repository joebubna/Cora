<?php
namespace Cora;

class Database
{
    public static $defaultDb;
    public static $testingDb;

    protected $tables;
    protected $selects;
    protected $updates;
    protected $delete;
    protected $distinct;
    protected $wheres;
    protected $ins;
    protected $limit;
    protected $offset;
    protected $groupBys;
    protected $orderBys;
    protected $havings;
    protected $joins;
    protected $inserts;
    protected $values;
    protected $create;
    protected $primaryKeys;
    protected $foreignKeys;
    protected $indexes;
    protected $query;
    protected $queryDisplay;

    // For calculating total row from last query within LIMIT
    protected $last_wheres;
    protected $last_distinct;
    protected $last_ins;
    protected $last_groupBys;
    protected $last_havings;
    protected $last_joins;

    public function __construct()
    {
        $this->reset();
    }


    public function isSelectSet()
    {
        if(empty($this->selects))
            return false;
        return true;
    }

    public function resetSelect()
    {
        $this->selects  = array();
    }


    public function table($tables)
    {
        $this->storeValue('tables', $tables);
        return $this;
    }


    // Alias of $this->table
    public function from($tables)
    {
        $this->table($tables);
        return $this;
    }


    public function select($fields, $delim = false)
    {
        $this->storeValue('selects', $fields, $delim);
        return $this;
    }


    // Alias of $this->table
    public function update($tables)
    {
        $this->table($tables);
        return $this;
    }


    public function set($field, $value)
    {
        $this->store('keyValue', 'updates', $field, $value);
        return $this;
    }


    public function delete()
    {
        $this->delete = true;
        return $this;
    }


    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    /**
     *  WHERE array format:
        [
            [
                [
                    ['amount', '>', '1000'],
                    ['savings', '>', '100']
                ],
                'AND'
            ]
        ]
    */
    public function where($conditions, $value = false, $comparison = '=')
    {
        $this->store('condition', 'wheres', $conditions, $value, $comparison);
        return $this;
    }


    public function orWhere($conditions, $value = false, $comparison = '=')
    {
        $this->store('condition', 'wheres', $conditions, $value, $comparison, 'OR');
        return $this;
    }


    //public function in($column, $fields)
    public function in($conditions, $value = false, $comparison = 'IN')
    {
        $val = $value;
        if (!is_array($value)) {
            $val = explode(',', $val);
        }
        $this->store('condition', 'wheres', $conditions, $val, $comparison);
        return $this;
    }


    public function limit($num)
    {
        $this->limit = $num;
        return $this;
    }


    public function offset($num)
    {
        $this->offset = $num;
        return $this;
    }


    public function groupBy($fields)
    {
        $this->storeValue('groupBys', $fields);
        return $this;
    }


    public function orderBy($field, $direction = 'DESC')
    {
        $this->store('keyValue', 'orderBys', $field, $direction, '');
        return $this;
    }


    public function having($conditions, $value = false, $comparison = '=')
    {
        $this->store('condition', 'havings', $conditions, $value, $comparison);
        return $this;
    }


    public function orHaving($conditions, $value = false, $comparison = '=')
    {
        $this->store('condition', 'havings', $conditions, $value, $comparison, 'OR');
        return $this;
    }


    public function insert($columns)
    {
        $val = $columns;
        if (!is_array($val)) {
            $val = array_map('trim', explode(',', $val));
        }
        $this->storeValue('inserts', $val);
        return $this;
    }


    // Alias of $this->table
    public function into($table)
    {
        $this->table($table);
        return $this;
    }


    public function values($values)
    {
        $this->storeValue('values', $values);
        return $this;
    }


    public function create($table)
    {
        $this->create = $table;
        return $this;
    }


    public function field($name, $type, $attributes = ' ')
    {
        $this->store('keyValue', 'fields', $name, $attributes, $type);
        return $this;
    }


    public function primaryKey($columns) {
        $this->storeValue('primaryKeys', $columns);
        return $this;
    }


    public function foreignKey($column, $foreignTable, $foreignColumn)
    {
        $this->store('keyValue', 'foreignKeys', $column, $foreignColumn, $foreignTable);
        return $this;
    }


    public function index($columns) {
        $this->storeValue('indexes', $columns);
        return $this;
    }


    public function getQuery()
    {
        if ($this->query == '') {
            $this->calculate();
        }
        $this->queryDisplay = $this->query;
        $this->query = '';

        return $this->queryDisplay;
    }


    public function reset()
    {
        $this->tables   = array();
        $this->selects  = array();
        $this->updates  = array();
        $this->wheres   = array();
        $this->ins      = array();
        $this->groupBys = array();
        $this->orderBys = array();
        $this->havings  = array();
        $this->joins    = array();
        $this->inserts  = array();
        $this->values   = array();
        $this->fields   = array();
        $this->primaryKeys  = array();
        $this->foreignKeys  = array();
        $this->indexes  = array();

        $this->distinct = false;
        $this->delete   = false;
        $this->limit    = false;
        $this->offset   = false;
        $this->create   = false;
        $this->query    = '';
        $this->queryDisplay = '';
    }


    /**
        JOIN array format:
        [
            [
                table,
                [
                    conditions
                ],
                type
            ]
        ]
     */
    public function join($table, $conditions, $type = 'INNER')
    {
        $dataMember = &$this->joins;
        $item = [$table, $conditions, $type];
        array_push($dataMember, $item);
        return $this;
    }


    protected function store($type, $dataMember, $fields, $value = false, $comparison = '=', $conjunction = 'AND')
    {

        // If the data being stored DOES need its key.
        // E.g. adding WHERE field = value pairs to wheres array.
        if ($type == 'keyValue') {
            if ($value !== false) {
                $key = $fields;
                $this->storeKeyValue($dataMember, $value, $key, $comparison);
            }

            // If the value passed in is FALSE, then convert that into string 'NULL'.
            else {
                $value = 'NULL';
                $key = $fields;
                $this->storeKeyValue($dataMember, $value, $key, $comparison);
                //$this->storeKeyValue($dataMember, $fields);
            }
        }

        // If the data being stored is condition statements (WHERE, HAVING)
        else if($type == 'condition') {
            if ($value !== false) {
                $key = $fields;
                $this->storeCondition($dataMember, $value, $key, $comparison, $conjunction);
            }
            else {
                $this->storeCondition($dataMember, $fields, $conjunction);
            }
        }
    }

    /**
     *  For storing a single value or flat list of values.
     *  STORAGE FORMAT:
     *  [item1, item2, item3]
     */
     protected function storeValue($type, $data, $delim = false)
     {
         $dataMember = &$this->$type;
         // If array or object full of data was passed in, add all data
         // to appropriate data member.
         if (is_array($data) || is_object($data)) {
             foreach ($data as $value) {
                 if ($value !== false) {
                     if ($delim) {
                         $value = $delim.$value.$delim;
                     }
                 }
                 else {
                     $value = 'NULL';
                 }
                 array_push($dataMember, $value);
             }
         }

         // Add singular data item to data member.
         else {
             if ($data !== false) {
                 if ($delim) {
                     $data = $delim.$data.$delim;
                 }
             }
             else {
                 $data = 'NULL';
             }
             array_push($dataMember, $data);
         }
     }


    /**
     *  For storing an array of data that represents an item.
     *  STORAGE FORMAT:
     *  [
     *      [column, operator, value],
     *      [name, LIKE, %s],
     *      [price, >, 100]
     *  ]
     */
    protected function storeKeyValue($type, $data, $key = false, $comparison = false)
    {
        $dataMember = &$this->$type;
        // If array or object full of data was passed in, add all data
        // to appropriate data member.
        if (is_array($data) || is_object($data)) {
            foreach ($data as $item) {
                array_push($dataMember, $item);
            }
        }

        // Add singular data item to data member.
        else {
            $item = array($key, $comparison, $data);
            array_push($dataMember, $item);
        }
    }

    /**
     *  For storing an array of data that represents an item which needs a custom conjunction connecting them.
     *  STORAGE FORMAT:
     *  [
     *      [
     *          [
     *              [column, operator, value],
     *              [name, LIKE, %s],
     *              [price, >, 100]
     *          ],
     *          AND
     *      ]
     *  ]
     */
    protected function storeCondition($type, $data, $key = false, $comparison = false, $conjunction = false)
    {
        $dataMember = &$this->$type;
        // If array or object full of data was passed in, add all data
        // to appropriate data member.
        if ($comparison != 'IN' && (is_array($data) || is_object($data))) {
            $conj = $key;
            $condition = array($data, $conj);
            array_push($dataMember, $condition);
        }

        // Add singular data item to data member.
        else {
            $item = [array($key, $comparison, $data)];
            $condition = array($item, $conjunction);
            array_push($dataMember, $condition);
        }
    }


    // Just a convenience method to invoke a result method without having to call exec() first.
    public function fetch()
    {
        return $this->exec()->fetch();
    }


    // Just a convenience method to invoke a result method without having to call exec() first.
    public function fetchAll()
    {
        return $this->exec()->fetchAll();
    }


    public function exec()
    {
        // To be implemented by specific DB adaptor.
        throw new Exception('exec() needs to be implemented by a specific database adaptor!');
    }


    public function emptyDatabase()
    {
        // to be implemented by specific adaptor.
        throw new Exception('emptyDatabase() needs to be implemented by a specific database adaptor!');
    }


    protected function calculate()
    {
        // To be implemented by specific DB adaptor.
        throw new Exception('getQuery() calls calculate(), which needs to be implemented by a specific database adaptor!');
    }

    public function tableExists($name)
    {
        // Implemented by Adaptor.
        throw new Exception('tableExists() needs to be implemented by a specific database adaptor!');
    }

    public function startTransaction()
    {
        // Implemented by Adaptor.
        throw new Exception('startTransaction() needs to be implemented by a specific database adaptor!');
    }

    public function commit()
    {
        // Implemented by Adaptor.
        throw new Exception('commit() needs to be implemented by a specific database adaptor!');
    }

    public function rollback()
    {
        // Implemented by Adaptor.
        throw new Exception('rollback() needs to be implemented by a specific database adaptor!');
    }

    // Clean user provided input to make it safe for use in a database query.
    public function clean($value)
    {
         // Implemented by Adaptor.
        throw new Exception('clean() needs to be implemented by a specific database adaptor!');
    }


    public function getConnection($name)
    {
        return false;
    }


    public static function getDefaultConnectionName()
    {
        // Load Cora DB settings
        require(dirname(__FILE__).'/../config/config.php');
        require(dirname(__FILE__).'/../config/database.php');

        // Load app specific DB settings
        if (file_exists($config['basedir'].'cora/config/database.php')) {
            include($config['basedir'].'cora/config/database.php');
        }

        return $dbConfig['defaultConnection'];
        //return $dbConfig['connections'][$defaultConn]['adaptor'];
    }


    public static function getDefaultDb($getFresh = false, $existingConnection = false)
    {
        // Check if default DB instance is already created.
        if (isset(self::$defaultDb) && $getFresh == false && empty($GLOBALS['coraRunningTests'])) {
            return self::$defaultDb;
        }

        // Check if default TESTING DB instance is already created.
        if (isset(self::$testingDb) && $getFresh == false && !empty($GLOBALS['coraRunningTests'])) {
            return self::$testingDb;
        }

        // Load Cora DB settings
        require(dirname(__FILE__).'/../config/config.php');
        require(dirname(__FILE__).'/../config/database.php');

        // Load app specific DB settings
        if (file_exists($config['basedir'].'cora/config/database.php')) {
            include($config['basedir'].'cora/config/database.php');
        }

        if ($getFresh) {
            // Return a brand new default adaptor.
            $defaultConn = $dbConfig['defaultConnection'];
            $dbAdaptor = '\\Cora\\Db_'.$dbConfig['connections'][$defaultConn]['adaptor'];
            return new $dbAdaptor(false, $existingConnection, true);
        }
        else {
            // Use Default Adaptor as defined in the settings.
            $defaultConn = $dbConfig['defaultConnection'];
            $dbAdaptor = '\\Cora\\Db_'.$dbConfig['connections'][$defaultConn]['adaptor'];

            if (empty($GLOBALS['coraRunningTests'])) {
                self::$defaultDb = new $dbAdaptor(false, $existingConnection);
                return self::$defaultDb;
            } else {
                self::$testingDb = new $dbAdaptor(false, $existingConnection);
                return self::$testingDb;
            }
        }
    }

    public static function getDb($connectionName, $existingConnection = false)
    {
        // Load Cora DB settings
        require(dirname(__FILE__).'/../config/config.php');
        require(dirname(__FILE__).'/../config/database.php');

        // Load app specific DB settings
        if (file_exists($config['basedir'].'cora/config/database.php')) {
            include($config['basedir'].'cora/config/database.php');
        }

        $dbAdaptor = '\\Cora\\Db_'.$dbConfig['connections'][$connectionName]['adaptor'];
        return new $dbAdaptor($connectionName, $existingConnection);
    }

}
