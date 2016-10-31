<?php
namespace Cora;

class Mailer extends Framework
{
    public $message;
    
    public function __construct($PHPMailer)
    {
        parent::__construct(); // Call parent constructor too so we don't lose functionality.
        
        $this->message = $PHPMailer;
        $this->message->CharSet = "utf-8";
        $this->message->isSMTP();
        $this->message->SMTPDebug   = 0;
        $this->message->SMTPAuth    = $this->config['smtp_auth'];
        $this->message->SMTPSecure  = $this->config['smtp_secure'];
        $this->message->Host        = $this->config['smtp_host'];
        $this->message->Port        = $this->config['smtp_port'];
        $this->message->Username    = $this->config['smtp_username'];
        $this->message->Password    = $this->config['smtp_password'];
        $this->message->isHTML(true);
    }

    
    public function __call($name, $arguments)
    {   
        // If a method exists in this class, then call that.
        if (method_exists($this, $name)) {
            call_user_func_array(array($this, $name), $arguments);
        }
        
        // Otherwise pass the method call onto the Message (PHPMailer) object.
        else {
            call_user_func_array(array($this->message, $name), $arguments);
        }
    }
    
    
    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
        else {
            return $this->message->$name;
        }
    }
    
    
    public function __set($name, $value)
    {
        if (isset($this->$name)) {
            $this->$name = $value;
        }
        else {
            $this->message->$name = $value;
        }
    }
    
    public function send()
    {
        if ($this->config['mode'] == 'development') {
            $recipients = $this->message->getAllRecipientAddresses();
            $subject = $this->message->Subject;
            $subject .= ' TO: ';
            foreach ($recipients as $key => $value) {
                $subject .= $key.'+';
            }
            $this->message->ClearAllRecipients();
            $this->message->addAddress($this->config['admin_email']);
            //echo $subject;
            $this->message->Subject = $subject;
        }
        $this->message->send();
    }
}