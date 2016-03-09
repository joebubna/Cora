<?php
/**
* 
*/
class CoraError extends Cora
{
     
    public function index()
    {
        $this->data->title = '404 Not Found';
        $this->data->content = $this->load->view('errors/404', null, true);
        
        // Show 404 to user.
        $this->load->view('', $this->data);
    }
 }