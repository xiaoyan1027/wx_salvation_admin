<?php
/**
 * @todo	验证类
 * @version	v1.0.0
 */
class Lib_validate
{
	/**
	 * 各种验证规则
	 * @var <array>
	 */
	private static $_rules = array(
		'email'    => '/^[a-z0-9]+[._\-\+]*@([a-z0-9]+[-a-z0-9]*\.)+[a-z0-9]+$/',
		'url'      => '/^(http|https):\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',
		'currency' => '/^\d+(\.\d+)?$/',
		'mobile'   => '/^1[34578]\d{9}$/',
		'identityid' => '/^[A-Za-z0-9]{18}$|^[A-Za-z0-9]{15}$/',	//普通身份证
		//'hk_identityid' => '/^[A-Za-z]{1,2}[0-9]{6}[1-9Aa]{1}$/',	//香港身份证
		'hk_identityid' => '/^[A-Za-z]{1,}[0-9]{1,}$/',	//香港身份证
		'tw_identityid' => '/^[A-Za-z]{1}[1-2]\d{8}$/',	//台湾身份证
		'am_identityid' => '/^\d{8}$/',	//澳门身份证
		//'passport' => '/^(14|15)\d{7}$|^[Gg]\d{8}$|^[Pp]\d{7}$|^[Ss]\d{7,8}$|^[Dd]\d{1,10}$/',	//护照
		'passport' => '/^\d{1,}$|^[A-Za-z]{1,}\d{1,}$/',	//护照
		'is_chinese_character' => '/^[\x{4e00}-\x{9fa5}]+$/u'	//汉字
	);

	/**
	 * @todo	是否字母加数字
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_alnum( $value )
    {
        return ctype_alnum( $value );
    }

   /**
    * @todo	是否字母
    * @param <type> $value
    * @return <type>
    */
    public static function is_alpha( $value )
    {
        return ctype_alpha( $value );
    }

	/**
	 * @todo	是否数字(必须是字符串类型)ctype_digit('42')为true，ctype_digit(42)为false
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_digits( $value )
    {
        return ctype_digit( $value );
    }

	/**
	 * @todo	是否数字is_num('42')和is_num(42)均返回true
	 * @param <type> $value
	 * @param <type> $max
	 * @return <type>
	 */
	public static function is_num( $value, $max = null )
    {
    	$regexp = $max ? '/^\d{1,'.$max.'}$/' : '/^\d+$/';
    	return preg_match($regexp, $value);
    }

	/**
	 * 判断是否id的格式
	 * @param <int> $value
	 * @return <boolean>
	 */
	public static function is_id( $value )
	{
		$is_num = self::is_num( $value );
		if( $is_num )
		{
			return $value > 0;
		}
		return false;
	}

	/**
	 * @todo	是否email
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_email( $value )
	{
		return self::regx( $value, 'email' );
	}

	/**
	 * @todo	是否货币
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_currency( $value )
	{
		return self::regx( $value, 'currency' );
	}

	/**
	 * @todo	是否url
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_url( $value )
	{
		return self::regx( $value, 'url' );
	}

	/**
	 * @todo	是否手机号
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_mobile( $value )
	{
		return self::regx( $value, 'mobile' );
	}

	/**
	 * 是否有效数据（不为空即为有效）
	 * @param <string> $value
	 * @return boolean
	 */
	public static function is_effective( $value )
	{
		return !empty($value);
	}

	/**
	 * 是否不是空字符串
	 *
	 * @param string $value
	 * @return boolean
	 */
	public static function no_empty_str($value)
	{
	    return ('' != $value);
	}

	/**
	 * 是否大于0
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public static function great_zero($value)
	{
	    return ($value > 0);
	}

	/**
	 * @todo	通用验证
	 * @param <type> $value
	 * @param <type> $type
	 * @return <type>
	 */
	public static function regx( $value, $type )
	{
		$v_type  = strtolower( $type );
		$pattern = empty(self::$_rules[$v_type]) ? $type : self::$_rules[$v_type] ;
		if(!preg_match("/^\/.*?\/$/", $pattern))
		{
			return false;
		}
		$num = preg_match( $pattern, $value );
		if (false === $num || 0 === $num)
		{
			return false;
		} else
		{
		    return true;
		}
	}
    
    /**
     * 是否简体中文
     * @param string $str
     * @return bool 
     * @author
     */
    public static function is_gb($str)
    {
        $str = mb_convert_encoding( $str, 'gbk', 'utf-8');
        if (strlen($str) >= 2) {
            $str = strtok($str, '');
            if ((ord($str[0]) < 161) || (ord($str[0]) > 247)) {
                return false;
            } else {
                if ((ord($str[1]) < 161) || (ord($str[1]) > 254)) {
                    return false;
                } else {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * 是否繁体中文
     * @param string $str
     * @return bool
     * @author
     */
    public static function is_big5($str)
    {
        $str = iconv('utf-8', 'gbk//IGNORE', $str);
        if (strlen($str) >= 2) {
            $str = strtok($str, "");
            if (ord($str[0]) < 161) {
                return false;
            } else {
                if (((ord($str[1]) >= 64) && (ord($str[1]) <= 126)) || ((ord($str[1]) >= 161) && (ord($str[1]) <= 254))) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    /**
	 * @ignore
	 */
	public function  __call($name, $arguments)
	{
		return 'unkown method';	//错误信息不可修改
	}
	
	/**
	 * @todo	是否普通身份证
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_identityid( $value )
	{
		return self::regx( $value, 'identityid' );
	}
	
	/**
	 * @todo	是否香港身份证
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_hk_identityid( $value )
	{
		return self::regx( $value, 'hk_identityid' );
	}
	
	/**
	 * @todo	是否台湾身份证
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_tw_identityid( $value )
	{
		return self::regx( $value, 'tw_identityid' );
	}
	
	/**
	 * @todo	是否澳门身份证
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_am_identityid( $value )
	{
		return self::regx( $value, 'am_identityid' );
	}
	
	/**
	 * @todo	是否护照
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_passport( $value )
	{
		return self::regx( $value, 'passport' );
	}
	
	/**
	 * @todo	是否军官证
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_army_id( $value )
	{
		//$mb_strlen = mb_strlen($value, 'utf-8');
		//if ($mb_strlen != 10)
		//{
		//	return FALSE;	
		//}
		//
		////截取 前两个判断是否汉字
		//$before_two  = mb_substr($value, 0, 2, 'utf-8');
		//$is_chinese_character = preg_match(self::$_rules['is_chinese_character'], $before_two);
		//if (!$is_chinese_character)
		//{
		//	return FALSE;		
		//}
		//
		//$other_str = mb_substr($value, 2, $mb_strlen, 'utf-8'); 
		//if (!preg_match("/^\d{8}$/", $other_str))
		//{
		//	return FALSE;		
		//}
		$reg = "/^[\x{4e00}-\x{9fa5}]{1,}\d{1,}$|^[A-Za-z]{1,}\d{1,}$/u";
		$result = preg_match($reg, $value);		
		
		return $result;
	}
	
	/**
	 * @todo	是否汉字
	 * @param <type> $value
	 * @return <type>
	 */
	public static function is_chinese_character( $value )
	{
		$result = preg_match(self::$_rules['is_chinese_character'], $value);
		return $result;
	}
}