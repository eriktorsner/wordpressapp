<?php
use Rocketeer\Facades\Rocketeer;

Rocketeer::listenTo('deploy.before-symlink', function ($task) {
    $currentRelease = $task->runForCurrentRelease('pwd');
    $installationRoot = dirname(dirname($currentRelease));
    $shared = $installationRoot.'/shared';
    /*
     * Make localsettings available for the Gruntfile in current release
     */
    $task->run('ln -s '.$shared.'/localsettings.json '.$currentRelease.'/localsettings.json');
    /*
     * Make sure Wordpress is installed and setup
     */
    $task->runForCurrentRelease('grunt wp-install');
    $task->runForCurrentRelease('grunt wp-setup');
    $task->runForCurrentRelease('grunt wp-import');

});

