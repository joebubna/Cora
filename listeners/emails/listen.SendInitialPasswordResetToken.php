<?php
namespace Listeners\Emails;

class SendInitialPasswordResetToken extends \Cora\Listener
{
    protected $mailer;
    protected $load;
    
    public function __construct($mailer, $load)
    {
        parent::__construct();
        $this->mailer = $mailer;
        $this->load = $load;
    }
    
    public function handle($event)
    {
        $user = $event->user;
        $mail = $this->mailer;
        $load = $this->load;
        
        $mail->setFrom('server@tinnitusnetwork.com');
        $mail->addAddress($user->email);
        
        $mail->Subject = 'Password Reset';
        $this->data->resetLink = $this->getLink($user->id, $user->resetToken);
        $mail->Body = $load->view('emails/initialPasswordReset', $this->data, true);
        $mail->send();
    }
    
    protected function getLink($user_id, $token)
    {
        $message = '';
        $url = $this->config['base_url'].$this->config['site_url'].'users/forgotPasswordVerify/?'.
                'token='.$token.
                '&id='.$user_id;
        
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