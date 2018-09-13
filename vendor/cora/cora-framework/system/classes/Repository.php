<?php
namespace Cora;
/**
*
*/
class Repository
{
    protected $factory;
    protected $gateway;
    protected $model;           // An instance of the model this Repository is for.
    protected $saveStarted;
    protected $savedAdaptors;

    public function __construct(Gateway $gateway, Factory $factory, $dummyModel)
    {
        $this->gateway = $gateway;
        $this->factory = $factory;
        $this->model = $dummyModel;

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

    public function getTable()
    {
        return $this->gateway->getTable();
    }

    public function find($id)
    {
        // $coraDbQuery = $this->gateway->getDb();
        // $coraDbQuery = $this->model::model_constraints($coraDbQuery);
        $record = $this->gateway->fetch($id);
        return $this->factory->make($record);
    }

    public function findOne($query = false, $vars = false, $loadMap = false)
    {
      // Vars
      $queryDefinition = false;

      // If a closure was passed in instead of query object, then store it
      if ($query instanceof \Closure) {
        $queryDefinition = $query;
      }

      // If no query builder object was passed in OR we were given a closure, 
      // then grab the gateway's query object.
      if (!$query || $queryDefinition) {
        $query = $this->gateway->getDb();
      }

      // Pass the query through any defined model constraints
      $query = $this->model::model_constraints($query);

      // If a closure was given for defining the query, then through that
      if ($queryDefinition) {
        $query = $queryDefinition($query, $vars);
      }

      $all = $this->gateway->fetchByQuery($query);
      return $this->factory->makeGroup($all, $loadMap)->get(0);
    }

    
    public function findAll($query = false, $vars = false, $loadMap = false)
    {
      // Vars
      $queryDefinition = false;

      // If a closure was passed in instead of query object, then store it
      if ($query instanceof \Closure) {
        $queryDefinition = $query;
      }

      // If no query builder object was passed in OR we were given a closure, 
      // then grab the gateway's query object.
      if (!$query || $queryDefinition) {
        $query = $this->gateway->getDb();
      }
      
      // Pass the query through any defined model constraints
      $query = $this->model::model_constraints($query);

      // If a closure was given for defining the query, then through that
      if ($queryDefinition) {
        $query = $queryDefinition($query, $vars);
      }
      
      $all = $this->gateway->fetchByQuery($query);
      return $this->factory->makeGroup($all, $loadMap);
    }

    public function findBy($prop, $value, $options = array())
    {
        $coraDbQuery = $this->gateway->getDb();
        $coraDbQuery = $this->model::model_constraints($coraDbQuery);
        $all = $this->gateway->fetchBy($prop, $value, $options);
        return $this->factory->makeGroup($all);
    }

    public function findOneBy($prop, $value, $options = array())
    {
        $coraDbQuery = $this->gateway->getDb();
        $coraDbQuery = $this->model::model_constraints($coraDbQuery);
        $all = $this->gateway->fetchBy($prop, $value, $options);
        return $this->factory->makeGroup($all)->get(0);
    }

    /**
     *  Count the number of results, optionally with query limiters.
     */
    public function count($coraDbQuery = false)
    {
        // If no query builder object was passed in, then grab the gateway's.
        if (!$coraDbQuery) {
            $coraDbQuery = $this->gateway->getDb();
        }
        $coraDbQuery = $this->model::model_constraints($coraDbQuery);
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
        $event = $GLOBALS['coraContainer']->event;

        // Check if model trying to be saved inherits from Cora model or is a collection of models...
        // Catch any exceptions thrown during the saving process.
        if ($this->checkIfModel($model)) {
            try {
                $return = $this->gateway->persist($model, $table, $id_name);
            } catch (\Cora\LockException $e) {
                $this->lockError = true;
                $event->fire(new \Cora\Events\DbLockError($model));
            } catch (\Exception $e) {
                $this->dbError = true;
                $event->fire(new \Cora\Events\DbRandomError($model, $e));
            }
        }
        else if ($model instanceof \Cora\Collection) {
            foreach ($model as $obj) {
                if ($this->checkIfModel($obj)) {
                    try {
                        $this->gateway->persist($obj, $table, $id_name);
                    } catch (\Cora\LockException $e) {
                        echo "\nCatching model error at repo level";
                        $this->lockError = true;
                        $event->fire(new \Cora\Events\DbLockError($model));
                    } catch (\Exception $e) {
                        $this->dbError = true;
                        echo $e->getMessage();
                        $event->fire(new \Cora\Events\DbRandomError($model, $e));
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
                if ($this->lockError) {
                    $conn->rollBack();
                }
                else if ($this->dbError) {
                    $conn->rollBack();
                }
                else {
                    $conn->commit();
                }
                unset($this->savedAdaptors[$key]);
            }

            // Update any models with their new version numbers if save didn't have any errors
            $versionUpdateArray = &$GLOBALS['coraVersionUpdateArray'];
            if ($this->lockError == false && $this->dbError == false) {
                foreach($versionUpdateArray as $update) {
                    $model = $update[0];
                    $key = $update[1];
                    $newKeyValue = $update[2];
                    $model->$key = $newKeyValue;
                }
            }
            else {
                $versionUpdateArray = array();
            }

            // Lock error cleanup. Throw any needed exceptions.
            // Clear any globally stored errors now that this transaction is complete.
            // Errors need to be cleared BEFORE throwing exceptions to not mess up future saves...
            $lockErrorStatus = $this->lockError;
            $dbErrorStatus = $this->dbError;
            $this->lockError = false;
            $this->dbError = false;

            if ($lockErrorStatus) {
                throw new \Cora\LockException('Tried to update a lock protected field in a database using old data (someone else updated it first and your data is potentially out-of-date)');
            }

            if ($dbErrorStatus) {
                throw new \Exception('An unexpected error occurred while trying to save something. Listen for the DbRandomError event to find out specifics.');
            }
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
