<?php

add_action('wp_enqueue_scripts', 'theme_enqueue_styles');
add_action('init', 'wordpressapp_blocknormalusers_init');

function theme_enqueue_styles()
{
    wp_enqueue_style('parent-style', get_template_directory_uri().'/style.css');
    wp_enqueue_style('child-style', get_stylesheet_directory_uri().'/style.css', array('parent-style'));
}

function wordpressapp_blocknormalusers_init()
{
    if (!current_user_can('administrator')) {
        add_filter('show_admin_bar', '__return_false');
        if (is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) {
            wp_redirect(home_url());
            exit;
        }
    }
}


