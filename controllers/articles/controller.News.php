<?php
namespace Articles;

class News extends \MyApp 
{
    
    public function index() 
    {
        echo 'This is the news homepage.';
    }
    
    public function create()
    {
        $this->load->library('Validate', $this, true); 
        $this->data->title = 'A Simple Form';
        $this->_loadView(__FUNCTION__);
        
        // THE ABOVE _loadView methods does the same thing as the two lines below!
        
        //$this->data->content = $this->load->view('articles/news/create', $this->data, true);
        //$this->load->view('', $this->data);
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
            
            // Insert code to save article here.
            // After saving, should display article, not homepage.
            //
            // ... code ...
            //
            
            // Display News homepage. CHANGE THIS TO DISPLAY ARTICLE. Hint: $this->view($articleId);
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
}