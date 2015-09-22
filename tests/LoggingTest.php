<?php

class LoggingTest extends \PHPUnit_Framework_TestCase
{
    public function test_LogUserRegister()
    {
        global $settings;
        require_once $settings->wppath . '/wp-load.php';

        $before = count(file($settings->logpath));
        do_action('user_register', 99);
        $after = count(file($settings->logpath));

        $this->assertTrue($after > $before);
	
    }
}
