<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Table_Model extends BASE_Model {

    //数据表引用
    static $_instance=array();
    public function __construct($table='',$active_group='default')
    {
        parent::__construct($table,$active_group);
    }
    /**
     * 获取引用
     */ 
    public static function get_instance($table,$active_group='default')
    {
        if(empty(self::$_instance) || !isset(self::$_instance[$active_group][$table]))
        {
            self::$_instance[$active_group][$table] = new self($table,$active_group);
        }
        return self::$_instance[$active_group][$table];
    }
}
