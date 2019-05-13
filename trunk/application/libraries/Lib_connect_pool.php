<?php
/**
 * 连接池
 */
class Lib_connect_pool
{
    //连接引用数组
    static $_instance=array();
    
    /**
     * 获取引用
     */ 
    public static function get_instance($name,$group='database')
    {
        if(empty(self::$_instance) || !isset(self::$_instance[$group][$name]))
        {
            $CI = & get_instance();
            if($group == 'database')
            {
                self::$_instance[$group][$name] = $CI->load->database($name, TRUE);
            }
        }
        return self::$_instance[$group][$name];
    }
}