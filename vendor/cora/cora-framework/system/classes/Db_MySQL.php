<?php
namespace Cora;

class Db_MySQL extends Database
{
    protected $db;
    protected $mysqli;
    protected $dbName;
    
    public function __construct($connection = false)
    {
        parent::__construct();
        
        // Load and set cora config.
        require(dirname(__FILE__).'/../config/config.php');
        require(dirname(__FILE__).'/../config/database.php');
        
        // Load custom app config
        if (file_exists($config['basedir'].'cora/config/database.php')) {
            include($config['basedir'].'cora/config/database.php');
        }
        
        // If a connection was specified, use that. Otherwise use the default DB connection.
        if (!$connection) {
            $connection = $dbConfig['defaultConnection'];
        }
        
        // Set DB name
        $this->dbName = $dbConfig['connections'][$connection]['dbName'];
        
        // Create mysqli connection. This is needed for it's escape function to cleanse variable inputs.
        $this->mysqli = new \mysqli($dbConfig['connections'][$connection]['host'], 
                                    $dbConfig['connections'][$connection]['dbUser'], 
                                    $dbConfig['connections'][$connection]['dbPass'], 
                                    $dbConfig['connections'][$connection]['dbName']);
        
        // Create PDO object for doing our queries.
        $errorMode = null;
        if ($config['mode'] == 'development') {
            $errorMode = array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION);
        }
        $this->db = new \PDO(
            'mysql:host='.$dbConfig['connections'][$connection]['host'].';dbname='.$dbConfig['connections'][$connection]['dbName'], 
            $dbConfig['connections'][$connection]['dbUser'], 
            $dbConfig['connections'][$connection]['dbPass'], 
            $errorMode
        );
    }
    
    // Clean user provided input to make it safe for use in a database query.
    protected function clean($value)
    {
        return $this->mysqli->real_escape_string($value);
    }
    
    // Create SQL string and execute it on our database.
    public function exec()
    {
        $this->calculate();
        $result = $this->db->query($this->query);
        $this->reset();
        
        $dbResult = new Db_MySQLResult($result, $this->db);
        return $dbResult;
    }
    
    
    public function emptyDatabase()
    {
        $sql = "
                USE $this->dbName;
                SET FOREIGN_KEY_CHECKS = 0;
                SET GROUP_CONCAT_MAX_LEN=32768;
                SET @tables = NULL;
                SELECT GROUP_CONCAT('`', table_name, '`') INTO @tables
                  FROM information_schema.tables
                  WHERE table_schema = (SELECT DATABASE());
                SELECT IFNULL(@tables,'dummy') INTO @tables;

                SET @tables = CONCAT('DROP TABLE IF EXISTS ', @tables);
                PREPARE stmt FROM @tables;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
                SET FOREIGN_KEY_CHECKS = 1;
        ";
        
        $result = $this->db->query($sql);
        $this->reset();
        return $result;
    }
    
    
    // Create the SQL string from Database class raw data.
    protected function calculate()
    {
        // Determine Action
        $action = false;
        $actions = 0;
        if($this->delete) {
            $actions += 1;
            $action = 'DELETE';
        }
        if(!empty($this->inserts)) {
            $actions += 1;
            $action = 'INSERT';
        }
        if(!empty($this->updates)) {
            $actions += 1;
            $action = 'UPDATE';
        }
        if(!empty($this->selects)) {
            $actions += 1;
            $action = 'SELECT';
        }
        if(!empty($this->create)) {
            $actions += 1;
            $action = 'CREATE';
        }
        if ($actions > 1) {
            throw new \Exception("More than one query action specified! When using Cora's query builder class, only one type of query (select, update, delete, insert) can be done at a time.");
        }
        else {
            $calcMethod = 'calculate'.$action;
            $this->$calcMethod();
        }      
    }
    
    // Create a SELECT statement
    protected function calculateSELECT()
    {
        $this->query .= 'SELECT ';
        
        // If distinct
        if ($this->distinct) {
            $this->query .= ' DISTINCT ';
        }
                  
        // If SELECT
        $this->queryStringFromArray('selects', '', ', ');
        
        // Determine Table(s)
        $this->queryStringFromArray('tables', ' FROM ', ', ');
        
        // Join
        $this->joinStringFromArray('joins');
        
        // Where and IN
        $this->conditionStringFromArray('wheres', ' WHERE ', ' AND ');
        
        // GroupBy
        $this->queryStringFromArray('groupBys', ' GROUP BY ', ', ');
        
        // Having
        $this->conditionStringFromArray('havings', ' HAVING ', ' AND ');
        
        // OrderBy
        $this->queryStringFromArray('orderBys', ' ORDER BY ', ', ', false);
        
        // Limit
        if ($this->limit) {
            $this->query .= ' LIMIT '.$this->limit;
        }
        
        // Offset
        if ($this->offset) {
            $this->query .= ' OFFSET '.$this->offset;
        }
    }
    
    // Create an UPDATE statement.
    protected function calculateUPDATE()
    {
        $this->query .= 'UPDATE ';
        
        // Determine Table(s)
        $this->queryStringFromArray('tables', '', ', ');
        
        // SETs
        $this->queryStringFromArray('updates', ' SET ', ', ');
        
        // Where and IN
        $this->conditionStringFromArray('wheres', ' WHERE ', ' AND ');
        
        // OrderBy
        $this->queryStringFromArray('orderBys', ' ORDER BY ', ', ', false);
        
        // Limit
        if ($this->limit) {
            $this->query .= ' LIMIT '.$this->limit;
        }
    }
    
    // Create an INSERT statement.
    protected function calculateINSERT()
    {
        $this->query .= 'INSERT INTO ';
        
        // Determine Table(s)
        $this->queryStringFromArray('tables', '', ', ');
        
        // SETs
        if (!empty($this->inserts)) {
            $this->query .= ' (';
            $this->queryStringFromArray('inserts', '', ', ');
            $this->query .= ')';
        }
        
        // Values
        $this->valueStringFromArray('values', ' VALUES ', ', ');
    }
    
    // Create a DELETE statement.
    protected function calculateDELETE()
    {
        $this->query .= 'DELETE FROM ';
        
        // Determine Table(s)
        $this->queryStringFromArray('tables', '', ', ');

        // Where and IN
        $this->conditionStringFromArray('wheres', ' WHERE ', ' AND ');
        
        // OrderBy
        $this->queryStringFromArray('orderBys', ' ORDER BY ', ', ', false);
        
        // Limit
        if ($this->limit) {
            $this->query .= ' LIMIT '.$this->limit;
        }
    }
    
    // Create a CREATE statement
    protected function calculateCREATE()
    {
        $this->query .= 'CREATE TABLE IF NOT EXISTS ';
        $this->query .= $this->create.' (';
        $this->queryStringFromArray('fields', '', ', ', false, true);
        $this->primaryKeyStringFromArray('primaryKeys', ', CONSTRAINT ');
        $this->foreignKeyStringFromArray('foreignKeys', ', CONSTRAINT ');
        $this->indexStringFromArray('indexes', ', INDEX ');
        $this->query .= ')';
    }
    
    
    /**
     *  For outputting a string of the following form from the 'indexes' array in Database.
     *  INDEX idx_id_name (id, name)
     */
    protected function IndexStringFromArray($dataMember, $opening, $sep = ', ')
    {
        if (empty($this->$dataMember)) {
            return 0;
        }
        $this->query .= $opening;
        $constraintName = 'idx';
        $query = ' (';
        $count = count($this->$dataMember);
        for($i=0; $i<$count; $i++) {
            $item = $this->{$dataMember}[$i];
            $constraintName .= '_'.$item;
            $query .= $item;
            if ($count-1 != $i) {
                $query .= $sep;
            }
        }
        $query .= ')';
        $this->query .= $constraintName.' '.$query;
    }
    
    
    /**
     *  For outputting a string of the following form from the 'primaryKeys' array in Database.
     *  CONSTRAINT pk_id_name PRIMARY KEY (id, name)
     */
    protected function primaryKeyStringFromArray($dataMember, $opening, $sep = ', ')
    {
        if (empty($this->$dataMember)) {
            return 0;
        }
        $this->query .= $opening;
        $constraintName = 'pk';
        $query = ' PRIMARY KEY (';
        $count = count($this->$dataMember);
        for($i=0; $i<$count; $i++) {
            $item = $this->{$dataMember}[$i];
            $constraintName .= '_'.$item;
            $query .= $item;
            if ($count-1 != $i) {
                $query .= $sep;
            }
        }
        $query .= ')';
        $this->query .= $constraintName.' '.$query;
    }
    
    
    /**
     *  For outputting a string of the following form from the 'foreignKeys' array in Database.
     *  CONSTRAINT fk_name FOREIGN KEY (name) REFERENCES users (name)
     */
    protected function foreignKeyStringFromArray($dataMember, $opening, $sep = ', ')
    {
        if (empty($this->$dataMember)) {
            return 0;
        }
        //$query = '';
        $count = count($this->$dataMember);
        for($i=0; $i<$count; $i++) {
            $item = $this->{$dataMember}[$i];
            
            $this->query .= ', CONSTRAINT '.'fk_'.$item[0].' FOREIGN KEY ('.$item[0].') REFERENCES '.$item[1].' ('.$item[2].')';
            if ($count-1 != $i) {
                $this->query .= $sep;
            }
        }
    }
    
    
    /**
     *  Create a string from a single-demensional Database class array.
     *  It calls getArrayItem() for each item and expects the returned result to be a string.
     *  It assumes the following structure:
     *  [
     *      item1,
     *      item2,
     *      item3
     *  ]
     *  An item can be another array if getArrayItem() knows how to translate it into a string.
     */
    protected function queryStringFromArray($dataMember, $opening, $sep, $quote = true, $set = false)
    {
        if (empty($this->$dataMember)) {
            return 0;
        }
        $this->query .= $opening;
        $count = count($this->$dataMember);
        for($i=0; $i<$count; $i++) {
            
            // Is this a normal situation, or are we outputting SET values for table creation statement?
            if ($set == false) {
                $this->query .= $this->getArrayItem($dataMember, $i, $quote);
            }
            else {
                // If we are creating a table, then we need to execute a diff method
                // so we can do some type checking on the column value. I.E. 'varchar' = 'varchar[255]'
                $this->query .= $this->getSetItem($dataMember, $i, $quote);
            }
            
            if ($count-1 != $i) {
                $this->query .= $sep;
            }
        }
    }
    
    /**
     *  Converts field types to real values that match this adaptor's DB type.
     */
    protected function getSetItem($dataMember, $offset, $quote = true)
    {
        $item = $this->{$dataMember}[$offset];
        switch ($item[1]) {
            case 'varchar':
                $type = 'varchar(255)';
                break;
            default:
                $type = $item[1];
        }
        $this->{$dataMember}[$offset][1] = $type;
        
        return $this->getArrayItem($dataMember, $offset, $quote);
    }
    
    
    /**
     *  When given an array of the following format:
     *  [item1, item2, item3]
     *  This either returns a cleaned single item if "item" is a string,
     *  OR returns a composite string of several variables if "item" is an array.
     *
     *  Single Items Example:
     *  ['table1', 'table2', 'table3'] when getting FROM clause
     *
     *  Items as Array Example:
     *  In the case of Item being an array, it expects it to have exactly 3 values like so:
     *  [column, operator, value]
     *  These three offsets can be finagled to use whatever three pieces of data is appropriate.
     *  Some examples of this in use are:
     *  ['column', '', 'DESC'] when getting ORDER BY clause. Middle offset is not used and left as blank string.
     *  ['name', '=', 'John'] when getting SET column = value clause.
     */
    protected function getArrayItem($dataMember, $offset, $quote = true)
    {
        if(is_array($this->{$dataMember}[$offset])) {
            if (count($this->{$dataMember}[$offset]) == 3) {
                $item = $this->{$dataMember}[$offset];
                
                // If the value is string 'NULL', output without quotes and without cleaning.
                if ($item[2] === 'NULL') {
                    return $item[0].' '.$item[1]." ".$item[2]; 
                }
                else {
                    if($quote) {
                       return $item[0].' '.$item[1]." '".$this->clean($item[2])."'"; 
                    }
                    else {
                       return $item[0].' '.$item[1]." ".$this->clean($item[2]); 
                    } 
                }   
            }
            else {
                throw new \Exception("Cora's Query Builder class expects query components to be in an array with form [column, operator, value]");
            }
        }
        else {
            return $this->clean($this->{$dataMember}[$offset]);
        }
    }
    
    
    /**
     *  Create a string from a multi-demensional Database class array.
     *  It calls getArrayCondition() for each item and expects the returned result to be a string.
     *  THIS CLASS IS SPECIFICALLY FOR CONDITIONS (Where, Having).
     *  It assumes the following structure:
     *  [
     *      [
     *          [
     *              [column, operator, value],
     *              [name, LIKE, %s],
     *              [price, >, 100]
     *          ],
     *          AND
     *      ],
     *      [
     *              [column, operator, value, conjunction],
     *              [name, LIKE, %s, OR],
     *              [price, >, 100]
     *          ],
     *          AND
     *      ]
     *  ]
     */
    protected function conditionStringFromArray($dataMember, $opening, $sep)
    {
        if (empty($this->$dataMember)) {
            return 0;
        }
        $this->query .= $opening;
        $count = count($this->$dataMember);
        for($i=0; $i<$count; $i++) {
            $statement = $this->{$dataMember}[$i];
            $this->query .= '(';
            $sCount = count($statement[0]);
            for($j=0; $j<$sCount; $j++) {
                
                // See if a custom separator (conjuction) was set, and grab it.
                $customSep = $this->getArrayConditionSep($dataMember, $i, $j);
                
                $this->query .= $this->getArrayCondition($dataMember, $i, $j);
                if ($sCount-1 != $j) {
                    if ($customSep) {
                        $this->query .= $customSep;
                    }
                    else {
                        $this->query .= $sep;
                    }
                }
            }
            $this->query .= ')';
            if ($count-1 != $i) {
//                echo $dataMember;
//                var_dump($this->$dataMember);
//                var_dump($this->{$dataMember}[$i+1]);
//                echo $i;
                $this->query .= ' '.$this->{$dataMember}[$i+1][1].' ';
            }
        }
    }
    
    /**
     *  When given an array of the following format:
     *  [column, operator, value]
     *  It returns a composite string of the variables.
     *
     *  If any of the three offsets are missing, it with throw an exception.
     *  Some examples of this in use are:
     *  [$column, '=', $value] when getting WHERE or HAVING clause.
     *  [$column, 'IN', array()] when getting column IN array() clause.
     */
    protected function getArrayCondition($dataMember, $statementNum, $offset)
    {
        if (count($this->{$dataMember}[$statementNum][0][$offset]) >= 3) {
            $item = $this->{$dataMember}[$statementNum][0][$offset];
            $result = '';
            
            if ($item[1] == 'IN') {
                $searchArea = $item[2];
                
                // Check if searchArea is array...
                if (is_array($searchArea)) {
                    // Convert the array into a comma delimited string.
                    $searchArea = implode(', ', $searchArea);
                }
                
                // Return string of form 'COLUMN IN (value1, value2, ...)'
                $result = $item[0].' '.$item[1]." (".$this->clean($searchArea).")";
            }
            else {
                // Return string of form 'COLUMN >= VALUE'
                $result = $item[0].' '.$item[1]." '".$this->clean($item[2])."'";
            }
            return $result;
        }
        else {
            throw new \Exception("Cora's Query Builder class expects advanced query components to be in an array with form {column, operator, value [, conjunction]}");
        }
    }
    
    
    /**
     *  If the optional 4th array parameter denoting the desired conjunction for the next condition is set
     *  within a condition statement, then return that separator.
     *  E.g. the 'OR' below:
     *  [   
     *      ['id', '>', '100', 'OR'],
     *      ['name', 'LIKE', '%s']
     *  ]
     */
    protected function getArrayConditionSep($dataMember, $statementNum, $offset)
    {
        if (isset($this->{$dataMember}[$statementNum][0][$offset][3])) {
            return ' '.$this->{$dataMember}[$statementNum][0][$offset][3].' ';
        }
        return false;
    }
    
    
    /**
     *  Almost the same as queryStringFromArray() except that this adds parenthesis
     *  around the query piece and calls getArrayList() instead of getArrayItem().
     *  See queryStringFromArray() description.
     *  This is used for getting the INSERT VALUES in an insert statement.
     *
     *  E.g. VALUES ('bob', 'bob@gmail.com', 'admin'), ('john', 'john@gmail.com', 'user')
     *  From an array with the following format:
     *  [   
     *      ['bob', 'bob@gmail.com', 'admin'],
     *      ['john', 'john@gmail.com', 'user']
     *  ]
     *  For each sub-array it creates an insert expression.
     */
    protected function valueStringFromArray($dataMember, $opening, $sep, $quote = true)
    {
        if (empty($this->$dataMember)) {
            return 0;
        }
        $this->query .= $opening;
        $count = count($this->$dataMember);
        $result = '';
        $addParenthesis = false;
        for($i=0; $i<$count; $i++) {
            if (!is_array($this->{$dataMember}[$i])) {
                $addParenthesis = true;
            }
            $result .= $this->getValuesList($dataMember, $i);
            if ($count-1 != $i) {
                $result .= $sep;
            }
        }
        if ($addParenthesis) {
            $this->query .= '('.$result.')';
        }
        else {
            $this->query .= $result;
        }
    }
    
    /**
     *  Returns a string for the VALUES (value1, value2, value3), (value1, value2, value3), ...
     *  part of an INSERT statement.
     */
    protected function getValuesList($dataMember, $offset)
    {
        if(is_array($this->{$dataMember}[$offset])) {
            $items = $this->{$dataMember}[$offset];
            $count = count($items);
            $result = ' (';
            for($i=0; $i<$count; $i++) {
                $result .= "'".$this->clean($items[$i])."'";
                if ($count-1 != $i) {
                    $result .= ', ';
                }
            }
            $result .= ')';
            return $result;   
        }
        else {
            return "'".$this->clean($this->{$dataMember}[$offset])."'";
        }
    }
    
    
    /**
     *  For creating a JOIN string expression from the database class arrays holding JOIN
     *  data.
     *
     */
    protected function joinStringFromArray($dataMember, $sep = ' AND ')
    {
        if (empty($this->$dataMember)) {
            return 0;
        }
        $count = count($this->$dataMember);
        //var_dump($this->$dataMember);
        for($i=0; $i<$count; $i++) {
            $statement = $this->{$dataMember}[$i];
            $this->query .= ' '.$statement[2].' JOIN '.$statement[0].' ON ';
            $sCount = count($statement[1]);
            for($j=0; $j<$sCount; $j++) {
                $this->query .= $this->getArrayJoin($dataMember, $i, $j);
                if ($sCount-1 != $j) {
                    $this->query .= $sep;
                }
            }
        }
    }
    
    
    /**
     *  Returns a string in "table1.column = table2.column" format.
     *  This method differs from getArrayCondition() not only because it doesn't have to take
     *  into account IN operators and is simpler because of it, but mainly because it doesn't
     *  wrap the VALUE in "column = value" in parenthesis because the expected value field is a table reference.
     */
    protected function getArrayJoin($dataMember, $statementNum, $offset)
    {
        if (count($this->{$dataMember}[$statementNum][1][$offset]) == 3) {
            $item = $this->{$dataMember}[$statementNum][1][$offset];
            return $this->clean($item[0]).' '.$item[1]." ".$this->clean($item[2]);
        }
        else {
            throw new \Exception("Cora's Query Builder class expects advanced query components to be in an array with form [column, operator, value]");
        }
    }
    
    
    
    /**
     *  Return a data type.
     */
    public function getType($props)
    {
        $result = '';
        if (isset($props['type'])) {
            
            // If field is a string
            if ($props['type'] == 'varchar' || $props['type'] == 'string') {
                if (isset($props['size'])) {
                    $result = 'varchar('.$props['size'].')';
                }
                else {
                    $result = 'varchar(255)';
                }
            }
            
            // If field is an Int
            else if ($props['type'] == 'int' || $props['type'] == 'integer') {
                if (isset($props['size'])) {
                    $result = 'int('.$props['size'].')';
                }
                else {
                    $result = 'int';
                }
            }
            
            // If field is a float
            else if ($props['type'] == 'float' || $props['type'] == 'double') {
                if (isset($props['size']) && isset($props['precision'])) {
                    $result = 'float('.$props['size'].', '.$props['precision'].')';
                }
                else {
                    $result = 'float';
                }
            }
            
            // If field is a date
            else if ($props['type'] == 'date') {
                $result = 'date';
            }
            
            // If field is a datetime
            else if ($props['type'] == 'datetime') {
                $result = 'datetime';
            }
            
            // If field is an enum
            else if ($props['type'] == 'enum') {
                if (isset($props['enum'])) {
                    $result = 'ENUM('.$props['enum'].')';
                }
                else {
                    $result = "ENUM('default')";
                }
            }
            
            // If nothing matches, just try returning what was set.
            else {
                if (isset($props['size'])) {
                    $result = $props['type'].'('.$props['size'].')';
                }
                else {
                    $result = $props['type'];
                }
            }
        }
        else {
            return 'varchar(255)';
        }
        return $result;
    }
    
    
    /**
     *  Return a field's attributes
     */
    public function getAttributes($props)
    {
        $attr = '';
        
        if (isset($props['primaryKey'])) {
            $attr .= 'NOT NULL AUTO_INCREMENT ';
        }
        
        if (isset($props['defaultValue'])) {
            $attr .= "DEFAULT '".$props['defaultValue']."'";
        }
        
        return $attr;
    }
    
    
    /**
     *  Set an index if defined.
     */
    public function setIndex($key, $props)
    {
        if (isset($props['index'])) {
            $this->index($key);
        }
    }
    
    
    
    
    public function field($name, $type, $attributes = ' ')
    {   
        // Set MySQL quotes around field. If this doesn't get done, then certain named fields
        // such as 'group' will cause an error because they are reserved.
        $name = '`'.$name.'`';
        
        $this->store('keyValue', 'fields', $name, $attributes, $type);
        return $this;
    }
    
}