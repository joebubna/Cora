<?php
$container = new \Cora\Container();

/*******************************************************************
 *
 *  GENERAL APP RESOURCES
 *
 *******************************************************************/

$container->auth = function($c, $user = false, $secureLogin = false, $authField = 'email') {
    return new \Libraries\Cora\Auth(
        $user, $secureLogin, $authField, $c->repository('user'), $c->repository('role'), $c->event, $c->session, $c->cookie, $c->redirect, $c
    );
};

$container->singleton('singleUser', function($c, $name) {
    return new \Models\User($name);
});

//$container->singleUser = new \Models\User('BobbyJones@gmail.com');

$container->{\Classes\User::class} = function($c, $user_id) {
  return new \Classes\User($user_id);
};

// $container->{\Classes\Users::class} = function($c) {
//   return new \Classes\Users(
//     $c->{\Cora\Database::class},
//     $c->getFactory(\Classes\User::class)
//   );
// };



/*******************************************************************
 *
 *  Repositories
 *
 *******************************************************************/

$container->comments = function($c) {
    return $c->repository('Comment');
};

$container->permissions = function($c) {
    return $c->repository('Permission');
};

$container->roles = function($c) {
    return $c->repository('Role');
};

$container->users = function($c) {
    return $c->repository('User');
};

$container->{\Cora\Repository::class.\Models\User::class} = function($c) {
    return $c->repository('User');
};


/*******************************************************************
 *
 *  Models
 *
 *******************************************************************/

$container->{\Models\User::class} = function($c, $name) {
    return new \Models\User($name);
};


/*******************************************************************
 *
 *  EVENTS
 *
 *******************************************************************/

$container->events = new \Cora\Container($container);

// Tell the container to return the listeners as closures.
$container->events->returnClosure(true);

$container->events->passwordReset = function($c, $user) {
    return new \Events\PasswordReset($user);
};

$container->events->userRegistered = function($c, $user) {
    return new \Events\UserRegistered($user);
};



/*******************************************************************
 *
 *  LISTENERS
 *
 *******************************************************************/

$container->listeners = new \Cora\Container($container);

$container->listeners->returnClosure(true);

$container->listeners->displayGreeting = function($c) {
    return new \Listeners\DisplayGreeting();
};



/*******************************************************************
 *
 *  LISTENERS THAT SEND EMAILS
 *
 *******************************************************************/

$container->listeners->emails = new \Cora\Container($container->listeners);

// Tell the container to return the listeners as closures.
$container->listeners->emails->returnClosure(true);

$container->listeners->emails->sendPasswordResetToken = function($c) {
    return new \Listeners\Emails\SendPasswordResetToken($c->mailer, $c->load);
};












/*******************************************************************
 *
 *  CORA RESOURCES
 *
 *******************************************************************/

$container->autoload = function($c) {
    return new \Cora\Autoload();
};

$container->container = function($c, $parent = false, $data = false, $dataKey = false) {
    return new \Cora\Collection($data, $dataKey, $parent);
};

$container->collection = function($c, $data = false, $dataKey = false, $parent = false) {
    return new \Cora\Collection($data, $dataKey, $parent);
};

$container->cookie = function($c) {
    return new \Cora\Cookie();
};

$container->db = function($c, $connection = false, $existingConnection = false) {
    if (!$connection) {
        return \Cora\Database::getDefaultDb(true, $existingConnection);
    }
    return \Cora\Database::getDb($connection, $existingConnection);
};

$container->database = function($c) {
    return new \Cora\Database();
};

$container->{\Cora\Database::class} = function($c) {
  return new \Cora\Database();
};

$container->dbBuilder = function($c) {
    return new \Cora\DatabaseBuilder(false);
};

$container->error = function($c) {
    return new \Cora\App\Error($c);
};

$container->event = function($c) {
    return new \Cora\EventManager($c->eventMapping());
};

$container->eventMapping = function($c) {
    return new \Cora\App\EventMapping($c);
};

$container->input = function($c) {
    return new \Cora\Input();
};

$container->load = function($c) {
    return new \Cora\App\Load();
};

$container->mailer = function($c) {
    return new \Cora\Mailer($c->PHPMailer());
};

$container->paginate = function($c, $paginateView, $filterArray, $numOfProviders, $pageOffset, $limit = 18) {
    return new \Cora\Paginate($c->load, $paginateView, $filterArray, $numOfProviders, $pageOffset, $limit);
};

$container->PHPMailer = function($c) {
    return new \PHPMailer;
};

$container->PHPUnit = function($c) {
    return new \Cora\PHPUnitTest();
};

$container->redirect = function($c) {
    return new \Cora\Redirect($c->session());
};

$container->repository = function($c, $class, $idField = false, $table = false, $freshAdaptor = false, $db = false) {
    return \Cora\RepositoryFactory::make($class, $idField, $table, $freshAdaptor, $db);
};

$container->session = function($c) {
    return new \Cora\Session();
};

$container->sessionStub = new \Cora\SessionStub();

$container->singleton('testSingleton', function($c) {
    return ['key1'=>'value1'];
});


/*******************************************************************
 *
 *  TESTING RESOURCES
 *  THE FOLLOWING IS USED BY CORA'S UNIT TESTS
 *
 *******************************************************************/

$container->tests = new \Cora\Container($container);

$container->tests->inactiveUsers = function($c) {
    return $c->repository('Tests\InactiveUser');
};

$container->tests->multiArticle = function($c) {
    return $c->repository('Tests\MultiArticle');
};

$container->tests->users = function($c) {
    return $c->repository('Tests\User');
};

$container->tests->userComments = function($c) {
    return $c->repository('Tests\Users\Comment');
};


// Don't remove. Register container to be accessible globally.
$GLOBALS['coraContainer'] = $container;
