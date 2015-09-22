<?php
define('BASEPATH', dirname(__DIR__));
if (isset($argv[1]) && $argv[1] == 'test') {
    define('TESTMODE', true);
}
require_once __DIR__."/src/Settings.php";
$localSettings = new Settings();
$bootstrapSettings = json_decode(file_get_contents(__DIR__.'/settings.json'));

require_once $localSettings->wppath."/wp-load.php";
require_once __DIR__."/src/Pushposts.php";
require_once __DIR__."/src/Pushmenus.php";
require_once __DIR__."/src/Resolver.php";
require_once $localSettings->wppath."/wp-admin/includes/image.php";

global $pages, $posts, $menus;

// pages
$pages = new Pushposts('pages');
$menues = new Pushmenus();

Resolver::resolveReferences();

function findTargetPostId($target)
{
    global $pages;

    foreach ($pages->posts as $page) {
        if ($page->meta->ID == $target) {
            return $page->id;
        }
    }

    return 0;
}
