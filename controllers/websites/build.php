<?php

class Build extends Cora {
    
    public function index() {
        $this->data->title = 'A Simple Form';
        $this->data->content = $this->Cora->view('forms/genericForm', $this->data, true);
        $this->Cora->view('', $this->data);
    }
    
    public function indexPOST() {
        echo $_POST['data'];
    }
    
    public function view($p1, $p2, $p3) {
        echo 'Yay<br>';
        echo $p1 . '<br>';
        echo $p2 . '<br>';
        echo $p3 . '<br>';
    }
    
    public function test() {
        
        $this->Cora->model('')
    }
    
}