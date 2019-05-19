<?php

class Admin_compose_model extends BASE_Model {

    const TABLE_ADMIN_COMPOSE = 'admin_compose';

    /**
     * 站点
     * @var array
     */
    private $domain_list = array(
                'home' => '前台',
                'admin' => '后台',
            );

    public function __construct() {
        parent::__construct(self::TABLE_ADMIN_COMPOSE);
    }

    /**
     * get_first_compose_id
     * 获取一个默认组件ID
     *
     * @return mixed
     */
    public function  get_first_compose_id(){
        $result = $this->fetch_row(array(), 'id', 'id ASC');
        return $result['id'];
    }

    /**
     * 获取所有站点
     * @return array
     */
    public function get_domain_list() {
        $list = $this->domain_list;
        return $list;
    }

    /**
     * 获取组件记录
     * @param array|string $condition
     * @param string $order
     * @param string $limit
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function get_list($condition = '', $order = '', $limit = '') {
        $res = $this->fetch_all($condition, $order, '',$limit);
        if (!$res) {
            return $this->_formatreturndata(false, '组件记录获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 获取组件记录总数
     * @param array|string $condition
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function get_count($condition = '') {
        $res = $this->fetch_count($condition);
        if ($res === false) {
            return $this->_formatreturndata(false, '组件记录总数获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 添加组件
     * @param array $data
     */
    public function compose_add($data) {
        if (!is_array($data) || count($data) == 0) {
            return $this->_formatreturndata(false, '参数错误！');
        }
        $res = $this->insert($data);
        if (!$res) {
            return $this->_formatreturndata(false, '组件添加失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 组件修改
     * @param int $id
     * @param array $data
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function compose_update($id, $data) {
        $id = intval($id);
        if ($id <= 0 || !is_array($data) || count($data) == 0) {
            return $this->_formatreturndata(false, '参数错误！');
        }

        $res = $this->update_by_id($id, $data);
        if ($res === false) {
            return $this->_formatreturndata(false, '组件修改失败！');
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
            return $this->_formatreturndata(false, '组件数据获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 根据组件id删除组件
     * @param int $id
     * @return array
     */
    public function compose_delete($id) {
        $id = intval($id);
        if ($id <= 0) {
            return $this->_formatreturndata(false, '参数错误！');
        }
        $res = $this->delete_by_id($id);
        if (!$res) {
            return $this->_formatreturndata(false, '组件删除失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    public function get_map_info($map) {
        if (!$map) {
            return $this->_formatreturndata(false, '参数错误！');
        }
        $res = $this->fetch_row($map);
        if (!$res) {
            return $this->_formatreturndata(false, '组件获取失败！');
        }
        return $this->_formatreturndata(true, $res);

    }

    /**
     * 获取组件列表
     * @return mixed
     */
    public function get_admin_compose_list() {
        $this->db->from(self::TABLE_ADMIN_COMPOSE);
        $this->db->where(array('domain' => 'admin'));
        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }
}