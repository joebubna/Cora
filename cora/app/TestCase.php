<?php
namespace Cora\App;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $app;
    protected static $sapp;
    
    protected function setUp()
    {
        // Load test container.
        require('includes/container.php');
        
        // The container will be accessable from out tests using $this->app.
        $this->app = $container;
        
        // Reset DB
        //$this->app->dbBuilder->reset();
        
        
        /**
         *  Stub password verify so we can easily login as any user for our tests.
         */
        $this->app->auth = function($c) {
            $auth = $this->getMockBuilder('Libraries\Cora\Auth')
                         ->setConstructorArgs(array(
                            false, false, 'email', $c->repository('user'), $c->repository('role'), $c->event(), $c->session(), $c->cookie(), $c->redirect(), $c
                         ))
                         ->setMethods(['passwordVerify'])
                         ->getMock();
            
            $auth->method('passwordVerify')
                 ->willReturn(true);
            
            return $auth;
        };
        
        
        /**
         *  We don't want any errors from trying to save Cookie data.
         *  Just stub whole object.
         */
        $this->app->cookie = function($c) {
            return $c->PHPUnit->getMockBuilder('\Cora\Cookie')
                              ->getMock();
        };
        
        
        /**
         *  We don't want any views to be output during testing.
         */
        $this->app->load = function($c) {
            // TRUE tells the loader to never output a view.
            return new \Cora\App\Load(true);
        };
        
        
        /**
         *  We don't want redirect header data getting output during tests.
         *  So stop redirect requests.
         */
        $this->app->redirect = function($c) {            
            $stub = $c->PHPUnit->getMockBuilder('\\Cora\\Redirect')
                               ->disableOriginalConstructor()
                               ->getMock();
            
            $stub->method('url')
                 ->will($this->returnCallback(function () {
                     echo 'Redirect';
                 }));
            return $stub;
        };
        
        
        /**
         *  If we try to do something we don't have access to do (403),
         *  then echo 403.
         */
        $this->app->error = function($c) {            
            $stub = $c->PHPUnit->getMockBuilder('\\Cora\\App\\Error')
                               ->disableOriginalConstructor()
                               ->getMock();
            
            $stub->method('handle')
                 ->will($this->returnCallback(function ($arg) {
                        echo $arg;
                   }));
            return $stub;
        };

        
        /**
         *  Some classes we might want to use like Auth, use the Session class.
         *  However, when testing we won't have a session, so let's stub it with
         *  a class that just holds data and can function like the normal Session class.
         */
        $this->app->session = function($c) {
            return $c->sessionStub;
        };
        
        
        /**
         *  Loop through all our email listeners and stub them all.
         *  We don't want to be sending out emails during testing.
         */
        foreach ($this->app->listeners->emails as $listener => $v) {
            $this->app->listeners->emails->$listener = function($c) {
                return $c->PHPUnit->getMockBuilder('\Cora\Listener')->getMock();
            };
        }
    }
    
    
    public static function setUpBeforeClass()
    {
        // Load test container.
        require('includes/container.php');
        
        // Reset DB.
        $container->dbBuilder->reset();
    }
    
    
    public static function tearDownAfterClass()
    {
        // Load test container.
        require('includes/container.php');
        
        // Reset DB.
        $container->dbBuilder->reset();
    }
}