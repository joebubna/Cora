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

$container->autoload = function($c) {
    return new \Cora\Autoload();
};

$container->container = function($c, $parent = false, $data = false, $dataKey = false) {
    return new \Cora\Container($parent, $data, $dataKey);  
};

$container->cookie = function($c) {
    return new \Cora\Cookie();
};

$container->db = function($c) {
    return new \Cora\Db_MySQL();
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

$container->resultSet = function($c, $data = null) {
    return new \Cora\ResultSet($data);
};

$container->session = function($c) {
    return new \Cora\Session();
};

//$container->setInstance('sessionStub', function($c, $data = false) {
//    return new \Cora\SessionStub($data);
//});

$container->setInstance('sessionStub', new \Cora\SessionStub());



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

$container->events->providerCreated = function($c, $user) {
    return new \Events\ProviderCreated($user);
};



/*******************************************************************
 *
 *  LISTENERS
 *
 *******************************************************************/

$container->listeners = new \Cora\Container($container);



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

$container->listeners->emails->sendInitialPasswordResetToken = function($c) {
    return new \Listeners\Emails\SendInitialPasswordResetToken($c->mailer, $c->load);
};





/*******************************************************************
 *
 *  TESTING RESOURCES
 *  THE FOLLOWING IS USED BY CORA'S UNIT TESTS
 *
 *******************************************************************/

$container->tests = new \Cora\Container($container);

$container->tests->users = function($c) {
    return $c->repository('Tests\User');  
};