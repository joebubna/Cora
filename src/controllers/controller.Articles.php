<?php
namespace Controllers;

class Articles extends \Cora\App\Controller {
    
    public function view($category, $year, $month, $author, $articlePublicId) 
    {
        echo "$category<br>";
        echo "$year<br>";
        echo "$month<br>";
        echo "$author<br>";
        echo "$articlePublicId";
    }
    
}