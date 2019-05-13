<?php

/**
 * Controller 管理
 */
class Admin_controller_model extends BASE_Model {

    const TABLE_ADMIN_CONTROLLER = 'admin_controller';

    public function __construct() {
        parent::__construct(self::TABLE_ADMIN_CONTROLLER);
    }

    /**
     * 获取Controller记录
     * @param array|string $condition
     * @param string $order
     * @param string $limit
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function get_list($condition = '', $order = '', $offset = 0, $limit = 20) {
        $res = $this->fetch_all($condition, $order, $offset, $limit);
        if (!$res) {
            return $this->_formatreturndata(false, 'Controller记录获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 获取Controller记录总数
     * @param array|string $condition
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function get_count($condition = '') {
        $res = $this->fetch_count($condition);
        if ($res === false) {
            return $this->_formatreturndata(false, 'Controller记录总数获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 添加Controller
     * @param array $data
     */
    public function controller_add($data) {
        if (!is_array($data) || count($data) == 0) {
            return $this->_formatreturndata(false, '参数错误！');
        }
        $res = $this->insert($data);
        if (!$res) {
            return $this->_formatreturndata(false, 'Controller添加失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * Controller修改
     * @param int $id
     * @param array $data
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function controller_update($id, $data) {
        $id = intval($id);
        if ($id <= 0 || !is_array($data) || count($data) == 0) {
            return $this->_formatreturndata(false, '参数错误！');
        }

        $res = $this->update_by_id($id, $data);
        if ($res === false) {
            return $this->_formatreturndata(false, 'Controller修改失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 根据id获取
     */
    public function get_infos($id) {
        $id = intval($id);
        if ($id <= 0) {
            return $this->_formatreturndata(false, '参数错误！');
        }

        $res = $this->fetch_by_id($id);
        if (!$res) {
            return $this->_formatreturndata(false, 'Controller数据获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 根据Controller id删除Controller
     * @param int $id
     * @return array
     * @author yapeng1@leju.com 2012-07-12
     */
    public function controller_delete($id) {
        $id = intval($id);
        if ($id <= 0) {
            return $this->_formatreturndata(false, '参数错误！');
        }
        $res = $this->delete_by_id($id);
        if (!$res) {
            return $this->_formatreturndata(false, 'Controller删除失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 根据条件删除Controller
     * @param array $condition
     */
    public function controller_delete_by_condition($condition) {
        if (empty($condition)) {
            return $this->_formatreturndata(false, '参数错误！');
        }
        $res = $this->delete($condition);
        if (!$res) {
            return $this->_formatreturndata(false, 'Controller删除失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     *
     * 得到所有controller
     * @param  $para
     */
    public function fetch_controller($compose_id = '') {
        $condition = 'controller_id = 0';
        if ($compose_id) {
            $condition .= " AND compose_id = $compose_id";
        }
        $result = $this->fetch_all($condition);

        return $result;
    }

    /**
     *
     * 得到所有权限功能
     * @param  $para
     */
    public function fetch_all_method() {
        $condition = 'controller_id != 0';
        $result = $this->fetch_all($condition);

        return $result;
    }

    /**
     *
     * 得到所有不属于权限的功能
     * @param  $para
     */
    public function fetch_all_no_rights_method() {
        $condition = 'controller_id != 0 and is_right = 0';
        $result = $this->fetch_all($condition);

        return $result;
    }

    /**
     *
     * 得到所有不属于权限的菜单功能
     * @param  $para
     */
    public function fetch_all_no_rights_menu_method() {
        $condition = 'controller_id != 0 and is_right = 0 and is_menu = 1';
        $result = $this->fetch_all($condition);

        return $result;
    }

    /**
     *
     * 检查controller中是否有该method
     * @param  $para
     */
    public function check_exist_method($condition) {
        $result = $this->fetch_row($condition);

        return $result['id'];
    }

    /**
     *
     * 通过$controller_id查询所有方法
     * @param  $para
     */
    public function fetch_method_by_controller_id($controller_id) {
        $condition_str = " controller_id='$controller_id'";

        $result = $this->fetch_all($condition_str);

        return $result;
    }

    /**
     *
     * 通过func_name查询是否有此记录
     * @param  $para
     */
    public function fetch_controller_by_func_name($func_name, $controller_id = 0) {
        $condition_str = " func_name = '$func_name' AND controller_id = $controller_id";


        $result = $this->fetch_row($condition_str);

        return $result;
    }

    /**
     * 检测全选
     * @param string $ids
     * @return mixed
     */
    public function get_rights_detail($ids = '') {
        $this->db->select('b.compose_id as compose_id');
        $this->db->from('zt_admin_controller as a');
        $this->db->join('zt_admin_controller as b', 'a.controller_id=b.id');
        $this->db->where_in('a.id', $ids);
        $this->db->group_by('compose_id');

        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }
}
