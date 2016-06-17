<?php

class ModelDemo extends \MyApp {
    
    public function index() {
        $user = new \User();
        
        $this->data->title = 'A Simple Form';
        $this->data->content = 'Test';
        $user = new \User();
        $this->load->view('', $this->data);
    }
    
}