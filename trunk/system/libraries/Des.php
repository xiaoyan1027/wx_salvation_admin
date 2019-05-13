<?php
/**
 * DES2加密解密类
 * @author  nmx
 * @example
 * $this->des2->key='leju_weixin';
 * $this->des2->iv='12345678';
 * echo  $this->des2->encrypt("nnn");
 */ 
 
defined('BASEPATH') OR exit('No direct script access allowed');
 
class CI_Des
{
    public $key='leju_zt';
    public $iv='08460801'; //偏移量
    //加密
    public function encrypt($input)
    {
        $input = $this->padding( $input );
        $key = base64_decode($this->key);
        $td = mcrypt_module_open( MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        //使用MCRYPT_3DES算法,cbc模式
        mcrypt_generic_init($td, $key, $this->iv);
        //初始处理
        $data = mcrypt_generic($td, $input);
        //加密
        mcrypt_generic_deinit($td);
        //结束
        mcrypt_module_close($td);
        $data = $this->remove_br(str_replace("+","-",base64_encode($data)));
        return $data;
    }
   
    //解密
    public function decrypt($encrypted)
    {
        $encrypted = str_replace("-","+",$encrypted);
        $encrypted = base64_decode($encrypted);
        $key = base64_decode($this->key);
        $td = mcrypt_module_open( MCRYPT_3DES,'',MCRYPT_MODE_CBC,'');
        //使用MCRYPT_3DES算法,cbc模式
        mcrypt_generic_init($td, $key, $this->iv);
        //初始处理
        $decrypted = mdecrypt_generic($td, $encrypted);
        //解密
        mcrypt_generic_deinit($td);
        //结束
        mcrypt_module_close($td);
        $decrypted = $this->remove_padding($decrypted);
        return $decrypted;
    }
   
    //填充密码，填充至8的倍数
    public function padding( $str )
    {
        $len = 8 - strlen( $str ) % 8;
        for ( $i = 0; $i < $len; $i++ )
        {
            $str .= chr( 0 );
        }
        return $str ;
    }
   
    //删除填充符
    public function remove_padding( $str )
    {
        $len = strlen( $str );
        $newstr = "";
        $str = str_split($str);
        for ($i = 0; $i < $len; $i++ )
        {
            if ($str[$i] != chr( 0 ))
            {
                $newstr .= $str[$i];
            }
        }
        return $newstr;
    }
   
    //删除回车和换行
    public function remove_br( $str )
    {
        $len = strlen( $str );
        $newstr = "";
        $str = str_split($str);
        for ($i = 0; $i < $len; $i++ )
        {
            if ($str[$i] != '\n' and $str[$i] != '\r')
            {
                $newstr .= $str[$i];
            }
        }
   
        return $newstr;
    }
}
?>