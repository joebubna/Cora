<?php

class Websites extends \MyApp {
    
    public function index($p = false) {
        $this->data->title = 'A Simple Form';
        $this->data->content = 'WEBSITES ' . $p;
        $this->load->view('', $this->data);
    }
    
    public function notes() {
        echo 'Notes method within Websites controller!';
        $n = new \Task\Note();
    }
    
    
//    public function build() {
//        echo 'Websites Build method.';
//    }
    
}