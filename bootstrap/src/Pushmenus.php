<?php

class Pushmenus
{
    public $menus = [];
    private $skipped_meta_fields = [
        '_menu_item_menu_item_parent', '_menu_item_object_id', '_menu_item_object',
    ];

    public function __construct()
    {
        global $bootstrapSettings;
        foreach ($bootstrapSettings->menus as $menu) {
            $dir = __DIR__.'/../menus/'.$menu->name;
            $newMenu = new stdClass();
            $newMenu->slug = $menu->name;
            $newMenu->location = $menu->location;
            $newMenu->items = [];
            foreach ($this->getFiles($dir) as $file) {
                $menuItem = new \stdClass();
                $menuItem->done = false;
                $menuItem->id = 0;
                $menuItem->parentId = 0;
                $menuItem->slug = $file;
                $menuItem->meta = unserialize(file_get_contents($dir.'/'.$file));
                $newMenu->items[] = $menuItem;
            }
            usort($newMenu->items, function ($a, $b) {
                return (int) $a->meta->menu_order - (int) $b->meta->menu_order;
            });
            $this->menus[] = $newMenu;
        }
        $baseUrl = get_option('siteurl');
        $neutralUrl = 'NEUTRALURL';
        Resolver::field_search_replace($this->menus, $neutralUrl, $baseUrl);
        $this->process();
    }

    private function process()
    {
        $locations = [];
        foreach ($this->menus as $menu) {
            $this->processMenu($menu);
            $locations[$menu->location] = $menu->id;
        }
        set_theme_mod('nav_menu_locations', $locations);
    }

    private function processMenu(&$menu)
    {
        $objMenu = wp_get_nav_menu_object($menu->slug);
        if (!$objMenu) {
            $id = wp_create_nav_menu($menu->slug);
            $objMenu = wp_get_nav_menu_object($menu->slug);
        }
        $menuId = $objMenu->term_id;
        $menu->id = $menuId;

        wp_set_current_user(1);
        $loggedInmenuItems = wp_get_nav_menu_items($menu->slug);
        wp_set_current_user(0);
        $notloggedInmenuItems = wp_get_nav_menu_items($menu->slug);
        $existingMenuItems = array_merge($loggedInmenuItems, $notloggedInmenuItems);
        foreach ($existingMenuItems as $existingMenuItem) {
            $ret = wp_delete_post($existingMenuItem->ID, true);
        }

        foreach ($menu->items as &$objMenuItem) {
            $newTarget = findTargetPostId($objMenuItem->meta->postMeta['_menu_item_object_id'][0]);
            $parentItem = $this->findMenuItem($objMenuItem->meta->postMeta['_menu_item_menu_item_parent'][0]);

            $args = [
                    'menu-item-title'       =>  $objMenuItem->meta->post_title,
                    'menu-item-position'    =>  $objMenuItem->meta->menu_order,
                    'menu-item-description' =>  $objMenuItem->meta->post_content,
                    'menu-item-attr-title'  =>  $objMenuItem->meta->post_title,
                    'menu-item-status'      =>  $objMenuItem->meta->post_status,
                    'menu-item-type'        =>  $objMenuItem->meta->postMeta['_menu_item_type'][0],
                    'menu-item-object'      =>  $objMenuItem->meta->postMeta['_menu_item_object'][0],
                    'menu-item-object-id'   =>  $newTarget,
                    'menu-item-url'         =>  $objMenuItem->meta->postMeta['_menu_item_url'][0],
                    'menu-item-parent-id'   =>  $parentItem,
            ];
            $ret = wp_update_nav_menu_item($menuId, 0, $args);
            $objMenuItem->id = $ret;

            foreach ($objMenuItem->meta->postMeta as $key => $meta) {
                if (in_array($key, $this->skipped_meta_fields) || substr($key, 0, 1) == '_') {
                    continue;
                }
                $val = $meta[0];
                update_post_meta($ret, $key, $val);
            }
        }
    }

    private function findMenuItem($target)
    {
        foreach ($this->menus as $menu) {
            foreach ($menu->items as $item) {
                if ($item->meta->ID == $target) {
                    return $item->id;
                }
            }
        }

        return 0;
    }

    private function getFiles($folder)
    {
        $files = scandir($folder);
        foreach ($files as $file) {
            if ($file != '..' && $file != '.') {
                $ret[] = $file;
            }
        }

        return $ret;
    }
}
