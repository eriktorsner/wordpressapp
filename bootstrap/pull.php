<?php
$localSettings = json_decode(file_get_contents(__DIR__.'/../localsettings.json'));
$bootstrapSettings = json_decode(file_get_contents(__DIR__.'/settings.json'));

require_once $localSettings->wppath."/wp-load.php";
global $wpdb;

// pages
foreach ($bootstrapSettings->pages as $page) {
    $pageId = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s", $page));
    $obj = get_post($pageId);
    $obj->page_template_slug = basename(get_page_template_slug($pageId));

    $dir = __DIR__.'/pages/'.$page;
    @mkdir($dir, 0777, true);
    file_put_contents($dir.'/meta', serialize($obj));

    $pageContent = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM $wpdb->posts WHERE id = %s", $pageId));
    file_put_contents($dir.'/content', $pageContent);
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
        file_put_contents($file, serialize($obj));
    }
}
