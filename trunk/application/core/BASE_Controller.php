<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BASE_Controller extends CI_Controller {

    protected $_succ = 'succ';
    protected $_fail = 'fail';

    public $CI;
    public $user_info;
    public $compose_info;
    public function __construct() {
        parent::__construct();

        $this->_init();
    }
    /**
     * 初始化
     */ 
    protected function _init()
    {
        $this->CI = & get_instance();
        $this->load->model('common/right_model');
        $this->load->model('admin/admin_user_model');
        $this->load->model('admin/admin_compose_model');
        $this->load->model('admin/admin_log_model');

        $path_set = array(
            'manager/login/index',
            'manager/login/verifycode'
        );
        $admin_uid = $this->login_model->get_admin_id();
        $path = $this->router->directory . $this->router->class . "/" . $this->router->method;
        if(in_array($path,$path_set) && !$admin_uid)
        {
            return;
        }

        if (!$admin_uid) {
            redirect('/manager/login/index');
        }

        //验证权限
        $check_right_res = $this->right_model->router_rights();

        if ($check_right_res['result'] == 'fail') {
            if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            {
                output_error(0,"抱歉，您还没有操作权限，请联系管理员先给您分配权限");
            }
            else {
                $this->show_message('抱歉，您还没有操作权限，请联系管理员先给您分配权限','/manager/login/logout');
            }
        }

        //用户信息
        $this->user_info = $this->admin_user_model->get_user_info($admin_uid);

        $this->assign('user_info', $this->user_info);
        $this->init_menu();
    }

    /**
     * 初始化组件和菜单
     */
    public function init_menu() {
        $result = $this->right_model->get_user_rights();
        $user_rights = isset($result['info']) ? $result['info'] : array();

        $user_compose_list = $this->right_model->get_user_tree($user_rights);

        if (empty($user_compose_list)) {
            $this->login_model->logout();
            $this->show_message('抱歉，您还没有操作权限，请联系管理员先给您分配权限','/manager/login/logout');
        }

        $url = $this->router->directory . $this->router->class . '/' . $this->router->method;

        $compose_id = '';
        foreach ($user_compose_list as $key => $compose) {
            $com = $compose['en_name'];
            foreach ($compose['child_info'] as $controller) {
                $con = $controller['func_name'];
                foreach ($controller['child_info'] as $method) {
                    $act = $method['func_name'];
                    $a_url = $com . '/' . $con . '/' . $act;

                    if ($url == $a_url) {
                        $compose_id = $compose['id'];
                        $this->input->set_cookie('compose_id', $compose_id, 3600);
                        break;
                    }
                }
            }
        }

        if (empty($compose_id)) {
            $compose_id = $this->input->cookie('compose_id');
        }

        if (!isset($user_compose_list[$compose_id])) {
            $com = array_keys($user_compose_list);
            $compose_id = $com[0];
        }

        $this->compose_info = $user_compose_list[$compose_id];
        $menu_list = $user_compose_list[$compose_id]['child_info'];

        if (isset($_GET['test'])) {
            dump($menu_list);
        }

        $this->assign('user_compose_list', $user_compose_list);
        $this->assign('menu_list', $menu_list);
        $this->assign('compose_info', $this->compose_info);
        $this->assign('compose_id', $compose_id);
    }

    /**
     * 信息提示
     * @param $message 提示内容
     * @author yapeng1@leju.com
     */
    public function show_message($message = '', $url = '') {
        $this->assign('message', $message);
        $this->assign('url', $url, 'NO_DENY');
        $this->display_base('common/message');
        exit;
    }

    /**
     * 信息提示
     * @param $message 提示内容
     * @author yapeng1@leju.com
     */
    public function show_alert_message($message = '', $url = '') {
        $this->assign('message', $message);
        $this->assign('url', $url, 'NO_DENY');
        $this->display_base('common/alert_message');
        exit;
    }

    /**
     * 解析基础模板
     * @param string $tmp_name
     */
    public function display_base($tmp_name) {
        $this->display($tmp_name);
        exit;
    }

    public function assign($key, $val , $xss_clean = TRUE) {
        if($xss_clean)
        {
            $val = xss_clean($val);
        }
        $this->lib_smarty->assign($key, $val);
    }

    public function display($html) {

        if (is_file($this->lib_smarty->template_dir .'/' . $html . '.html')) {
            $file = $html;
        } else {
            $path = $this->router->class;
            if ($this->router->directory) {
                $path = $this->router->directory . $path;
            }
            $file = $path . '/' . $html;
        }

        $this->assign('act', $this->router->method);
        $this->assign('con', $this->router->class);
        $this->assign('directory', $this->router->directory);

        $this->lib_smarty->display($file . '.html');
    }


    public function fetch($file) {
        $content = $this->lib_smarty->fetch($file . '.html');
        return $content;
    }
    /**
     * Build SQL
     * @param  array $config
     * @return string
     */
    public function build_condition($config) {
        $condition = '';
        foreach ($config as $k => $v) {
            if (!empty($v[1])) {
                switch ($v[0]) {
                    case "like":
                        $condition .= $k . " LIKE " . "'%{$v[1]}%'" . ' AND ';
                        break;
                    case "gt":
                        $condition .= $k . " >= " . "'{$v[1]}'" . ' AND ';
                        break;
                    case "lt":
                        $condition .= $k . " <= " . "'{$v[1]}'" . ' AND ';
                        break;
                    case "eq":
                        $condition .= $k . " = " . "'{$v[1]}'" . ' AND ';
                        break;
                    case "between":
                        $str = explode(",", $v[1]);
                        if ($str[0] && $str[1]) {
                            $condition .= $k . " BETWEEN " . "'{$str[0]}'" . " AND " . "'{$str[1]}'" . ' AND ';
                        } else {
                            if ($str[0]) {
                                $condition .= $k . " >= " . "'{$str[0]}'" . ' AND ';
                            }
                            if ($str[1]) {
                                $condition .= $k . "<=" . "'{$str[1]}'" . ' AND ';
                            }
                        }
                        break;
                    case "in":
                        $condition .= $k . " IN ('" . implode("', '", $v['1']) . "') AND ";
                        break;
                    case "not_in":
                        $condition .= $k . " NOT IN ('" . implode("', '", $v['1']) . "') AND ";
                        break;
                }
            }
        }
        $condition = rtrim($condition, "AND ");

        return $condition;
    }

    /**
     * AJAX Return
     * @param  string  $type       [类型(error, succ, info)]
     * @param  string  $reason     [错误原因]
     * @param  integer $error_code [错误码]
     * @return [type]              [description]
     */
    public function ajax_return($type = 'info', $code = 0, $reason = '',  $data = array())
    {
        $result           = array();
        $result['reason'] = $reason;
        $result['code']   = $code;
        $result['data']   = $data;

        switch (trim($type)) {
            case 'error':
                $result['status'] = 'fail';
                break;
            case 'succ':
                $result['status'] = 'succ';
                break;
            default:
                $result['status'] = 'info';
                break;
        }

        $callback = $this->input->get('callback');
        $return   = empty($callback) ? json_encode($result) : ' ' . $callback . "(" . json_encode($result) . ");";
        echo $return;
        exit;
    }

}
