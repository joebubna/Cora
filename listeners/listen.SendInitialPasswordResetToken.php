<?php
namespace Listener;

class SendInitialPasswordResetToken extends \Cora\Listener
{
    public function handle($event)
    {
        $user = $event->user;
        $mail = $event->mailer;
        $load = $event->load;
        
        $mail->setFrom('server@localhost.com');
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