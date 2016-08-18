<?php
$container = new \Cora\Container();

$container->auth = function($c, $user = false, $secureLogin = false, $authField = 'username') {
    return new \Cora\Auth($user, $secureLogin, $authField, $c->repository('user'), $c->event(), $c->session(), $c->cookie(), $c->redirect());
};

$container->cookie = function($c) {
    return new \Cora\Cookie();
};

$container->db = function($c) {
    return new \Cora\Db_MySQL();
};

$container->event = function($c) {
    return new \Cora\EventManager($c->eventMapping());  
};

$container->eventMapping = function($c) {
    return new EventMapping();
};

$container->repository = function($c, $class, $idField = false, $table = false, $freshAdaptor = false, $db = false) {
    return \Cora\RepositoryFactory::make($class, $idField, $table, $freshAdaptor, $db);  
};

$container->session = function($c) {
    return new \Cora\Session();
};

$container->redirect = function($c) {
    return new \Cora\Redirect($c->session());
};



