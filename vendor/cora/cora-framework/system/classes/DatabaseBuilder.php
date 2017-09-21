<?php
namespace Cora;

class DatabaseBuilder extends Framework
{
    protected $modelPath;
    public $displayOutput;
    public $newline = "\n";
    
    public function __construct($displayOutput = true)
    {
        parent::__construct();
        
        $this->modelPath = realpath($this->config['pathToModels']);
        $this->displayOutput = $displayOutput;
    }
    
    public function reset($connection = false)
    {
        $this->dbEmpty($connection);
        $this->dbBuild();
    }
    
    public function dbEmpty($connection = false)
    {
        if ($this->config['mode'] == 'development') {
            $db = false;
            if ($connection == false) {
                $db = \Cora\Database::getDefaultDb();
            }
            else {
                $db = \Cora\Database::getDb($connection);
            }
            $db->emptyDatabase();
        }
        else {
            echo 'Database manipulation is turned off for projects not in "development" mode. If you want to use the Database Builder on this project, edit the config "mode" setting and change it to "development"';
        }
    }
    
    public function dbBuild($data = '')
    {
        if ($this->config['mode'] == 'development') {
            // Call recursive processing of models to build DB.
            $this->examine('');

            // If no datafile name was passed in, set default.
            if ($data == '') {
                $data = 'data.php';
            }

            // Determine path
            $path = $this->config['basedir'].'includes/'.$data;

            // If a data PHP file to include exists, include it.
            if (file_exists($path)) {
                include($path);
            }
        }
        else {
            echo 'Database manipulation is turned off for projects not in "development" mode. If you want to use the Database Builder on this project, edit the config "mode" setting and change it to "development"';
        }
    }
    
    protected function examine($partialPath)
    {
        $path = $this->modelPath.'/'.$partialPath;
        
        // Grab list of files and subfolders in Models directory.
        $files = array_diff(scandir($path), array('..', '.'));
        
        foreach ($files as $file) {
            $fullPath = $path.'/'.$file;
            $relPath = $partialPath.$file;
            if (is_dir($fullPath)) {
                $this->examine($relPath.'/');
            }
            else {
                $fileinfo = pathinfo($fullPath);
                if ($fileinfo['extension'] == 'php') {
                    
                    $model = $this->getModel($relPath);
                    $refModel = new \ReflectionClass($model);
                    $this->output("//////////////////////");
                    $this->output('MODEL: '.$model);
                    $this->output("//////////////////////");

                    // Only build a table for this model if extends Cora's Model class.
                    if (is_subclass_of($model, '\\Cora\\Model') && $refModel->isAbstract() == false) {
                        $object = new $model();

                        $db         = $object->getDbAdaptor();
                        $tableName  = $object->getTableName();

                        // It's possible for models to have nothing but references (no actual table data of their own)
                        // In that case, we don't want to issue a CREATE table statement.
                        $nonEmptyModel = false;

                        foreach ($object->model_attributes as $key => $props) {
                            //echo $key."\n";
                            $rmodel = isset($props['model']);
                            $rmodels = isset($props['models']);
                            $fieldName = $object->getFieldName($key);

                            // Check if current attribute is a model reference.
                            if ($rmodel || $rmodels) {

                                // Grab related object.
                                if ($rmodel) {
                                    $relatedObj = $object->fetchRelatedObj($props['model']);
                                }
                                else {
                                    $relatedObj = $object->fetchRelatedObj($props['models']);
                                }

                                // Check if uses a relation table.
                                $rTable = $object->usesRelationTable($relatedObj, $key);
                                if ($rTable) {
                                    if (!isset($props['passive'])) {
                                        $this->output("Creating Relation Table: ".$rTable);

                                        // Checking dominance
                                        $rdb = $object->getDbAdaptor(true);

                                        // Determine relation table field names. 
                                        // Set to default to start.
                                        $className = $object->getClassName();
                                        $relClassName = $relatedObj->getClassName();
                                        
                                        // Check if custom field names were set for relation.
                                        if (isset($props['relThis']) && isset($props['relThat'])) {
                                            $className = $props['relThis'];
                                            $relClassName = $props['relThat'];
                                        }

                                        // EDGE CASE - if a relation is being created between the same model.
                                        // If no custom names were set, check for this edge case.
                                        else if ($className == $relClassName) {
                                            $relClassName = $relClassName.'2';
                                        }

                                        // Build relation table.
                                        $rdb->create($rTable)
                                            ->field('id', 'int', 'NOT NULL AUTO_INCREMENT')
                                            ->field($className, 'int')
                                            ->field($relClassName, 'int')
                                            ->primaryKey('id');
                                        $this->output($rdb->getQuery(), 2);
                                        //$rdb->reset();
                                        $rdb->exec();
                                    }
                                }

                                // Model either is a direct reference to a single object,
                                // or else an abstract reference that uses an 'owner' type column on
                                // the other object's table.
                                // If abstract reference (via keyword is set), then we don't want to do anything
                                // here. The ownership column will be handled when the other object is processed.
                                else {
                                    if (!isset($props['via'])) {
                                        $db ->field($fieldName, 'int');
                                    }    
                                }
                            }

                            // If not a model reference, then just add column to this models table.
                            else {
                                // There's at least one attribute on this model which isn't a reference
                                $nonEmptyModel = true;

                                // Set primary key if applicable
                                if (isset($props['primaryKey'])) {
                                    $db ->primaryKey($fieldName);
                                }

                                // Grab column type and then set it.
                                //$attr = $this->getAttributes($props);
                                $attr = $db->getAttributes($props);
                                $type = $db->getType($props);
                                $def = $type.' '.$attr;
                                $db ->field($fieldName, $def);

                                // If the column is defined to have an index, create one.
                                $db->setIndex($fieldName, $props);
                            }
                        }

                        if ($nonEmptyModel) {
                            $this->output("Creating Table: ".$tableName);
                            $db ->create($tableName);
                            $this->output($db->getQuery(), 2);
                            //echo $db->reset();
                            $db->exec();
                        }
                    }
                    else {
                        $this->output('NOTICE: '.$model." is not a Cora Model. Ignoring for DB creation", 2);
                    }
                }
                else {
                    $this->output('NOTICE: '.$relPath." is not a PHP file. Ignoring for DB creation", 2);
                }
            }
        }
        //print_r($files);
    }
    
    
    protected function output($string, $newlines = 1)
    {
        if ($this->displayOutput) {
            echo $string;
            for($i = 0; $i < $newlines; $i++) {
                echo $this->newline;
            }
        }
    }
    
    
    /**
     *  Given a model's attributes, return it's 
     */
    protected function getAttributes($props)
    {
        $attr = '';
        if (isset($props['primaryKey'])) {
            $attr .= 'NOT NULL AUTO_INCREMENT';
        }
        return $attr;
    }
    
    /**
     *  Returns a model in string form such that we could do
     *  'new $fullClassName()' to get an instance of it.
     */
    protected function getModel($filepath) 
    {
        ////////////////////////////////////////////
        // If file path is /task/class.note.inc.php
        ////////////////////////////////////////////

        // Get 'class.note.inc.php'
        $nameFull = $this->getName($filepath);
        
        // Get '/task'
        $path = $this->getPath($filepath);
        
        // Remove prefix and postfix and .php to get classname.
        // gives 'note'
        $className = $this->getClassName($nameFull);
        
        // Get '\task\note'
        $fullClassName = CORA_MODEL_NAMESPACE.$this->getClassPath($path).$className;

        return $fullClassName;
    }
    
    
    /**
     *  Changes '/path/name' to '\path\name'
     */
    protected function getClassPath($path)
    {
        $path = str_replace('/', '\\', $path);
        
        return $path;
    }
    
    
    /**
     *  Removes prefix, postfix, and .php from class filename.
     */
    protected function getClassName($fileName)
    {
        // Get rid of prefix
        $namePiece = @explode($this->config['modelsPrefix'], $fileName);
        $name = isset($namePiece[1]) ? $namePiece[1] : $fileName;
        
        // Get rid of postfix
        $namePiece = @explode($this->config['modelsPostfix'], $name);
        $name = isset($namePiece[1]) ? $namePiece[0] : $name;
        
        // Get rid of .php
        $namePiece = explode('.php', $name);
        $name = isset($namePiece[1]) ? $namePiece[0] : $name;
        
        return $name;
    }
}