<?php 
namespace Cora;
/**
* 
*/
class Repository
{
    protected $factory;
    protected $gateway;
    protected $saveStarted;
    protected $savedAdaptors;

    public function __construct(Gateway $gateway, Factory $factory)
    {
        $this->gateway = $gateway;
        $this->factory = $factory;
        
        $this->saveStarted = &$GLOBALS['coraSaveStarted'];
        $this->savedAdaptors = &$GLOBALS['coraAdaptorsForCurrentSave'];
        $this->lockError = &$GLOBALS['coraLockError']; // If a lock exception gets thrown when trying to modify the db, set true.
        $this->dbError = &$GLOBALS['coraDbError']; // If some random error occurs, set this to true so rollback gets triggered.
    }
    
    public function viewQuery($bool = true)
    {
        $this->gateway->viewQuery($bool);
        return $this;
    }
    
    public function getDb($fresh = false)
    {
        return $this->gateway->getDb();
    }

    public function find($id)
    {
        $record = $this->gateway->fetch($id);
        return $this->factory->make($record);
    }
    
    public function findOne($coraDbQuery)
    {
        $all = $this->gateway->fetchByQuery($coraDbQuery);
        return $this->factory->makeGroup($all)->get(0);
    }

    public function findAll($coraDbQuery = false)
    {
        if ($coraDbQuery) {
            $all = $this->gateway->fetchByQuery($coraDbQuery);
        }
        else {
            $all = $this->gateway->fetchAll();
        }
        return $this->factory->makeGroup($all);
    }
    
    public function findBy($prop, $value, $options = array())
    {
        $all = $this->gateway->fetchBy($prop, $value, $options);
        return $this->factory->makeGroup($all);
    }
    
    public function findOneBy($prop, $value, $options = array())
    {
        $all = $this->gateway->fetchBy($prop, $value, $options);
        return $this->factory->makeGroup($all)->get(0);
    }
    
    /**
     *  Count the number of results, optionally with query limiters.
     */ 
    public function count($coraDbQuery = false)
    {
        if (!$coraDbQuery) {
            $coraDbQuery = $this->gateway->getDb();
        }
        return $this->gateway->count($coraDbQuery);
    }
    
    /**
     *  Counts the number of affected rows / results from the last executed query.
     *  Removes any LIMITs.
     */
    public function countPrev()
    {
        return $this->gateway->countPrev();
    }

    public function delete($id)
    {
        // Get model from DB.
        $model = $this->find($id);

        // Delete any data associated with this model by calling it's own delete method
        // I.E. Notes, file uploads, etc.
        $model->delete();
        
        // Delete the model from the DB.
        $this->gateway->delete($id);
    }


    public function save($model, $table = null, $id_name = null)
    {
        $return = 0;
        
        // Check whether or not a "save transaction" has been started.
        // If not, start one.
        $clearSaveLockAfterThisFinishes = false;
        if ($this->saveStarted == false) {
            $clearSaveLockAfterThisFinishes = true;
            $this->saveStarted = true;

            $config = $this->gateway->getDb()->getConfig();

            // Add default DB connection to connections saved list. 
            $defaultConn = \Cora\Database::getDefaultDb();
            $this->savedAdaptors[$defaultConn->getDefaultConnectionName()] = $defaultConn;

            // For each connection defined in the config, create a global one that will share its
            // connection details with any new adaptors created during this save transaction.
            foreach ($config['database']['connections'] as $key => $connName) {
                if (!isset($this->savedAdaptors[$key])) {
                    $conn = \Cora\Database::getDb($key);
                    $this->savedAdaptors[$key] = $conn;
                } 
                $this->savedAdaptors[$key]->startTransaction();
            }
        }
        
        // Grab event manager for this app.
        $event = $GLOBALS['container']->event;

        if ($this->checkIfModel($model)) {
            try {
                $return = $this->gateway->persist($model, $table, $id_name);
            } catch (\Cora\LockException $e) {
                $this->lockError = true;
            } catch (\Exception $e) {
                $this->dbError = true;
            }
        }
        else if ($model instanceof \Cora\Container || $model instanceof \Cora\ResultSet) {
            foreach ($model as $obj) {
                if ($this->checkIfModel($obj)) {
                    try {
                        $this->gateway->persist($obj, $table, $id_name);
                    } catch (\Cora\LockException $e) {
                        $this->lockError = true;
                        //$event->fire(new \Event\PasswordReset($user, $this->app->mailer(), $this->load));
                    } catch (\Exception $e) {
                        $this->dbError = true;
                    }
                }
                else {
                    throw new \Exception("Cora's Repository class can only be used with models that extend the Cora Model class. ".get_class($obj)." does not.");
                }
            }
        }
        else {
            throw new \Exception("Cora's Repository class can only be used with models that extend the Cora Model class. " .get_class($model)." does not.");
        }
        
        // Check whether this call to Save should clear the lock.
        //
        // Basically, because when an object is saved, child objects are also recursively saved...
        // To avoid save loops and resaving models that have already been saved once during the current transaction,
        // this save lock is initiated on the original call to Save() on the parent object.
        if ($clearSaveLockAfterThisFinishes) {
            $this->resetSavedModelsList();
            $this->saveStarted = false;

            // Either commit or roll-back the changes made during this transaction.
            foreach ($this->savedAdaptors as $key => $conn) {
                if ($this->lockError || $this->dbError) {
                    $conn->rollBack();
                }
                else {
                    $conn->commit();
                }
                unset($this->savedAdaptors[$key]);
            }

            // Clear any globally stored errors now that this transaction is complete.
            $this->lockError = false;
            $this->dbError = false;
        }  
        return $return;
    }
    
    protected function checkIfModel($model)
    {
        if ($model instanceof \Cora\Model) {
            return true;   
        }
        return false;
    }
    
    protected function resetSavedModelsList()
    {
        $GLOBALS['savedModelsList'] = [];
    }

}
