<?php 
/**
* 
*/
class Article {
    
    public static function exists($title = false) {
        
        // Pretend we're checking if an article with this title already exists.
        return $title == 'test' ? true : false;
    }

}