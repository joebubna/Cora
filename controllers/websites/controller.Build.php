<?php
namespace Websites;

class Build extends \MyApp {
    
    public function index() {
        $this->data->title = 'A Simple Form';
        $this->data->content = $this->load->view('forms/genericForm', $this->data, true);
        $this->load->view('', $this->data);
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
        
        //$this->load->model('note');
        //$this->load->model('task/note');
        
        $note = new Note();
        $taskNote = new \Task\Note();
        
        $this->load->library('TestLib');
        $t = new \TestLib();
        $t->hi();
    }
    
    public function testNote() {
        //$this->load->model('task/note');
        $note = new Note();
    }
    
}