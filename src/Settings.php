<?php
namespace Wordpressapp;

class Settings
{
    private $obj;

    public function __construct()
    {
        $this->obj = json_decode(file_get_contents(BASEPATH.'/localsettings.json'));
        if (defined('TESTMODE') && TESTMODE) {
            $this->obj->environment = 'test';
            $this->obj->wppath      = $this->obj->wppath_test;
            $this->obj->dbname      = $this->obj->dbname_test;
            $this->obj->url         = $this->obj->url_test;
        }
    }

    public function __get($name)
    {
        if (isset($this->obj->$name)) {
            return $this->obj->$name;
        }

        return;
    }
}
