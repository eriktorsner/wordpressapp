<?php
define('BASEPATH', dirname(__DIR__));
if (isset($argv[1]) && $argv[1] == 'test') {
    define('TESTMODE', true);
}
require_once __DIR__."/src/Settings.php";
$localSettings = new Settings();
$bootstrapSettings = json_decode(file_get_contents(__DIR__.'/settings.json'));

require_once $localSettings->wppath."/wp-load.php";
require_once __DIR__."/src/Resolver.php";
global $wpdb;
$uploadDir = wp_upload_dir();
$baseUrl = get_option('siteurl');
$neutralUrl = 'NEUTRALURL';

recursiveRemoveDirectory(__DIR__.'/pages/');
recursiveRemoveDirectory(__DIR__.'/media/');
recursiveRemoveDirectory(__DIR__.'/menus/');

// pages
foreach ($bootstrapSettings->pages as $page) {
    $pageId = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s", $page));
    $obj = get_post($pageId);
    unset($obj->post_content);
    $obj->page_template_slug = basename(get_page_template_slug($pageId));

    $dir = __DIR__.'/pages/'.$page;
    @mkdir($dir, 0777, true);
    Resolver::field_search_replace($obj, $baseUrl, $neutralUrl);
    file_put_contents($dir.'/meta', serialize($obj));

    $pageContent = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM $wpdb->posts WHERE id = %s", $pageId));
    Resolver::field_search_replace($pageContent, $baseUrl, $neutralUrl);
    file_put_contents($dir.'/content', $pageContent);
}

// media for pages
foreach ($bootstrapSettings->pages as $page) {
    $pageId = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s", $page));
    $media = get_attached_media('', $pageId);
    foreach ($media as $item) {
        $itemMeta = wp_get_attachment_metadata($item->ID, true);
        $item->meta = $itemMeta;
        $dir = __DIR__.'/media/'.$item->post_name;
        @mkdir($dir, 0777, true);
        file_put_contents($dir.'/meta', serialize($item));
        $src = $uploadDir['basedir'].'/'.$itemMeta['file'];
        $trg = $dir.'/'.basename($itemMeta['file']);
        copy($src, $trg);
    }
}

//menus
foreach ($bootstrapSettings->menus as $menu) {
    wp_set_current_user(1);
    $loggedInmenuItems = wp_get_nav_menu_items($menu->name);
    wp_set_current_user(0);
    $notloggedInmenuItems = wp_get_nav_menu_items($menu->name);
    $menuItems = array_merge($loggedInmenuItems, $notloggedInmenuItems);

    $dir = __DIR__.'/menus/'.$menu->name;
    array_map('unlink', glob("$dir/*"));

    @mkdir($dir, 0777, true);
    foreach ($menuItems as $menuItem) {
        $obj = get_post($menuItem->ID);
        $obj->postMeta = get_post_meta($obj->ID);
        $file = $dir.'/'.$menuItem->post_name;
        Resolver::field_search_replace($obj, $baseUrl, $neutralUrl);
        file_put_contents($file, serialize($obj));
    }
}

function recursiveRemoveDirectory($directory)
{
    foreach (glob("{$directory}/*") as $file) {
        if (is_dir($file)) {
            recursiveRemoveDirectory($file);
        } else {
            unlink($file);
        }
    }
    rmdir($directory);
}
