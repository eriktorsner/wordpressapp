<?php
/*
 * Plugin Name: WordpressApp
 * Plugin URI: http://notaurl.com
 * Description: Functionality for WordpressApp
 * Author: Torgesta Technology
 * Author URI: http://erik.torgesta.com
 * Version: 1.0.
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

define('BASEPATH', dirname(dirname(dirname(__DIR__))));
require_once(BASEPATH . '/vendor/autoload.php');

$objLogging = new Wordpressapp\Logging;
add_action( 'user_register', array($objLogging, 'LogUserRegister'), 10, 1 );
