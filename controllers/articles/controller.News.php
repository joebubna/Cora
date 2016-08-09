<?php
namespace Articles;

class News extends \MyApp 
{
    
    public function index() 
    {
        echo 'This is the articles homepage.';
    }
    
    public function create()
    {
        $this->load->library('Validate', $this, true); 
        $this->data->title = 'A Simple Form';
        $this->data->content = $this->load->view('forms/articles_create', $this->data, true);
        $this->load->view('', $this->data);
    }
    
    public function createPOST() 
    {       
        // Recommended way of setup
        $this->load->library('Validate', $this, true);  
        
        // Define custom method validation call.
        // param1 = 'call' tells Validation that this is a custom call.
        // param2 = 'Article' specifies the class to be called.
        // param3 = 'exists' tells we want to call the 'exists' method.
        // param4 = The error message to display when the check fails.
        // param5 = false tells Validate that to PASS the test the returned result needs to be false.
        $this->Validate->def('articleExists', 'Article','exists', 'This title already exists.', false);
        
        // Define validation rules.
        $this->Validate->rule('title', ['required', 'articleExists', 'trim']);
        $this->Validate->rule('content', 'required|matches[title]', 'Password Confirmation');
        
        // Initiate validation
        if ($this->Validate->run()) {        
            // Submit was successful!
            $this->index();
        }
        else {      
            // Call the main method to redisplay the form.
            $this->create();
        }
    }
    
    public function view($p1, $p2, $p3) 
    {
        echo 'Yay<br>';
        echo $p1 . '<br>';
        echo $p2 . '<br>';
        echo $p3 . '<br>';
    }
    
    public function testModels() 
    {       
        /**
         *  You can explicitly load models if you want to, but it's not necessary
         *  as Cora's autoloader will load models for you. Note that this does
         *  NOT apply to other resources like Libraries; you must explicitly load
         *  those.
         */
        //$this->load->model('note');
        //$this->load->model('task/note');
        
        $note = new Note();
        $taskNote = new \Task\Note();
    }
    
    public function testLibraries()
    {
        //$this->load->library('TestLib');
        echo 'HERE<BR>';
        $v = new \DateTime();
        $t = new \Library\TestLib();
        $t->hi();
    }
    
}