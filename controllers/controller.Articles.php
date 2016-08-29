<?php

class Articles extends \App {
    
    public function index($p = false) {
        $this->data->title = 'This is Awesome!!!';
        $this->data->content = 'HELLO WORLD???' . $p;
        $this->load->view('', $this->data);
    }   
    
    // Renaming this method to 'news' will cause the affect mentioned within.
    public function news_test() {
        echo 'This method supercedes the articles/controller.News.php file!!!';
    }
    
}