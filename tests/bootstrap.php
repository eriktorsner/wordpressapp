<?php
global $settings;
define('TESTMODE', true);
define('BASEPATH', dirname(__DIR__));
require_once BASEPATH.'/vendor/autoload.php';

$settings = new Wordpressapp\Settings();

