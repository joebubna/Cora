<?php
namespace Websites;

class Notes extends \MyApp {
    
    public function index($p = false) {
        $this->data->title = 'A Simple Form';
        $this->data->content = $p;
        $this->load->view('', $this->data);
    }
    
    public function add() {
        echo 'Add a note page within Note controller!';
    }
    
}