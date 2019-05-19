<?php
/**
 * 快速上传资源客户端封装类。
 *
 * Version: v1.0
 * Version: $Id$
 *
 * 屏蔽上传细节，把上传资源的工作当作简单的API调用
 * 在给定稳定的接口名称的情况下，如需要升级接口，客户端只需要更新该API封装类
 *
 *
 * public类：
 *     ResourceApiClient
 *     ResourceUrlBuilder
 *     plugin_resourceapi
 *
 *
 * private类：
 *     ResourceApiHelper
 */

class ResourceApiHelper {
    var $pkey;
    var $mkey;

    var $errno;
    var $errmsg;
    var $result;
    var $_version = '1.0';
    var $_debug = true;

    var $_UPMODE_POST = 1;
    var $_UPMODE_PUT = 2;
    var $_upmode_type = 1; // 上传模式

    function __construct($pkey, $mkey) {
        $this->pkey = $pkey;
        $this->mkey = $mkey;

    }

    function get_base_front_url() {
        $furl = "";

        if (isset($_SERVER) && array_key_exists('HTTP_HOST', $_SERVER)) {
            $dhost = $_SERVER['HTTP_HOST'];
            if (strpos($dhost, 'test.') === 0) {
                $furl = "";
            }
        }

        return $furl;
    }

    function get_base_resouce_url() {
        $burl = "";
        if (isset($_SERVER) && array_key_exists('HTTP_HOST', $_SERVER)) {
            $dhost = $_SERVER['HTTP_HOST'];
            if (strpos($dhost, 'test.') === 0) {
                $burl = "";
            }
        }
        return $burl;
    }

    function get_action_url() {
        $aurl = $this->get_base_resouce_url() . 'resource/resource/upload';
        return $aurl;
    }

    function get_api_url() {
        $aurl = $this->get_base_resouce_url() . 'resource/api/index';
        return $aurl;
    }

    function make_upload_url($file, $old_file_id = '') {
        $url_prefix = $this->get_action_url() . "?";
        // pkey='.$pkey.'&mkey='.$mkey.'&filename='.$filename";

        $filename = basename($file);
        $url_suffix = 'pkey='.$this->pkey.'&mkey='.$this->mkey
            . '&filename=' . urlencode($filename)
            . '&filepath=' . urlencode($file);
        $url_suffix .= '&sig=' . md5($url_suffix);
        $url = $url_prefix . $url_suffix;
        // echo $url . "\n";

        if (!empty($old_file_id)) {
            $url .= '&hashcode=' . $old_file_id;
        }

        return $url;
    }

    function make_url_upload_url($file = '') {
        $url_prefix = $this->get_action_url() . "?";

        $url_suffix = 'pkey='.$this->pkey.'&mkey='.$this->mkey
            . '&FileUrl=' . urlencode($file);
        //$url_suffix .= '&sig=' . md5($url_suffix);
        $url = $url_prefix . $url_suffix;

        return $url;
    }


    function make_upload_data($file, $old_file_id = '')
    {
        $datas = array();

        $datas['version'] = $this->_version;
        $datas['method'] = 'resource.upload';
        $datas['pkey'] = $this->pkey;
        $datas['mkey'] = $this->mkey;
        $datas['filename'] = basename($file);
        $datas['filepath'] = $file;
        if (!empty($old_file_id)) {
            $datas['hashcode'] = $old_file_id;
        }
        $datas['ctime'] = microtime(true);

        $sig = $this->_make_parameter_signature($datas);
        $datas['sig'] = $sig;

        return $datas;
    }

    function upload_impl($url, $file, $old_file_id = '')
    {
        $result = array('errno' => '-1', 'errmsg' => 'Unknown.', 'result' => '');

        if ($this->_upmode_type == $this->_UPMODE_PUT) {
            $result = $this->_curl_put($url, $file);
        } else if ($this->_upmode_type == $this->_UPMODE_POST) {
            $post_data = $this->make_upload_data($file, $old_file_id);
            $upload_name = "Filedata";
            $file_fields = array($upload_name => $file);
            $result = $this->_curl_multipart_post($url, $post_data, $file_fields);
        } else {
            return $result;
        }
        // print_r($result);

        $this->errno = $result['errno'];
        $this->errmsg = $result['errmsg'];

        // var_dump($this->result);

        if ($this->errno != 0) {
            $this->result = array();
            $this->result['success'] = false;
            $this->result['msg'] = 'Network error.';
            // $this->result['_r'] = $result;
            // $this->result['_t'] = array(
            //     'api' => $url,
            //     'post_data' => $post_data,
            //     'file_fields' => $file_fields,
            // );
            $this->result = json_decode(json_encode($this->result), true);
        } else {
            $this->result = json_decode($result['result'], true);
            if (!is_array ($this->result)) {
                $this->result = array('success' => false,
                                      'msg' => 'Result format error.',
                                      'result' => $result['result']
                                      );
            }
        }

        return $this->result;
    }


    function _make_parameter_signature($params)
    {
        $datas = $params;
        ksort($datas);

        $rrstr = '';
        foreach ($datas as $key => $value) {
            $rrstr .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        // echo $rrstr . $this->pkey . "\n";
        $sig = md5($rrstr . $this->pkey);
        return $sig;
    }

    function _make_orig_secret($file_id)
    {
        // md5($file_id . $pkey) => extract 6chs
        $md5_sum = md5($file_id . $this->pkey);
        $chars = str_split($md5_sum);

        $sec = '';
        for ($i = 0; $i < count($chars); $i += 6) {
            $sec .= $chars[$i];
        }

        return $sec;
    }

    function _verify_orig_secret($file_id, $osec)
    {
        $md5_sum = md5($file_id . $this->pkey);
        $chars = str_split($md5_sum);

        $sec = '';
        for ($i = 0; $i < count($chars); $i += 6) {
            $sec .= $chars[$i];
        }

        return ($osec == $sec);
    }

    function performRestRequest($params)
    {
        $api_url = $this->get_api_url();

        $datas = $params;
        $datas['pkey'] = $this->pkey;
        $datas['mkey'] = $this->mkey;
        $datas['ctime'] = microtime(true);
        $sig = $this->_make_parameter_signature($datas);
        $datas['sig'] = $sig;

        // echo $api_url . "\n";
        // print_r($datas);

        $result = $this->_curl_multipart_post($api_url, $datas);

        $this->errno = $result['errno'];
        $this->errmsg = $result['errmsg'];

        // var_dump($result);

        if ($this->errno != 0) {
            $this->result = array();
            $this->result['success'] = false;
            $this->result['msg'] = 'Network error.';
            $this->result = json_decode(json_encode($this->result), true);
        } else {
            $this->result = json_decode($result['result'], true);
            if (!is_array($this->result)) {
                $this->result = array('success' => false,
                                      'msg' => 'Result format errora.',
                                      'result' => $result['result']
                                      );
            }
        }

        return $this->result;
    }

    function _curl_put($url, $file, $header = array(), $timeout = 5, $port = 80) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        !empty ($header) && curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PUT, 1);
        $ifp = fopen($file, 'r');
        curl_setopt($ch, CURLOPT_INFILE, $ifp);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
        // curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($this, "_curl_write_func"));

        $result = array();
        $result['errno'] = 0;
        $result['errmsg'] = '';
        $result['result'] = curl_exec($ch);
        // $result['output'] = $result['result'];
        if (0 != curl_errno($ch)) {
            $result['errno'] = curl_errno($ch);
            $result['errmsg']  = "Error:" . curl_error($ch);
        }
        curl_close($ch);
        fclose($ifp);

        return $result;
    }

    /**
     * Send multipart post data to the target URL
     * return data returned from url or false if error occured
     * (contribution by vule nikolic, vule@dinke.net)
     * @param string url
     * @param array assoc post data array ie. $foo['post_var_name'] = $value
     * @param array assoc $file_field_array, contains file_field name = value - path pairs
     * @param int timeout in sec for complete curl operation (default 30 sec)
     * @return array data('errno'=>?, 'errmsg'=>?, 'result'=>?)
     * @access private
     */
    function _curl_multipart_post($url, $post_data, $file_fields = array(), $timeout=30)
    {
        $result = array('errno' => 0, 'errmsg' => '', 'result' => '');

        $ch = curl_init();
        //set various curl options first

        // set url to post to
        curl_setopt($ch, CURLOPT_URL, $url);

        // return into a variable rather than displaying it
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //set curl function timeout to $timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_VERBOSE, false);

        //set method to post
        curl_setopt($ch, CURLOPT_POST, true);

        // disable Expect header
        // hack to make it working
        $headers = array("Expect: ");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // initialize result post array
        $result_post = array();

        //generate post string
        $post_array = array();
        $post_strings = array();
        if (!is_array($post_data)) {
            $result['errno'] = 5;
            $result['errmsg'] = 'Params error.';
            return json_encode($result);
            // return false;
        }

        foreach($post_data as $key=>$value) {
            $post_array[$key] = $value;
            $post_strings[] = urlencode($key)."=".urlencode($value);
        }

        //$post_string = implode("&", $post_strings);

        // set post string
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);

        // set multipart form data - file array field-value pairs
        if (!empty($file_fields)) {
            foreach($file_fields as $key => $value) {
                if (strpos(PHP_OS, "WIN") !== false) {
                    $value = str_replace("/", "\\", $value); // win hack
                }

                if (version_compare("5.5", PHP_VERSION, "<=")) {
                    $file_fields[$key] = new CURLFile($value);
                }else{
                    $file_fields[$key] = "@" . $value;
                }
            }
        }

        // set post data
        $result_post = array_merge($post_array, $file_fields);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $result_post);
        // print_r($result_post);

        //and finally send curl request
        $output = curl_exec($ch);
        $result['result'] = $output;
        // print_r($result);

        if (curl_errno($ch)) {
            if (0) {
                echo "Error Occured in Curl\n";
                echo "Error number: " .curl_errno($ch) ."\n";
                echo "Error message: " .curl_error($ch)."\n";
            }
            $result['errno'] = curl_errno($ch);
            $result['errmsg'] = curl_error($ch);
            // return false;
        } else {
            // return $result;
        }
        curl_close($ch);

        return $result;
    }

};

/**
 * 自由拼接图片URL类
 *
 * 最终结果与各方法的调用顺序无关
 */
class ResourceUrlBuilder {
    private $_pid;
    private $_mkid;
    private $_file_id;

    private $_size;
    private $_clip;
    private $_border;
    private $_scale;
    private $_watermark;
    private $_backgroud_color;
    private $_quality_level;
    private $_ext;

    private $_helper;

    function __construct($pid, $mkid, $file_id, $helper)
    {
        $this->_pid = $pid;
        $this->_mkid = $mkid;
        $this->_file_id = $file_id;

        $this->_helper = $helper;
    }

    /**
     * 按尺寸缩放
     *
     * @param $size 缩放后大小，格式wXh
     */
    public function resize($size)
    {
        $this->_size = $size;
        return $this;
    }

    public function resize2($width, $height)
    {
        $this->_size = $width . 'X' . $height;
        return $this;
    }

    public function clip($clip)
    {
        $this->_clip = $clip;
        return $this;
    }

    /**
     *
     * @param $bx 起始点x坐标，单位像素
     * @param $by 起始点y坐标，单位像素
     * @param $width 裁切宽度，单位像素
     * @param $height 裁切高度，单位像素
     * @return $this object for continue's operation on $this object.
     */
    public function clip2($bx, $by, $width, $height)
    {
        $this->_clip = $bx . 'X' . $by . 'X' . $width . 'X' . $height;
        return $this;
    }

    public function border($border)
    {
        $this->_border = $border;
        return $this;
    }

    /**
     * 按比例缩放
     *
     * @param $scale 缩放比例，格式：1 <= $scale < 1000
     */
    public function scale($scale)
    {
        $this->_scale = $scale;
        return $this;
    }

    public function watermark($wmid)
    {
        $this->_watermark = $wmid;
        return $this;
    }

    public function extension($ext)
    {
        $this->_ext = $ext;
        return $this;
    }

    public function backgroud_color($color)
    {
        $this->_backgroud_color = $color;
        return $this;
    }

    public function quality_level($level)
    {
        $this->_quality_level = $level;
        return $this;
    }

    public function get()
    {
        $purl = $this->_helper->get_base_front_url();
        $purl .= 'imp/imp/deal/';

        $args = array($this->_file_id, 'p'.$this->_pid);
        array_push($args, 'mk'.$this->_mkid);

        if (!empty($this->_size)) {
            array_push($args, 's'.$this->_size);
        }
        if (!empty($this->_clip)) {
            array_push($args, 'c'.$this->_clip);
        }
        if (!empty($this->_border)) {
            array_push($args, 'b'.$this->_border);
        }
        if (!empty($this->_scale)) {
            array_push($args, 't'.$this->_scale);
        }
        if (!empty($this->_watermark)) {
            array_push($args, 'wm'.$this->_watermark);
        } else {
            // 不用水印的时候，对原图访问加安全码
            $osec = $this->_helper->_make_orig_secret($this->_file_id);
            array_push($args, 'os' . $osec);
        }

        if (!empty($this->_backgroud_color)) {
            array_push($args, 'bc' . $this->_backgroud_color);
        }

        if (!empty($this->_quality_level)) {
            array_push($args, 'qa' . $this->_quality_level);
        }

        $purl .= implode('_', $args);
        $purl .= '.' . $this->_ext;

        return $purl;
    }
};

class ResourceApiClient {
    // 用于加密
    var $pkey;
    // 用于标识
    var $mkey;
    var $_helper;

    function __construct($pkey, $mkey) {
        $this->pkey = $pkey;
        $this->mkey = $mkey;
        $this->_helper = new ResourceApiHelper($pkey, $mkey);
    }



    /**
     * 单文件上传
     */
    function upload($file) {
        $url = $this->_helper->make_upload_url($file);

        return $this->_helper->upload_impl($url, $file);
    }

    /**
     * 单个url文件上传
     * hdd   2016-11-30
     */
    function url_upload($file) {
        $url = $this->_helper->make_url_upload_url($file);

        $post_data = array();
        $post_data['mkey'] = $this->mkey;
        $post_data['FileUrl'] = $file;
        $curl_result = $this->_helper->_curl_multipart_post($url, $post_data);

        if ($curl_result['errno'] != 0) {
            $result = array();
            $result['success'] = false;
            $result['msg'] = 'Network error.';
            $result = json_decode(json_encode($result), true);
        } else {
            $result= json_decode($curl_result['result'], true);
            if (!is_array ($result)) {
                $result = array(
                    'success' => false,
                    'msg' => 'Result format error.',
                    'result' => $curl_result['result']
              );
            }
        }
        return $result;
    }


    /**
     * 多个url资源上传
     * hdd  2016-11-30
     */
    function multi_url_upload($files) {
        $result = array();

        if(empty($files)){
            return false;
        }

        $files_urls = explode(',', $files);
        foreach ($files_urls as $idx => $file) {
            $result[$idx] = $this->url_upload($file);
        }
        return $result;
    }


    /**
     * 多文件上传
     *
     */
    function multi_upload($files) {
        $result = array();

        if (!is_array($files)) {
            return false;
        }

        foreach ($files as $idx => $file) {
            $result[$idx] = $this->upload($file);
        }
        return $result;
    }

    /**
     * 单文件修改上传
     */
    function rewrite_upload($file, $old_file_id) {
        $url = $this->_helper->make_url($file, $old_file_id);

        return $this->_helper->upload_impl($url, $file, $old_file_id);
    }

    /**
     * 多文件修改上传
     */
    function multi_rewrite_upload($files, $old_file_ids) {
        if (!is_array($files) || !is_array($old_file_ids)) {
            return false;
        }
        if (count($files) != count($old_file_ids)) {
            return false;
        }

        $result = array();
        foreach ($files as $idx => $file) {
            $result[$idx] = $this->reupload($file, $old_file_ids[$idx]);
        }

        return $result;
    }

    // 客户端模式，FORM提交地址
    function get_form_url() {
        return $this->_helper->get_action_url();
    }

    // 客户端模式，FORM表单项目
    function get_form_items() {
        $items = array(
                       'pkey' => $this->pkey,
                       'mkey' => $this->mkey,
                       'ctime' => microtime(true),

                       );
        $sig = '';
        $sig_str = '';
        $form_str = '';
        foreach ($items as $key=>$item) {
            $sig_str .= "&{$key}={$item}";
        }

        $sig = md5($sig_str);
        $items['sig'] = $sig;

        foreach ($items as $key=>$item) {
            $form_str .= '<input type="hidden" name="'.$key.'" value="'.$item.'">' . "\n";
        }

        return $form_str;
    }

    // 获取前端url
    /**
     * 拼接访问特定格式图片的url
     *
     * @param  size 格式100X80
     * @param  clip 格式
     * @param scale 格式
     * @param watermark  水印ID
     */
    function get_resource_url($pid, $mkid, $file_id, $ext, $size = '', $clip = '',
                              $border = '', $scale = '', $watermark  = '',
                              $backgroud_color = '', $quality = '') {
        $purl = $this->_helper->get_base_front_url();
        $args = array($file_id, 'p' . $pid);
        array_push($args, 'mk' . $mkid);

        if (!empty($size)) {
            array_push($args, 's'.$size);
        }
        if (!empty($clip)) {
            array_push($args, 'c'.$clip);
        }
        if (!empty($border)) {
            array_push($args, 'b'.$border);
        }
        if (!empty($scale)) {
            array_push($args, 't'.$scale);
        }
        if (!empty($watermark)) {
            array_push($args, 'wm'.$watermark);
        } else {
            // 不用水印的时候，对原图访问加安全码
            $osec = $this->_helper->_make_orig_secret($file_id);
            array_push($args, 'os' . $osec);
        }

        if (!empty($backgroud_color)) {
            array_push($args, 'bc' . $backgroud_color);
        }

        if (!empty($quality)) {
            array_push($args, 'qa' . $quality);
        }

        $purl .= implode('_', $args);
        $purl .= '.' . $ext;

        return $purl;
    }

    /**
     * 获取拼接访问特定格式图片的url的构造器
     *
     * @return ResourceUrlBuilder object
     *
     * eg.
     * $ubuilder = $cli->get_resource_url_builder(1, 2, 'aa/bb/c/ddeeff');
     * $ubuilder->size(200, 100);
     * $ubuilder->watermark(56);
     * $ubuilder->extension('jpg');
     * $purl = $ubuilder->get();
     * echo $purl;
     */
    public function get_resource_url_builder($pid, $mkid, $file_id)
    {
        return new ResourceUrlBuilder($pid, $mkid, $file_id, $this->_helper);
    }

    /**
     * 验证原图访问安全码是否正确
     *
     * @param $osec  url带的安全码
     * @return 正确返回true, 错误返回false
     */
    public function verify_resource_secret($file_id, $osec)
    {
        return $this->_helper->_verify_orig_secret($file_id, $osec);
    }

    /*
     * 获取上传HTML代码片断
     * 可插入到页面中，实现用户端上传功能。
     *
     */
    function get_swfupload_snippet($js_callback)
    {
        $cdir = dirname(__FILE__);
        $snippet = '';

        $uniq_string = 'u'.md5(uniqid('', true) . rand());
        $rurl = $this->_helper->get_base_resouce_url();
        $rdata = $this->_helper->make_upload_data('');
        $ctime = $rdata['ctime'];
        $sig = $rdata['sig'];

        $snip_tpl = file_get_contents($cdir . '/resourceapi/flash_upload.html');
        $snippet = str_replace('{$domain_base}', $rurl, $snip_tpl);
        $snippet = str_replace('{$uniq_string}', $uniq_string, $snippet);
        $snippet = str_replace('{$pkey}', $this->pkey, $snippet);
        $snippet = str_replace('{$mkey}', $this->mkey, $snippet);
        $snippet = str_replace('{$ctime}', $ctime, $snippet);
        $snippet = str_replace('{$sig}', $sig, $snippet);

        $snippet = str_replace('{$js_callback}', $js_callback, $snippet);

        return $snippet;
    }

    /**
     * 获取项目属性信息
     *
     */
    function get_attr_info()
    {
        $method = 'Project.getAttrInfo';
        $params = array('method' => $method);

        $attrs = $this->_helper->performRestRequest($params);
        return $attrs;
    }

    /**
     * 获取项目信息
     */
    function get_project_info()
    {
        $method = 'Project.getInfo';
        $params = array('method' => $method);

        $attrs = $this->_helper->performRestRequest($params);
        return $attrs;
    }

    /**
     * 获取项目可用水印列表
     *
     *
     */
    function get_watermarks()
    {
        $method = 'Project.getWaterMarks';
        $params = array('method' => $method);

        $attrs = $this->_helper->performRestRequest($params);
        return $attrs;
    }
};

// 与框架的兼容层类
class plugin_resourceapi extends ResourceApiClient
{
};


/*
 * for test
 * example
 */
function test_resource_api() {
    $pkey = "3a25d133456bf92b94262f9c4776171b";
    $mkey = "cd33159678ea24381c7f230412b306a5";
    $rac = new ResourceApiClient($pkey, $mkey);
    $file = "/home/gzleo/shots/test50haha.jpg";

    echo "Testing Resource API ...\n";
    $result = $rac->upload($file);
    // $result = $rac->rewrite_upload($file, "93/18/c/0b3c90bad5ae5c58730a378a84c");
    echo "returned.\n";
    print_r($result);
    echo "Done.\n";

    //
    // $snippet = $rac->get_swfupload_snippet();
    // echo $snippet;
}

function test_plugin_resource_api() {
    $pkey = "3a25d133456bf92b94262f9c4776171b";
    $mkey = "cd33159678ea24381c7f230412b306a5";
    $rac = new plugin_resourceapi($pkey, $mkey);
    $file = "/home/gzleo/shots/test50haha.jpg";
    echo "Testing Resource API ...\n";
    // $result = $rac->upload($file);
    // $result = $rac->rewrite_upload($file, "93/18/c/0b3c90bad5ae5c58730a378a84c");
    // $result = $rac->get_attr_info();
    // $result = $rac->get_project_info();


    //多个url资源上传
    // $pic_url = 'http://img2.3lian.com/2014/f2/181/109.jpg,http://pic2.nipic.com/20090417/727047_094753006_2.jpg';
    // $result = $rac->multi_url_upload($pic_url);


    //单个url资源上传
// $pic_url = 'http://pic.58pic.com/58pic/15/60/89/67g58PICN3r_1024.jpg';
        // $result = $client->url_upload($pic_url);

    $result = $rac->get_watermarks();

    echo "returned.\n";
    print_r($result);
    echo "Done.\n";

    //
    // $snippet = $rac->get_swfupload_snippet();
    // echo $snippet;
}



// test_resource_api();
// test_plugin_resource_api();
