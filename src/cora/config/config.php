<?php
#$config['debug'] = true;
#$config['debugHide'] = false;

// If you did not setup a virtual address, set this to "localhost".
$config['base_url'] = 'localhost';

// If not using a virtual address, this should be set to the directory name of the project.
$config['site_url'] = '/';

#$config['automatic_routing'] = false;

/**
 *  Email settings. Need to set these if you want to send emails.
 */
$config['smtp_host']     = 'smtp.gmail.com';
$config['smtp_port']     = 587;
$config['smtp_secure']   = 'tls';
$config['smtp_auth']     = 1;
$config['smtp_username'] = '';
$config['smtp_password'] = '';