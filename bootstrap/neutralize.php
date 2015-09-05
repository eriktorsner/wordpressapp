<?php
$localSettings = json_decode(file_get_contents(__DIR__.'/../localsettings.json'));
$bootstrapSettings = json_decode(file_get_contents(__DIR__.'/settings.json'));

require_once $localSettings->wppath."/wp-load.php";
require_once __DIR__."/src/Resolver.php";

$wpcfmsource = $argv[1];
$baseUrl = get_option('siteurl');
$neutralUrl = 'NEUTRALURL';
$wpcfmsettings = json_decode(file_get_contents(__DIR__.'/'.$wpcfmsource));

foreach($wpcfmsettings as $setting => &$value) {
    if($setting == ".label") {
        continue;
    }
    Resolver::field_search_replace($value, $baseUrl, $neutralUrl);
}
file_put_contents(__DIR__.'/'.$wpcfmsource, json_encode($wpcfmsettings));

