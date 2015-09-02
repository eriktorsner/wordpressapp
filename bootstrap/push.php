<?php
define('BASEPATH', dirname(__DIR__));
if (isset($argv[1]) && $argv[1] == 'test') {
    define('TESTMODE', true);
}

$localSettings = json_decode(file_get_contents(__DIR__.'/../localsettings.json'));
$bootstrapSettings = json_decode(file_get_contents(__DIR__.'/settings.json'));

require_once $localSettings->wppath."/wp-load.php";
require_once __DIR__."/src/Replace.php";
require_once __DIR__."/src/Pushposts.php";
require_once __DIR__."/src/Pushmenus.php";

global $pages, $posts, $menus;

// pages
$pages = new Pushposts('pages');
$menues = new Pushmenus();

