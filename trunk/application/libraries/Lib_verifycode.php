<?php
/**
 * 验证码类
 *
 */
class lib_verifycode
{
    private $_width = 100;
    private $_height = 25;
    private $_code_num = 4;
    private $_code_key = '';
    private $logger_model;

    public $CI;
   // public $lib_memcache;
    
	public function __construct($code_key = '', $width= 100, $height = 25, $code_num = 4)
	{
	    if(empty($code_key))
	    {
	        $code_key = $this->get_cur_user_key();
	    }
	    $this->_code_key = md5('adminpet_' . $code_key);

		$this->CI = & get_instance();
	    
	    $this->_width = $width;
	    $this->_height = $height;
	    $this->_code_num = $code_num;
        $this->CI->load->model('api/logger_model');
        $this->logger_model = $this->CI->logger_model;
	}
	
	/**
	 * 随进获取验证码
	 */
	public function get_code()
	{
		$code_type = mt_rand(1, 3);

		switch($code_type)
		{
			case 1 :
			    $this->get_math_code();
			    break;
		    case 2 :
		        $this->get_num_code();
		        break;
	        case 3 :
	            $this->get_char_code();
	            break;
		}
	}

	/**
	 * 获取数学验证码
	 */
	public function get_math_code()
	{
    	$im = imagecreate($this->_width, $this->_height);
    
    	//imagecolorallocate($im, 14, 114, 180); // background color
    	$red = imagecolorallocate($im, 255, 0, 0);
    	$white = imagecolorallocate($im, 255, 255, 255);
    
    	$num1 = mt_rand(1, 20);
    	$num2 = mt_rand(1, 20);
    
    	//计算结果
    	$ari_type = mt_rand(1, 3);
    	switch ($ari_type)
    	{
    		case 1:
    		    $arithmetic = '+';
    		    $code_result = $num1 + $num2;
    		    break;
		    case 2:
		        $arithmetic = '-';
		        if($num1 < $num2)
		        {
		        	$test_num = $num1;
		        	$num1 = $num2;
		        	$num2 = $test_num;
		        }
		        $code_result = $num1 - $num2;
		        break;
		    case 3:
		        $arithmetic = 'x';
		        $num1 = mt_rand(1, 9);
		        $num2 = mt_rand(1, 9);
		        $code_result = $num1 * $num2;
		        break;
    	}    	
    	
    	//存储数据
    	//$this->lib_memcache->set($this->_code_key, $code_result, 1800);
    	$this->CI->lib_redis->set($this->_code_key, $code_result);
    	$this->CI->lib_redis->expire($this->_code_key, 1800);
    
    	$gray = imagecolorallocate($im, 118, 151, 199);
    	$black = imagecolorallocate($im, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));
    
    	//画背景
    	imagefilledrectangle($im, 0, 0, 100, 24, $black);
    	//在画布上随机生成大量点，起干扰作用;
    	for ($i = 0; $i < 80; $i++) {
    		imagesetpixel($im, rand(0, $this->_width), rand(0, $this->_height), $gray);
    	}
    
    	imagestring($im, 5, 5, 4, $num1, $red);
    	imagestring($im, 5, 30, 3, $arithmetic, $red);
    	imagestring($im, 5, 45, 4, $num2, $red);
    	imagestring($im, 5, 70, 3, "=", $red);
    	imagestring($im, 5, 80, 2, "?", $white);

    	header("Content-type: image/png");
    	imagepng($im);
    	imagedestroy($im);
    }
    
    /**
     * 数字验证码
     */
    public function get_num_code() 
    {        
        $code = "";
        for ($i = 0; $i < $this->_code_num; $i++) {
            $code .= rand(0, 9);
        }
        //4位验证码也可以用rand(1000,9999)直接生成
        //存储数据
    	//$this->lib_memcache->set($this->_code_key, $code, 1800);

	    $this->CI->lib_redis->set($this->_code_key, $code);
	    $this->CI->lib_redis->expire($this->_code_key, 1800);
    	
        //创建图片，定义颜色值
        Header("Content-type: image/PNG");
        $im = imagecreate($this->_width, $this->_height);
        $black = imagecolorallocate($im, 0, 0, 0);
        $gray = imagecolorallocate($im, 200, 200, 200);
        $bgcolor = imagecolorallocate($im, 255, 255, 255);
    
        imagefill($im, 0, 0, $gray);
    
        //画边框
        imagerectangle($im, 0, 0, $this->_width-1, $this->_height-1, $black);
    
        //随机绘制两条虚线，起干扰作用
        $style = array (
            $black,
            $black,
            $black,
            $black,
            $black,
            $gray,
            $gray,
            $gray,
            $gray,
            $gray
        );
        imagesetstyle($im, $style);
        $y1 = rand(0, $this->_height);
        $y2 = rand(0, $this->_height);
        $y3 = rand(0, $this->_height);
        $y4 = rand(0, $this->_height);
        imageline($im, 0, $y1, $this->_width, $y3, IMG_COLOR_STYLED);
        imageline($im, 0, $y2, $this->_width, $y4, IMG_COLOR_STYLED);
    
        //在画布上随机生成大量黑点，起干扰作用;
        for ($i = 0; $i < 80; $i++) {
            imagesetpixel($im, rand(0, $this->_width), rand(0, $this->_height), $black);
        }
        //将数字随机显示在画布上,字符的水平间距和位置都按一定波动范围随机生成
        $strx = rand(3, 8);
        for ($i = 0; $i < $this->_code_num; $i++) {
            $strpos = rand(1, 6);
            imagestring($im, 5, $strx, $strpos, substr($code, $i, 1), $black);
            $strx += rand(8, 12);
        }
        imagepng($im);
        imagedestroy($im);
    }
    
    /**
     * 生成字符验证码
     */
    public function get_char_code()
    {
        // 去掉了 0 1 O l 等
        $str = "23456789abcdefghijkmnpqrstuvwxyz";
        $code = '';
        for ($i = 0; $i < $this->_code_num; $i++) {
            $code .= $str[mt_rand(0, strlen($str)-1)];
        }
        
        //存储数据
        //$this->lib_memcache->set($this->_code_key, $code, 1800);
	    $this->CI->lib_redis->set($this->_code_key, $code);
	    $this->CI->lib_redis->expire($this->_code_key, 1800);



	    //创建图片，定义颜色值
        Header("Content-type: image/PNG");
        $im = imagecreate($this->_width, $this->_height);
        $black = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand(0, 120));
        $gray = imagecolorallocate($im, 118, 151, 199);
        $bgcolor = imagecolorallocate($im, 235, 236, 237);
    
        //画背景
        imagefilledrectangle($im, 0, 0, $this->_width, $this->_height, $bgcolor);
        //画边框
        imagerectangle($im, 0, 0, $this->_width-1, $this->_height-1, $gray);
        //imagefill($im, 0, 0, $bgcolor);    
    
        //在画布上随机生成大量点，起干扰作用;
        for ($i = 0; $i < 80; $i++) {
            imagesetpixel($im, rand(0, $this->_width), rand(0, $this->_height), $black);
        }
        //将字符随机显示在画布上,字符的水平间距和位置都按一定波动范围随机生成
        $strx = rand(3, 8);
        for ($i = 0; $i < $this->_code_num; $i++) {
            $strpos = rand(1, 6);
            imagestring($im, 5, $strx, $strpos, substr($code, $i, 1), $black);
            $strx += rand(8, 14);
        }
        imagepng($im);
        imagedestroy($im);
    }
    
    /**
     * 检查验证码
     */
    public function check_code($code)
    {
    	//$verify_code = $this->lib_memcache->get($this->_code_key);
    	$verify_code = $this->CI->lib_redis->get($this->_code_key);

	    if($verify_code && $verify_code == $code)
    	{
    		return true;
    	}
    	else 
    	{
            $params = array(
                'post_code'  => $code,
                'cache_code' => $verify_code,
                'write_file' => 'application/libraries/Lib_verifycode.php/',
            );
            $this->logger_model->fail(true, $params, 'post', 'manager/login/check_code');
    	    //删除验证码
    	    $this->CI->lib_redis->del($this->_code_key);

    	}
    	
    	return false;
    }
	
    /**
     * 获取当前用户唯一key
     */
    public function get_cur_user_key()
    {
    	//$cur_user_key = lib_cookie::getcookie('verfiy_code_user');
	    $cur_user_key = isset($_COOKIE['verfiy_code_user']) ? : '';
    	if(!$cur_user_key)
    	{
    	    $cur_user_key = md5(microtime() . rand(1111, 9999));
    	    $cur_user_key = substr($cur_user_key, 8, 16);
    	    setcookie('verfiy_code_user', $cur_user_key, 0, '/');
		//$this->CI->input->set_cookie('verfiy_code_user', $cur_user_key, 0, '/');
    	}
    	
    	return $cur_user_key;
    }
}
