<?php
/**
 * 图片上传类
 */
//rest api
require_once( APPPATH . 'libraries/file/resourceapi.php' );
class Lib_upload extends ResourceApiClient
{
    //private $_pkey = '3a25d133456bf92b94262f9c4776171b';//test
    private $_pkey = '67474a25838d885cb04b61eb3a18c938';
    //private $_mkey = 'cd33159678ea24381c7f230412b306a5';//test
    private $_mkey = '0cffae80a14013170cc3ee260b31bfa9';

    private $_upload_name = 'Filedata';
    private $_pic_max_size = 2097152; //文件最大字节
    private $_ci;
    private $_logger_model;
    private $_ext = '';

    public function __construct($pkey = '', $mkey = '')
    {
        parent::__construct($this->_pkey, $this->_mkey);
        $this->_ci = & get_instance();
        $this->_ci->load->model('api/logger_model');
        $this->_logger_model = $this->_ci->logger_model;
    }
    
    /**
     * 上传图片
     * @param string $field_name
     * @return multitype:string
     */
    public function upload_pic($dir_name = 'leju_xcx', $field_name = '', $watermark = 0 , $max_size ='')
    {
        $this->_pic_max_size = empty($max_size) ? $this->_pic_max_size : $max_size ;
        if(!empty($field_name)) $this->_upload_name = $field_name;
        if(!isset($_FILES[$this->_upload_name]))
        {
            return $this->result(false, '文件不存在');
        }
        $filear = $_FILES[$this->_upload_name];
        if($filear['error'] > 0)
        {
            return $this->result(false, $filear['error']);
        }
        if($filear["size"] > $this->_pic_max_size )
        {
            return $this->result(false, "上传文件 ".$filear["name"]." 大小超出系统限定值[".$this->_pic_max_size." 字节]，不能上传。");
        }
        
        //图片存本地处理
        //取得扩展名
        $this->get_ext($filear["name"]);
        //设置保存文件名
        $savename = $this->set_savename();
        $code = md5($savename);        
        $tmpdir = $_SERVER['SINASRV_CACHE_DIR']. $dir_name .'/'.date('ym').'/'.$code{6}.$code{8}.'/'.$code{10}.$code{12}.'/';
        $tmpfile = $tmpdir . $savename;        
        if(!is_dir($tmpdir))
        {
            mk_dir($tmpdir,0777);
        }
        
        $tmp_res = move_uploaded_file($filear["tmp_name"], $tmpfile);
        if(!$tmp_res) return $this->result(false, $tmp_res);
        
        //图片加水印
        if($watermark == 1)
        {
            $watermark_file = ROOT_DIR . '/resources/images/WatermarkLogo.png';
            $cls_image = new lib_image();
            $this->set(200, 90, 100, 80);
            $res = $cls_image->watermark($tmpfile, $tmpfile, 9, $watermark_file);
        }
        //api上传图片
        $res = $this->upload($tmpfile);
        if($res['code'])
        {
            // 成功日志
            $this->_logger_model->success($res, $tmpfile, 'post', 'lib_upload/upload_pic');
            return $this->result(true, $res['msg']);
        }
        // 失败日志
        $this->_logger_model->fail($res, $tmpfile, 'post', 'lib_upload/upload_pic');
        return $this->result(false, $res);
        
    }
    /**
     * 上传图片
     * @param string $field_name
     * @return multitype:string
     */
    public function upload_base64_pic($imgData, $dir_name = 'leju_xcx', $watermark = 0)
    {
        if(empty($imgData))
        {
            return $this->result(false, '文件不存在');
        }

        //图片存本地处理
        //取得扩展名
        if (preg_match('/(?<=\/)[^\/]+(?=\;)/', $imgData, $match)) {
            $this->_ext = $match[0];
        } else {
            $this->_ext = '';
        }

        if(!$this->_ext || !in_array($this->_ext, array('jpg', 'jpeg', 'gif', 'png'))) {
            return $this->result(false, '图片格式非法');
        }

        //设置保存文件名
        $savename = $this->set_savename();
        $code = md5($savename);
        $tmpdir = $_SERVER['SINASRV_CACHE_DIR']. $dir_name .'/'.date('ym').'/'.$code{6}.$code{8}.'/'.$code{10}.$code{12}.'/';
        $tmpfile = $tmpdir . $savename;
        if(!is_dir($tmpdir))
        {
            mk_dir($tmpdir,0777);
        }

        preg_match('/(?<=base64,)[\S|\s]+/',$imgData, $matches); //处理base64文本，用正则把第一个base64,之前的部分砍掉

        if(!$matches) {
            return $this->result(false, '图片编码问题');
        }
        
        if(strlen(base64_decode($matches[0])) > $this->_pic_max_size )
        {
            return $this->result(false, "上传文件大小超出系统限定值[".$this->_pic_max_size." 字节]，不能上传。");
        }

        $tmp_res = file_put_contents($tmpfile,base64_decode($matches[0]));//写入文件
        if(!$tmp_res) return $this->result(false, '写入文件失败');

        //图片加水印
        if($watermark == 1)
        {
            $watermark_file = ROOT_DIR . '/resources/images/WatermarkLogo.png';
            $cls_image = new lib_image();
            $cls_image->set(200, 90, 100, 80);
            $res = $cls_image->watermark($tmpfile, $tmpfile, 9, $watermark_file);
        }
        //api上传图片
        $res = $this->upload($tmpfile);
        if($res['code'])
        {
            $res['msg']['local_file'] = $tmpfile;
            return $this->result(true, $res['msg']);
        }

        return $this->result(false, $res);

    }
    
    /**
     * 上传图片
     * @param string $field_name
     * @return multitype:string
     */
    public function upload_url_pic($url, $dir_name = 'leju_xcx', $watermark = 0,$method='GET',$params= array())
    {
        if(empty($url))
        {
            return $this->result(false, '文件不存在');
        }
        $this->_ci = & get_instance();
        $this->_ci->load->library('lib_http',array(),'lib_upload_http');
        $imgData = $this->_ci->lib_upload_http->request($url,$method,$params);
        $mimes = get_mimes();
        $content_type = $this->_ci->lib_upload_http->http_info['content_type'];
        foreach($mimes as $k => $v)
        {
            if(is_string($v) && $v == $content_type)
            {
                $this->_ext = $k;
                break;
            }
            elseif(is_array($v) && in_array($content_type,$v))
            {
                $this->_ext = $k;
                break;
            }
        }
        if(!$this->_ext || !in_array($this->_ext, array('jpg', 'jpeg', 'gif', 'png'))) {
            return $this->result(false, '图片格式非法');
        }
        //设置保存文件名
        $savename = $this->set_savename();
        $code = md5($savename);
        $tmpdir = $_SERVER['SINASRV_CACHE_DIR']. $dir_name .'/'.date('ym').'/'.$code{6}.$code{8}.'/'.$code{10}.$code{12}.'/';
        $tmpfile = $tmpdir . $savename;
        if(!is_dir($tmpdir))
        {
            mk_dir($tmpdir,0777);
        }

        if (!file_exists($tmpdir))
             return $this->result(false, "目录不存在");  

        
        if(strlen($imgData) > $this->_pic_max_size )
        {
            return $this->result(false, "上传文件大小超出系统限定值[".$this->_pic_max_size." 字节]，不能上传。");
        }

        $tmp_res = file_put_contents($tmpfile,$imgData);//写入文件
        if(!$tmp_res) return $this->result(false, '写入文件失败');

        //图片加水印
        if($watermark == 1)
        {
            $watermark_file = ROOT_DIR . '/resources/images/WatermarkLogo.png';
            $cls_image = new lib_image();
            $cls_image->set(200, 90, 100, 80);
            $res = $cls_image->watermark($tmpfile, $tmpfile, 9, $watermark_file);
        }
        //api上传图片
        $res = $this->upload($tmpfile);
        if($res['code'])
        {
            $res['msg']['local_file'] = $tmpfile;
            return $this->result(true, $res['msg']);
        }
        return $this->result(false, $res);

    }
    
    /**
     * 取得文件扩展名
     * $filename 为文件名称
     */
    function get_ext($filename)
    {
        if($filename == "") return;
        $ext = explode(".", $filename);
        $this->_ext = $ext[sizeof($ext)-1];
    }
    
    /**
     * 功能: 设置文件保存名
     * $savename 保存名，如果为空，则系统自动生成一个随机的文件名
     */
    function set_savename( $name = "" )
    {
        if ($name == "")
        {  // 如果未设置文件名，则生成一个随机文件名
            srand ((double) microtime() * 1000000);
            $rnd = rand(1000,9999);
            $name = date('His'). $rnd;
            $name = $name.".".$this->_ext;
        }
        return $name;
    }
    
    /**
     * 返回结果
     * @param string $result
     * @param string $info
     * @return multitype:string
     */
    public function result($result = false, $info = '')
    {
        return array(
            'result' => $result,
            'info' => $info,
        );
    }
}
