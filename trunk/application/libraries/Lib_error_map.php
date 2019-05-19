<?php
/***************************************************
*
*   Filename: application/libraries/Lib_error_map.php
*
*   Description: 错误字典
*   Create: 2018-07-04 05:33:20
****************************************************/

class Lib_error_map
{


    // 错误字典
    protected static $err_map = array(

        // xxx
        '500' =>'登录超时',


    );

    /**
     * 获取错误信息
     * @param  int   $code
     * @return array 一维数组
     */
    public static function get_err_msg($code)
    {
    
        if ( !isset(self::$err_map[$code]) ) {
        
            return array('code'=>$code, 'error_msg'=>'未定义' . $code);
        }
        
        return array('code'=>$code, 'error_msg'=>self::$err_map[$code]);
    }
}
