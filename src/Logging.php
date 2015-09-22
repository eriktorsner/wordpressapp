<?php
namespace Wordpressapp;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Logging
{
    public function __construct()
    {
        $settings = json_decode(file_get_contents(BASEPATH.'/localsettings.json'));
        $this->logger = new Logger('WordpressApp');
        $this->logger->pushHandler(new StreamHandler(
            $settings->logpath,
            constant('Monolog\Logger::'.$settings->loglevel)
        ));
        $this->pid = getmypid();
    }

    public function LogUserRegister($user_id) 
    {
        $this->logger->addDebug('New user created', ['user_id' =>  $user_id, 'pid' => $this->pid]);
    }
}


