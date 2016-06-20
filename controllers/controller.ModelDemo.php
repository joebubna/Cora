<?php

class ModelDemo extends \MyApp {
    
    public function index() {
        $user = new \User();
        
        $this->data->title = 'A Simple Form';
        $this->data->content = 'Test';
        $user = new \User();
        $this->load->view('', $this->data);
    }
    
    public function test() 
    {
        $repo = \Cora\RepositoryFactory::make($this->db, 'User');
        $user = $repo->find(53);
        echo $user->name;
        $user->name = 'Josiah';
        echo $user->name;
        $repo->save($user);
    }
}