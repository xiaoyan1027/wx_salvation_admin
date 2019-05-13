<?php

/**
 * Created by PhpStorm.
 * Date: 16/10/27
 * Time: 上午11:01
 */
class Admin_user_model extends BASE_Model {

    const TABLE_ADMIN_USER = 'admin_users';

    /**
     * 管理员状态
     * @var array
     */
    private static $_status = array(
        1 => '使用中',
        2 => '已暂停',
        3 => '已关闭',
    );

    /**
     * 管理员类型
     * @var array
     */
    private $_admin_type_list = array(
                1 => '内部',
                2 => '外部',
            );

    public function __construct() {
        parent::__construct(self::TABLE_ADMIN_USER);
    }

    /**
     * 获取管理员类型
     * @return mixed
     */
    public function get_admin_type_list() {
        $list = $this->_admin_type_list;
        return $list;
    }

    /**
     * 获取全部用户数据
     * @param string $condition
     * @param string $order
     * @param int    $offset
     * @param int    $limit
     * @param string $group
     * @param string $fileds
     * @return  array
     * Author: zhangxin11@leju.com
     */
    public function get_user_list($condition = '', $order = 'id DESC', $offset = 0, $limit = 20, $group = '', $fileds = '*') {
        return $this->fetch_all($condition, $order , $offset, $limit , $group, $fileds);
    }



    /**
     * @param string $user_id
     * @return bool
     */
    public function get_user_info($user_id = '') {

        if (empty($user_id)) {
            return array();
        }

        $user_info = $this->fetch_by_id($user_id);

        if(!$user_info)
        {
            return false;
        }
        // 查询角色信息
        $this->load->model('admin/admin_role_model');
        $this->load->model('admin/admin_user_extend_model');
        $role_type = array();

        if ($user_info['is_super'] == 'Y') {
            $user_info['rights_sys'] = array("ad", "admin_ad");
        }
        // 获取用户扩展表中的权限（其他系统）
        //$extend_list = $this->admin_user_extend_model->fetch_all("admin_uid = {$user_info['id']}");
//        if (!empty($extend_list)) {
//            foreach ($extend_list as $item=>$value) {
//                if ($value['is_super'] == 'Y' ) {
//                    $user_info['rights_sys'][] = $value['product_type'];
//                }
//            }
//        }

        return $user_info;
    }

    /**
     * 获取扩展信息
     */
    public function get_all_user_extend($user_id)
    {
        $admin_users_extend_model = Table_Model::get_instance('admin_users_extend');
        $info = $admin_users_extend_model->fetch_all(array('admin_uid'=>$user_id));
        return $info;
    }
    /**
     * 获取账号对应业务的扩展信息
     */
    public function get_user_extent($user_id,$product_type)
    {
        $admin_users_extend_model = Table_Model::get_instance('admin_users_extend');
        $info = $admin_users_extend_model->fetch_row(array('admin_uid'=>$user_id,'product_type' => $product_type));
        return $info;
    }

    
    /**
     * 返回管理员状态
     * @return multitype:string
     */
    public function ret_status()
    {
        return self::$_status;
    }

    /**
     * 获取用户角色
     * [get_user_right description]
     * @return [type] [description]
     */
    public function get_user_right($name) {

        $this->db->from('pet_admin_users a');
        $this->db->join('pet_admin_users_extend b', 'a.id=b.admin_uid', 'left');

        $this->db->select('a.user_name,a.real_name,a.mobile,a.role_ids,b.* ');

        if ($name) {
            $this->db->where(array("a.user_name"=>$name));
        }

        $query = $this->db->get();
        $list = $query->row_array();


       
        if($list['is_super'] != 'Y') {

            $right_list = $this->get_role_list($list['role_ids']);
            $list['right_list'] = $right_list;
        }else{
            $list['right_list'] = "all";
        }

        return $list;

    }

    /**
     * 非超管获取right_list权限列表
     * [get_role_list description]
     * @return [type] [description]
     */
    private function get_role_list($id) {

        $this->db->from('pet_admin_role');
        
        $this->db->select('*');

        $this->db->where(array("id"=>$id));
        
        $query = $this->db->get();
        $list = $query->row_array();

//        $fole_rights[] = json_decode($list['role_rights'],true);
        $fole_rights[] = json_decode($list['permission'],true);
        return $fole_rights;
    }


    /**
     * 添加管理员
     * @param array $data
     */
    public function add($data)
    {
        return $this->insert($data);

    }

    /**
     * 修改用户信息
     * @param array $param 角色数据
     * @return bool
     */
    public function updateuser($id, $param) {

        $bool = $this->update_by_id($id, $param);

        return $bool;
    }


    /**
     * 更新用户扩展表
     */
    public function update_extend($id, $data)
    {
        $admin_users_extend_model = Table_Model::get_instance('admin_users_extend');
        $extend = $admin_users_extend_model->fetch_master_row(array('admin_uid'=>$id,'product_type'=>$data['product_type']));
        if($extend)
        {
            $data['edit_time'] = time();
            return $admin_users_extend_model->update($data,array('admin_uid'=>$id,'product_type'=>$data['product_type']));
        }
        else
        {
            $data['add_time'] = time();
            $data['edit_time'] = time();
            return $admin_users_extend_model->insert($data);
        }
    }

    /**
     * 用户扩展表删除信息
     */
    public function delete_extend($data) {
        if (empty($data)) {
            return false;
        }
        $admin_users_extend_model = Table_Model::get_instance('admin_users_extend');

        return $admin_users_extend_model->delete($data);
    }



    /**
     * 获取管理员记录总数
     * @param array|string $condition
     * @return Ambigous <multitype:, multitype:mixed string >
     */
    public function get_count($condition = '')
    {
        if (is_array($condition)) {
            $condition = $this->build_sql($condition);
        }

        $res = $this->fetch_count($condition);
        if($res === false)
        {
            return $this->_formatreturndata(false, '管理员记录总数获取失败！');
        }
        return $this->_formatreturndata(true, $res);
    }

    /**
     * 根据id获取管理员数据
     * @param unknown_type $id
     * @return boolean|Ambigous <multitype:, multitype:mixed string >
     */
    public function get_info_by_id($id)
    {
        $res = $this->fetch_by_id($id);
        return $res;
    }



    public function get_auth_list() {
        $admin_compose = Table_model::get_instance('admin_compose');
        $admin_controller = Table_model::get_instance('admin_controller');

        //后台权限
        $admin_compose_list = $admin_compose->fetch_all(array('domain'=>'admin'));
        $admin_res = array();
        foreach($admin_compose_list as $k=>$v)
        {
            $admin_res[$v['en_name']] = array(

                'name' => $v['cn_name']
            );
            $controller = $admin_controller->fetch_all(array('compose_id'=>$v['id'],'controller_id'=>0), 'id desc', 0, 0);
            foreach($controller as $kk=>$vv)
            {
                $admin_res[$v['en_name']]['children'][$vv['func_name']] = array(
                    'name' => $vv['func_name_cn']
                );

                $children_controller = $admin_controller->fetch_all(array('controller_id'=>$vv['id'],'is_right' => 1), 'id desc', 0, 0);

                foreach($children_controller as $kkk=>$vvv)
                {
                    $admin_res[$v['en_name']]['children'][$vv['func_name']]['children'][$vvv['func_name']] = array(
                        'name' => $vvv['func_name_cn'],
                        'is_menu' => $vvv['is_menu']
                    );
                }

            }
        }
        //前台权限
        $home_compose_list = $admin_compose->fetch_all(array('domain'=>'home'));
        $home_res = array();
        foreach($home_compose_list as $k=>$v)
        {
            $home_res[$v['en_name']] = array(

                'name' => $v['cn_name']
            );
            $controller = $admin_controller->fetch_all(array('compose_id'=>$v['id'],'controller_id'=>0));
            foreach($controller as $kk=>$vv)
            {
                $home_res[$v['en_name']]['children'][$vv['func_name']] = array(
                    'name' => $vv['func_name_cn']
                );

                $children_controller = $admin_controller->fetch_all(array('controller_id'=>$vv['id'],'is_right' => 1));

                foreach($children_controller as $kkk=>$vvv)
                {
                    $home_res[$v['en_name']]['children'][$vv['func_name']]['children'][$vvv['func_name']] = array(
                        'name' => $vvv['func_name_cn'],
                        'is_menu' => $vvv['is_menu']
                    );
                }

            }
        }
        $result = array(
            'admin' => $admin_res,
            'home' => $home_res
        );

        return $result;
    }


}
