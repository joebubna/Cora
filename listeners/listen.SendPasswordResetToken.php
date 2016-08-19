<?php
namespace Listener;

class SendPasswordResetToken extends \Cora\Listener
{
    public function handle($event)
    {
        $user = $event->user;
        $mail = $event->mailer;
        
        $mail->setFrom('server@tinnitusnetwork.com');
        $mail->addAddress($user->email);
        
        $mail->Subject = 'Password Reset';         
        $mail->Body = 'Password Reset Link:<br>'.$this->getLink($user->token);;
        $mail->send();
    }
    
    protected function getLink($token)
    {
        $message = '';
        $url = $this->config['base_url'].$this->config['site_url'].'users/forgotPasswordVerify/?token='.$token;
        
        // Determine if should use HTTPS or not.
        if ($this->config['mode'] == 'development') {
            $message .= '<a href="http://';
        }
        else {
            $message .= '<a href="https://';
        }
        
        $message .= $url.'">'.$url.'</a>';
        
        return $message;
    }
}