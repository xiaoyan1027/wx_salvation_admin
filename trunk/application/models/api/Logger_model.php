<?php
/**
 * API日志类
 */ 
class Logger_model extends BASE_Model {
    
    private $_redis;
    private $_log_key = '';
    
    public function __construct() {
        parent::__construct();
        $this->_redis = $this->lib_redis;
        $this->_log_key = get_cache_key('api_logs');
    }

    /**
     * 添加成功日志记录
     */
    public function success($log_data = '', $params = '', $method ,$api_url) {
        
        $base_data = $this->_base_data($log_data, $params, $method,$api_url);
        $base_data['status'] = 1;
        $this->_redis->lpush($this->_log_key, serialize($base_data));
    }

    /**
     * 添加失败日志记录
     */
    public function fail($log_data = '', $params = '', $method ,$api_url) {
        $base_data = $this->_base_data($log_data, $params, $method,$api_url);
        $base_data['status'] = 2;
        $this->_redis->lpush($this->_log_key, serialize($base_data));
    }

    /**
     *
     * 日志基础数据 
     */

    private function _base_data($log_data = '', $params = '', $method = '',$api_url='') {
        $this->load->helper('number');
        $debug_backtraces = debug_backtrace();
        $debug_backtraces = array_reverse($debug_backtraces);
        foreach ($debug_backtraces as $debug_backtrace) {
            $data['backtrace'][] = array(
                'file' => isset($debug_backtrace['file']) ? str_replace('\\', '/', $debug_backtrace['file']) : '',
                'line' => isset($debug_backtrace['line']) ? $debug_backtrace['line'] : '',
                'function' => isset($debug_backtrace['function']) ? $debug_backtrace['function'] : '',
                'class' => isset($debug_backtrace['class']) ? $debug_backtrace['class'] : '',
            );
        }
        $get_param = array();
        $post_param = array();
        if($method == 'POST')
        {
            $post_param = $params;
        }
        else
        {
            $get_param = $params;
        }
        $data['appkey'] = '';
        $data['trace_id'] = $GLOBALS['_traceId'];
        //$data['backtrace'] = serialize($data['backtrace']);
        $data['data'] = $log_data ? serialize($log_data) : '';
        $data['category'] = '';
        $data['user_agent'] = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : '';
        $data['keyword'] = '';
        $data['site'] = $this->router->directory;
        $data['controller'] = $this->router->class;
        $data['action'] = $this->router->method;
        $data['refer'] = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '';
        $data['request_method'] = strtolower($method);
        $data['request_url'] = $api_url;
        $data['page_url'] = CUR_URL;
        $data['page_param'] = serialize($this->input->post(null,FALSE));
        $data['get_param'] = $get_param ? serialize($get_param) : '';
        $data['post_param'] = $post_param ? serialize($post_param) : '';
        $data['client_ip'] = $this->input->ip_address();
        $data['request_start_time'] = $GLOBALS['_beginTime'];
        $data['spend_time'] = sprintf('%.4f', (microtime(TRUE) - $GLOBALS['_beginTime']) * 1000);
        $data['memory'] = byte_format(memory_get_usage(true));
        $data['create_at'] = time();
        return $data;
    }
}