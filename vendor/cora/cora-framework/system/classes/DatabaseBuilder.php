<?php
namespace Cora;

class DatabaseBuilder extends Framework
{
    protected $modelPath;
    
    public function __construct()
    {
        parent::__construct();
        spl_autoload_register(array($this, 'autoLoader'));
        spl_autoload_register(array($this, 'coraExtensionLoader'));
        spl_autoload_register(array($this, 'libraryLoader'));
        spl_autoload_register(array($this, 'coraLoader'));
        
        $this->modelPath = realpath($this->config['pathToModels']);
    }
    
    public function dbEmpty($connection)
    {
        $db = \Cora\Database::getDb($connection);
        $db->emptyDatabase();
    }
    
    public function dbBuild($data)
    {
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
                $model = $this->getModel($relPath);
                echo "//////////////////////\n";
                echo 'MODEL: '.$model."\n";
                echo "//////////////////////\n";
                
                // Only build a table for this model if extends Cora's Model class.
                if (is_subclass_of($model, '\Cora\Model')) {
                    $object = new $model();
                    
                    $db         = $object->getDbAdaptor();
                    $tableName  = $object->getTableName();
                    
                    foreach ($object->model_attributes as $key => $props) {
                        //echo $key."\n";
                        $rmodel = isset($props['model']);
                        $rmodels = isset($props['models']);
                        
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
                                echo "Creating Relation Table: ".$rTable."\n";
                                
                                // Checking dominance
                                $rdb;
                                if (isset($props['passive'])) {
                                    $rdb = $relatedObj->getDbAdaptor(true);
                                }
                                else {
                                    $rdb = $object->getDbAdaptor(true);
                                }
                                
                                // Build relation table.
                                $rdb->create($rTable)
                                    ->field('id', 'int', 'NOT NULL AUTO_INCREMENT')
                                    ->field($object->getClassName(), 'int')
                                    ->field($relatedObj->getClassName(), 'int')
                                    ->primaryKey('id');
                                echo $rdb->getQuery()."\n\n";
                                //$rdb->reset();
                                $rdb->exec();
                            }
                            
                            // Model either is a direct reference to a single object,
                            // or else an abstract reference that uses an 'owner' type column on
                            // the other object's table.
                            // If abstract reference (via keyword is set), then we don't want to do anything
                            // here. The ownership column will be handled when the other object is processed.
                            else {
                                if (!isset($props['via'])) {
                                    $db ->field($key, 'int');
                                }    
                            }
                        }
                        
                        // If not a model reference, then just add column to this models table.
                        else {
                            // Set primary key if applicable
                            if (isset($props['primaryKey'])) {
                                $db ->primaryKey($key);
                            }
                            
                            // Grab column type and then set it.
                            //$attr = $this->getAttributes($props);
                            $attr = $db->getAttributes($props);
                            $type = $db->getType($props);
                            $def = $type.' '.$attr;
                            $db ->field($key, $def);
                            
                            // If the column is defined to have an index, create one.
                            $db->setIndex($key, $props);
                        }
                    }
                    
                    echo "Creating Table: ".$tableName."\n";
                    $db ->create($tableName);
                    echo $db->getQuery()."\n\n";
                    //echo $db->reset();
                    $db->exec();
                }
                else {
                    echo 'NOTICE: '.$model." is not a Cora Model. Ignoring for DB creation.\n";
                }
            }
        }
        //print_r($files);
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
        $fullClassName = '\\'.$this->getClassPath($path).$className;

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
        $name = $namePiece ? $namePiece[1] : $fileName;
        
        // Get rid of postfix
        $namePiece = @explode($this->config['modelsPostfix'], $name);
        $name = $namePiece ? $namePiece[0] : $name;
        
        // Get rid of .php
        $namePiece = explode('.php', $name);
        $name = $namePiece ? $namePiece[0] : $name;
        
        return $name;
    }
    
    
    
    
    
    ////////////////////////////////////////////////
    // These are identical to the autoloading methods in Route.
    // In the future get rid of this duplication.
    ////////////////////////////////////////////////
    
    protected function autoLoader($className)
    {
        $fullPath = $this->config['pathToModels'] .
                    $this->getPathBackslash($className) .
                    $this->config['modelsPrefix'] .
                    $this->getNameBackslash($className) .
                    $this->config['modelsPostfix'] .
                    '.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        
        // Grab root of namespace.
        $rootName = explode('\\', $className)[0];
        
        // Depending on the root of the namespace, possibly run one of several autoloaders.
        if ($rootName == 'Event') {
            $this->eventLoader($className);
        }
        else if ($rootName == 'Listener') {
            $this->listenerLoader($className);
        }
        else if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
    
    protected function eventLoader($className)
    {
        $fullPath = $this->config['pathToEvents'] .
                    $this->getPathBackslash($className, true) .
                    $this->config['eventsPrefix'] .
                    $this->getNameBackslash($className) .
                    $this->config['eventsPostfix'] .
                    '.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
    
    protected function listenerLoader($className)
    {
        $fullPath = $this->config['pathToListeners'] .
                    $this->getPathBackslash($className, true) .
                    $this->config['listenerPrefix'] .
                    $this->getNameBackslash($className) .
                    $this->config['listenerPostfix'] .
                    '.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
    
    protected function coraLoader($className)
    {
        $fullPath = dirname(__FILE__) . '/' .
                    //$this->getPathBackslash($className) .
                    $this->getNameBackslash($className) .
                    '.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
    
    protected function coraExtensionLoader($className)
    {
        $fullPath = $this->config['pathToCora'] .
                    'extensions/' .
                    $this->getPathBackslash($className) .
                    $this->getNameBackslash($className) .
                    '.php';
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        if (file_exists($fullPath)) {
            include($fullPath);
        }
    }
    
    protected function libraryLoader($className)
    {
        //$name = $this->getName($className);
        //$path = $this->getPath($className);
        $fullPath = $this->config['pathToLibraries'] .
                    $this->getPathBackslash($className) .
                    $this->config['librariesPrefix'] .
                    $this->getNameBackslash($className) .
                    $this->config['librariesPostfix'] .
                    '.php';
        
        //echo 'Trying to load ', $className, '<br> &nbsp;&nbsp;&nbsp; from file ', $fullPath, "<br> &nbsp;&nbsp;&nbsp; via ", __METHOD__, "<br>";
        // If the file exists in the Libraries directory, load it.
        if (file_exists($fullPath)) {
            include_once($fullPath);
        }
    }
    
    public function dummy($item1, $item2)
    {
        // For testing if calling an empty method
        // OR
        // using method_exists() is faster then doing lifecycle callbacks.
    }
}