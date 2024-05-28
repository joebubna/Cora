<?php
namespace Cora\App;
/**
* 
*/
class Error extends Controller
{
    public function handle($errorType)
    {
        $this->data->title = $errorType .' Error';
        
        // If the error is a '404', then load "errors/404.php" from views.
        $this->data->content = $this->load->view('errors/'.$errorType, $this->data, true);
        
        // Show error page to user using default site template.
        $this->load->view('', $this->data);
    }
 }