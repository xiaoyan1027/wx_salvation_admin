<?php
/**
 * 转义数据
 * @param string|array $string
 * @param int $force
 */
function daddslashes($string, $force = 0)
{
	if (!get_magic_quotes_gpc() || $force)
	{
		if (is_array($string))
		{
			foreach ($string as $key => $val)
			{
				$string[$key] = daddslashes($val, $force);
			}
		}
		else
		{
			$string = addslashes($string);
		}
	}
	return $string;
}

/**
 * 转义字符编码
 * @param string $in_charset
 * @param string $out_charset
 * @param array $arr
 */
function iconv_array($in_charset, $out_charset, $arr)
{
	if (strtolower($in_charset) == "utf8")
	{
		$in_charset = "UTF-8";
	}
	if (strtolower($out_charset) == "utf8")
	{
		$out_charset = "UTF-8";
	}
	if (is_array($arr))
	{
		foreach ($arr as $key => $value)
		{
			$arr[$key] = iconv_array($in_charset, $out_charset, $value);
		}
	}
	else
	{
		$arr = @iconv($in_charset, $out_charset, $arr);
	}
	return $arr;
}


/**
 * 获取字符长度
 * 全角作为一个字符
 * @param string $str
 * @return number
 */
function get_length($str)
{
	$len = strlen($str);
	$strlen = $len;
	for($i = 0; $i < $len; $i++)
	{
		if(ord($str[$i])>128)
		{
			$strlen = $strlen-1;
			$i++;
		}
	}
	return $strlen;
}
/**
 * 加密，解密方法
 * @author xiaoping1@leju.sina.com.cn
 * @param string $string
 * @param string $operation encode|decode
 * @param string $key
 * @return string
 */
function my_crypt($string, $key, $operation = 'encode') {
    $keyLength = strlen($key);
    $string = ($operation == strtolower('decode'))
        ? base64_decode($string)
        : substr(md5($string . $key), 0, 8) . $string;
    $stringLength = strlen($string);

    $rndkey = $box = array();
    $result = '';

    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($key[$i % $keyLength]);
        $box[$i] = $i;
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $stringLength; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if ($operation == strtolower('decode')) {
        if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key), 0, 8)) {
            return substr($result, 8);
        } else {
            return '';
        }
    } else {
        return base64_encode($result);
    }
}
/**
 * 获取客户端IP
 *
 * @return string
 */
function get_client_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
	{
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
	    $ip = $_SERVER['REMOTE_ADDR'];
	}
	
	$valid_res = filter_var($ip, FILTER_VALIDATE_IP);
	if ($valid_res)
	{
	    return $ip;
	}
	else
	{
	    return $_SERVER["REMOTE_ADDR"];
	}
}



/**
 * 去掉字符两端空格
 * @param unknown_type $string
 */
function trims($string)
{
    if (is_array($string))
    {
        foreach ($string as $key=>$val)
        {
            $string[$key] = trims($val);
        }
    }
    else
    {
        $string = trim($string);
    }
    
    return $string;
}

/**
 * 处理HTML输出字符
 * @param string $string
 * @param int $force
 * @return string
 */
function dhtmlspecialchars($string, $force = 0)
{
	!defined('MAGIC_QUOTES_GPC') && define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
	if (!MAGIC_QUOTES_GPC || $force)
	{
		if (is_array($string))
		{
			foreach ($string as $key => $val)
			{
				$string[$key] = dhtmlspecialchars($val, $force);
			}
		}
		else
		{
			$string = htmlspecialchars($string);
		}
	}
	return $string;
}

/**
 * 循环创建目录
 *
 * @param string $dir
 * @param int $mode
 * @return boolean
 */
function mk_dir($dir, $mode = 0755)
{
    if (is_dir($dir) || @mkdir($dir,$mode))
    {
        return true;
    }
    if (!mk_dir(dirname($dir),$mode)) {
        return false;
    }
    return @mkdir($dir,$mode);
}


/**
 * 二维数组按指定字段排序
 * @param array $arr
 * @param string $shortKey
 * @param string $short
 * @param string $shortType
 * @return array
 */
function multi_array_sort($arr,$shortKey,$short=SORT_DESC,$shortType=SORT_REGULAR)
{
    foreach ($arr as $key => $data){
        $name[$key] = $data[$shortKey];
    }
    array_multisort($name,$shortType,$short,$arr);
    return $arr;
}
/**
 * 解决 unserialize出现Error at offset 错误
 * @param unknown $string
 */
function dunserialize($string)
{
    return unserialize(preg_replace('!s:(\d+):"(.*?)";!se', '"s:".strlen("$2").":\"$2\";"', $string));
}
/**
 * 获取文件后缀
 */ 
function get_file_suffix($file)
{
    $suffix	= strtolower(substr($file, (strrpos($file,".",1)+1)));
    return $suffix;
}

if (!function_exists('array_column')) {
    function array_column($input, $column_key, $index_key = '') {
        $result = array();
        foreach ($input as $k => $v) {
            if (empty($index_key)) {
                $result[] = $v[$column_key];
            } else {
                $result[$v[$index_key]] = $v[$column_key];
            }
        }
        return $result;
    }
}

/**
 * 获取分页输出模板
 *
 * @param $count
 * @param $limit
 * @param $url
 * @param array $params
 */
function get_pager_html($count, $limit = 20, $url = '', $params = array()) {
    $CI = &get_instance();

    $query_arr = $_REQUEST;
    unset($query_arr['per_page']);
    $str = http_build_query($query_arr);
    $config['page_query_string'] = TRUE;
    $config['base_url'] = $url ?: './' . $CI->router->method . '?' . $str;
    $config['total_rows'] = $count;
    $config['per_page'] = $limit;
    $config['num_links'] = 5;
    $config['first_link'] = '首页';
    $config['last_link'] = '末页';
    $config['full_tag_open'] = '<ul class="pagination">';
    $config['full_tag_close'] = '<ul>';
    $config['prev_tag_open'] = '<li class="paginate_button previous"> ';
    $config['prev_tag_close'] = '</li>';
    $config['prev_link'] = '上一页';
    $config['next_tag_open'] = '<li class="paginate_button next"> ';
    $config['next_tag_close'] = '</li>';
    $config['next_link'] = '下一页';
    $config['num_tag_open'] = '<li class="paginate_button ">';
    $config['num_tag_close'] = '</li>';
    $config['cur_tag_open'] = '<li class="paginate_button active"><a>';
    $config['cur_tag_close'] = '</a></li>';

    $config['last_tag_open'] = '<li class="paginate_button"> ';
    $config['last_tag_close'] = '</li>';

    $config['first_tag_open'] = '<li class="paginate_button"> ';
    $config['first_tag_close'] = '</li>';


    $CI->pagination->initialize($config);
    $pagination = $CI->pagination->create_links();
    return $pagination;
}

/**
 * json错误输出
 *
 * @param string $message
 * @param string $code
 * @param array $data
 * @param string $url
 */
function output_error($code = '0', $message = '', $data = array(), $url = '') {
    $return = array(
        'status' => 'false',
        'code' => $code,
        'info' => $message,
        'data' => $data,
        'url' => $url,
    );

    header('Content-Type:application/json; charset=utf-8');
    echo json_encode($return);
    exit;
}


/**
 * json输出
 *
 * @param string $message
 * @param string $code
 * @param array $data
 * @param string $url
 */
function output_ok($message = '', $data = array(), $code = '200', $url = '') {
    $return = array(
        'status' => 'true',
        'code' => $code,
        'info' => $message,
        'data' => $data,
        'url' => $url,
    );

    header('Content-Type:application/json; charset=utf-8');
    echo json_encode($return);
    exit;
}


/**
 * 根据指定字段获取列表数据
 * @param array $source
 * @param array $keys
 * @param string $action intersect|diff
 * @return multitype:multitype:
 */
function get_list_by_keys($source, $keys, $action='intersect')
{
    if(!is_array($source) || !is_array($keys) || count($source) == 0) return false;
    $keys = array_fill_keys($keys, '');
    $result = array();
    foreach($source as $key => $value)
    {
        if($action == 'intersect')
        {
            $result[$key] = array_intersect_key($value, $keys);
        }
        else
        {
            $result[$key] = array_diff_key($value, $keys);
        }
    }
    return $result;
}


/**
* 缓存键名池
* @author  mingxing
* @param   string  $name   键名
* @param   array   $data   替换数据
* @return  string
*/
function get_cache_key($name,$data=array(),$type='redis')
{
    $cache_prefix = 'xcx_v1_';
    $CI =& get_instance();
    $cache_key = '';
    if($type == 'redis')
    {
        if ($CI->config->load('redis', TRUE, TRUE))
    	{
    		$config = $CI->config->item($type);
    	}
    }
    $key_set = $config['key_set'];
    if(isset($key_set[$name]))
    {
        if(is_string($key_set[$name]))
        {
            $cache_key = $cache_prefix.$key_set[$name];
            $result = $cache_key;
        }
        elseif(is_array($key_set[$name]))
        {
            $cache_key = $cache_prefix.$key_set[$name]['key'];
            $result['expire'] = $key_set[$name]['expire'];
            $result['key'] = $cache_key;
        }
        
        if(empty($data)) return $result;
        array_unshift($data,$cache_key);
        $cache_key = call_user_func_array("sprintf",$data);
        if(isset($result['key']))
        {
            $result['key'] = $cache_key;
        }
        else
        {
            $result = $cache_key;
        }
    }
    return $result;
}



/**
 * 手机号验证
 */ 
function is_mobile($mobile)
{
    if(preg_match("/^1[3456789]{1}\d{9}$/",$mobile))
    {
        return TRUE;
    }
    return FALSE;
}

function md5_16($str = '')
{
    return substr(md5($str ? $str : uniqid(mt_rand())),8,16);
}
/**
 * 构造url
 * @param   string  $url    url地址
 * @param   array   $params url参数
 * @return  string
 */
function build_url($url,$params='')
{
    if(stripos($url,"?")===false)
    {
        $url .= $params ? ("?".http_build_query($params)) : '';
    }
    else
    {
        $url .= $params ? "&".(http_build_query($params)) : '';
    }
    return $url;
}
if ( ! function_exists('infinite_assort'))
{
    /**
	 * 对二维数组进行无限极分类函数(一定要存在父子关系)
	 *
	 * @param array $array			二维数组
	 * @param int $parent			父类ID	
	 * @param int $level			层次号
	 * @param string $parentKeyName	父类键名	
	 * @param string $sonKeyName	子类键名
	 * @param mixed	&$result		保存最终结果
	 */
	function  infinite_assort($array,$parent,$level,$parentKeyName,$sonKeyName,&$result,$levelName="level",$mode=1,$fields="")
	{
	    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
	    if($mode==1)
        {
			for($i=0;$i<count($array);$i++)
			{
				if($array[$i][$parentKeyName]==$parent)
				{
					$array[$i][$levelName]=$level;
					$sub[]=$array[$i];
				}
			}
			for($j=0;$j<count($sub);$j++)
			{
				$result[]=$sub[$j];
				infinite_assort($array,$sub[$j][$sonKeyName],$level+1,$parentKeyName,$sonKeyName,$result,$levelName,$mode,$fields);
			}
        }
        else if($mode==2)
        {
            for($i=0;$i<count($array);$i++)
			{
				if($array[$i][$parentKeyName]==$parent)
				{
					$array[$i][$levelName]=$level;
					$sub[]=$array[$i];
				}
			}
            
			for($j=0;$j<count($sub);$j++)
			{
			    if(!empty($fields))
                {
                    foreach($fields as $k=>$v)
                    {
                        $temp[$v]=$sub[$j][$k];
                    }
                    $result["children"][]=$temp;
                }
                else
                {
                    $result["children"][]=$sub[$j];
                }
				
				infinite_assort($array,$sub[$j][$sonKeyName],$level+1,$parentKeyName,$sonKeyName,$result["children"],$levelName,$mode,$fields);
			}
        }
	}
}
/**
 * 用数组值重新定义数组键名
 */ 
function array_key_rewrite($fields,$data)
{
    $result = array();
    if(is_string($fields))
    {
        foreach($data as $k=>$v)
        {
            $result[$v[$fields]] = $v;
        }
    }
    return $result;
}
/**
 * 打印调试
 * @param mixed
 */
function p($data)
{
    echo '<pre>';
    var_dump($data);
}

function array_fields($input, $column_key, $index_key = '') {
    $result = array();
    foreach ($input as $k => $v) {
        if (empty($index_key)) {
            $result[] = $v[$column_key];
        } else {
            $result[$v[$index_key]] = $column_key ? $v[$column_key] : $v;
        }
    }
    return $result;
}
/**
 * 判断是否为null值，并去除空格
 * @author      guojun5@leju.com  2018-07-17
 * @input       需要转换的数据
 * @$default    需要替换后的数据
 *
 */
if(!function_exists('isnull'))
{
    function isnull($input,$default='')
    {
        if(is_null($input))
        {
            return $default;
        }elseif (is_string($input) || is_integer($input)) {
            $input = trim($input);
            return $input;
        }else{
            return $input;
        }
    }
}

/**
 * 一维数组合并相同键值的数据
 * @param $arr
 * @return  array
 * Author: zhangxin11@leju.com
 */
function comm_sumarrs($arr1,$arr2,$type= ""){

    foreach($arr1 as $k=>$v) {
        foreach($arr2 as $k1=>$v2) {
            if($k == $k1) {
                $data[] = $v.$type.$v2;
            }
        }
    }

    return $data;
}
