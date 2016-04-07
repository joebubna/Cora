<?php

class Demo extends \MyApp {
    
    public function __construct()
    {   parent::__construct($this->container);
        
        
    }
    
    public function index()
    {
        echo 'Index GET';
    }
    
    public function itemGET($id)
    {
        echo $id;
    }
    
    public function someWebPage()
    {
        $this->data->title = "Demo App!";
        $this->data->content = $this->load->view('demo/partialView', $this->data, true);
        $this->load->view('template', $this->data);
    }
}