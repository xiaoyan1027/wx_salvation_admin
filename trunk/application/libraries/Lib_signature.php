<?php
/**
 * 签名算法
 */
class Lib_signature 
{
	public function __construct()
    {

    }
    /**
     * 签名算法
     */ 
    function create_sign($token, $data)
	{
	    if(empty($data) || empty($token))
	    {
			return false;
		}
		ksort($data);
		$tmpstr = http_build_query($data);
		$sign = md5($tmpstr.$token);
		return $sign;		
	}
    
    /**
     * 签名校验token方法
     * @param   array   $search_cond    查询条件
     * @param   string  $key    业务Key
     * @return  string  数据签名token
     */
    function get_check($search_cond, $key){
        $string = '';
        if(is_array($search_cond)){
            foreach ($search_cond as $v){
                $string .= $v;
            }
        }else{
            $string = $search_cond;
        }
        $token = md5($string.$key);
        return $token;
    }

    /*
     * 签名算法
     * */
    function get_sign($params = array())
    {
        if (empty($params)) {
            return '';
        }
        $sKey = "+#~Cn8KN"; // 加密因子
        ksort($params); // 升序排序
        $str = '';
        foreach ($params as $val) {
            $str .= $val; // 参数拼接
        }
        return md5($str.$sKey);// 再拼接加密因子 MD5加密
    }

    /*
     * 生成sign
     * */
    public function _create_sign($token, $data)
    {
        if(empty($data) || empty($token) || empty($data['timestamp']))
        {
            return false;
        }
        ksort($data);
        $tmpstr = http_build_query($data);
        $sign = md5($tmpstr.$token);
        return $sign;
    }
    /**
     * 计算签名
     * @author
     *
     */
    public function create_scar_sign($data, $key = '')
    {
        if(is_array($data)){
            ksort($data);//按照键值排序
        }

        $string = $this->getPostString($data);
        return md5($string.$key);
    }
    /**
     * 签名
     *
     */
    protected function getPostString(&$post)
    {
        $string = '';
        if(is_array($post))
        {
            foreach($post as $item)
            {
                if(is_array($item))
                    $string .= $this->getPostString($item);
                else
                    $string .= $item;
            }
        }
        else
        {
            $string = $post;
        }
        return $string;
    }

    /**
     *
     * @param array $params
     * @param int $appId
     * @param string $encrypt_method
     * @return bool
     */
    public function _sign($params=array(), $appId = 0, $encrypt_method = 'md5')
    {
        $str = '';
        if (!empty($params)) {
            ksort($params);
            foreach( $params as $k => $v )
            {
                if ($str == '')
                {
                    $str .= $k . '=' . $v ;
                }
                else
                {
                    $str .= '&' . $k . '=' . $v ;
                }
            }
        }
        return $encrypt_method($str . "JjKoBcJ1#*12HBiM");
    }


}
