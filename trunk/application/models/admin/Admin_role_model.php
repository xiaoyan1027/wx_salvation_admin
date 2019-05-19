<?php

/**
 * Description of role
 * 用户角色module类
 * @author luis
 * @time 2014-11-10
 */
class Admin_role_model extends BASE_Model {

    const TABLE_ADMIN_ROLE = 'admin_role';


    public function __construct() {
        parent::__construct(self::TABLE_ADMIN_ROLE);
    }

    /**
     * 角色添加
     * @param array $param 角色数据
     * @return array
     */
    public function addRole($param) {

        $res = $this->insert($param);

        return $res;
    }

    /**
     * 获取全部角色信息
     *
     * @param string $condition 查询条件
     * @param string $order 排序条件
     * @param string $limit 分页限制数
     * @param string $group 分组条件
     * @return array
     */
    public function getAllRoles($condition = '', $order = 'id DESC', $limit = '', $group = '') {
        $resource = $this->fetch_all($condition, $order, $limit, $group);

        return $resource;
    }

    /**
     * 获取相关字段信息
     * @param string $condition 查询条件
     * @param string $fields 要获取的字段
     * @return array
     */
    public function getFiledByCondition($condition = '', $fields = '') {
        $resource = $this->fetch_field_all($condition, $fields);

        return $resource;
    }

    /**
     * 修改角色信息
     * @param array $param 角色数据
     * @return bool
     */
    public function updateRole($id, $param) {

        $bool = $this->update_by_id($id, $param);

        return $bool;
    }

    /**
     * 根据条件获取角色信息
     * @param array $param 查询条件
     * @return array
     */
    public function getRole($param) {

        $result = $this->fetch_row($param);

        return $result;
    }

    /**
     * 根据id获取角色信息
     * @param int $role_id 角色id
     * @return array
     */
    public function getRoleById($role_id) {

        $result = $this->fetch_by_id($role_id);

        return $result;
    }

    /**
     * 根据id删除角色信息
     * @param int $id 角色id
     * @return boolean
     */
    public function deleteRole($id) {

        $bool = $this->delete(array('id' => $id));

        return $bool;
    }

    /**
     * 获取记录总数
     * @return int
     */
    public function getTotal($condition = '') {
        $total = $this->fetch_count($condition);

        return $total;
    }
}
