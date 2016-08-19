<?php
namespace Cora;
/**
 *
 */
 class Redirect extends Framework {

    protected $session;
    protected $saved;
    
     
    public function __construct($session)
    {
        parent::__construct();
        $this->session = $session;
        
        if (isset($_SERVER['HTTP_REFERER'])) {
            
            // Check if a redirect history already exists.
            if (isset($_SESSION['redirect']['history'])) {
                
                // Check if the current URL is the same as previous.
                // If they are different, then add current URL to history.
                $lastUrlInHistoryArray = count($_SESSION['redirect']['history']) - 1;
                if ($_SERVER['HTTP_REFERER'] != $_SESSION['redirect']['history'][$lastUrlInHistoryArray]) {
                    $_SESSION['redirect']['history'][] = $_SERVER['HTTP_REFERER'];
                }
                
                // If there's more than 10 items in the history array (higher index = newer)
                // Then start removing the oldest entries.
                while (count($_SESSION['redirect']['history']) > 10) {
                    array_shift($_SESSION['redirect']['history']);
                }
                
            // If no history exists.
            } else {
                $_SESSION['redirect']['history'] = array();
                $_SESSION['redirect']['history'][] = $_SERVER['HTTP_REFERER'];
            }
        }
        
        if ($this->session->savedUrl) {
            $this->saved = $this->session->savedUrl;
        }
    }
    
     
    /**
     *  Redirects the browser.
     */
    public function url($url = null, $append = '')
    {
        unset($_POST);
        unset($_FILES);
        header('location: '.$this->getRedirect($url).$append);
        exit;
    }
     
     
    /**
     *  For situations such as where a not-logged-in user tries to access a restricted page,
     *  you may want to save the URL they were trying to go to, so you can try forwarding them
     *  to it again after they login.
     */
    public function saveUrl($url = false, $append = '')
    {
        // If no URL is specified, then save the current URL.
        if ($url == false) {
            $url = $this->config['base_url'].$_SERVER['REQUEST_URI'];
        }
        
        // Set saved URL
        $this->saved = $this->getRedirect($url).$append;
        
        // Also save URL to session, in-case it won't be used until next request.
        $this->session->savedUrl = $this->saved;
    }
     
    
    /** 
     *  Send the user so a saved URL.
     */
    public function gotoSaved()
    {
        unset($_POST);
        unset($_FILES);
        header('location: '.$this->saved);
        exit;
    }
     
    
    public function isSaved()
    {
        if (isset($this->saved)) {
            return true;
        }
        return false;
    }

     
    /**
     *  Returns a URL.
     */
    public function getRedirect($url = null)
    {
        // If a url to redirect to was specified
        if ($url) {
            
            // If the redirect is being specified as an offset such as "-2"
            if (is_numeric($url)) {
                
                // Just clarifying that the URL (in this case) is steps to take backwards in the history array, not an actual URL.
                $steps = $url;
                
                // If you want to send the user back 2 steps, you would pass in -2 as the steps.
                // Adding this negative number to the number of items in the history array gives the desired URL offset.
                $offset = count($_SESSION['redirect']['history']) + $steps;
                
                // Check if such an offset exists.
                if (isset($_SESSION['redirect']['history'][$offset])) {
                    
                    $redirect = $_SESSION['redirect']['history'][$offset];
                    if (!empty($redirect)) {
                        return $redirect;
                    } else {
                        return BASE_URL;
                    }
                }
                
                // Otherwise redirect to homepage as fallback.
                else {
                     return $this->config['site_url'];
                }
            } 
            
            // If the URL is an actual URL.
            else {
                // If the URL is specified as a relative URL, then include the 'site_url' setting at beginning.
                if (substr($url, 0, 1) == '/') {
                    return $this->config['site_url'].substr($url, 1);
                }
                else {
                    return $url;
                }
            }
        } 
        
        // Redirect to home page.
        else {
            return $this->config['site_url'];
        }
    }

     
    public function clearHistory()
    {
        unset($_SESSION['redirect']['history']);
    }
     
    
    public function getHistory()
    {
        return $_SESSION['redirect']['history'];
    } 

     
    public function viewHistory()
    {
        print_r($_SESSION['redirect']['history']);
    }

 }