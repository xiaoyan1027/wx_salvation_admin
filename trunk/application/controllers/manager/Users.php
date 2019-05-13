<?php

/**
 * 管理员管理
 * @author yapeng1@leju.com
 *
 */
error_reporting(0);
class Users extends BASE_Controller {

    private $_status = array(
        1=> '使用中',
        2=> '已暂停',
        3=> '已关闭',
    );
    private $_super = array(
        'N'=> '普通管理员',
        'Y'=> '超级管理员',
    );

    public function __construct() {
        parent::__construct();

        $this->load->model('admin/admin_user_model');
        $this->load->model('admin/admin_role_model');
        $this->load->model('admin/admin_user_extend_model');

    }

    /**
     * 添加用户
     * Author: zhangxin11@leju.com
     */
    public function add_admin_user() {

        if($this->input->post()) {
            $params['user_name']   = $this->input->post('user_name');
            $params['passwd']      = $this->input->post('passwd');
            $params['passwd_agen'] = $this->input->post('passwd_agen');
            $params['gender']      = $this->input->post('gender');
            $params['real_name']   = $this->input->post('real_name');
            $params['mobile']      = $this->input->post('mobile');
            $params['status']      = $this->input->post('status');
            $id                    = $this->input->post('id');
            $params['createtime']  = time();

            if(empty($params['user_name']) ||empty($params['gender']) ||empty($params['real_name']) ||empty($params['mobile']) ||empty($params['status'])) {
                $this->show_message('存在空数据字段');
            }


            if(!is_mobile($params['mobile'])) {
                $this->show_message('用户手机号格式不正确');
            }

            if(empty($id)) {
                if(empty($params['passwd']) ||empty($params['passwd_agen'])) {
                    $this->show_message('存在空数据字段');
                }
                if($params['passwd'] !== $params['passwd_agen']) {
                    $this->show_message('两次密码不一致');
                }
                unset($params['passwd_agen']);
                $res = $this->admin_user_model->add($params);
                if(!$res) {
                    $this->show_message("添加失败");
                }
                $this->show_message("添加成功");
            }else{
                unset($params['passwd_agen']);
                unset($params['passwd']);
                $res = $this->admin_user_model->updateuser($id,$params);

                if(!$res) {
                    $this->show_message("修改失败");
                }
                $this->show_message("修改成功");
            }
        }

        $type = '';
        $info = array();
        $id = $this->input->get('id');
        if(!empty($id)) {
            $info = $this->admin_user_model->get_user_info($id);
            $this->assign('id',$id);
            $type = "edit";
        }
        $this->assign('type',$type);
        $this->assign('info',$info);
        $this->assign('status',$this->_status);
        $this->assign('super',$this->_super);
        $this->display('add_user');
    }

    /**
     * 管理员列表
     */
    public function index() {

        header("Cache-control: public"); //解决网页过期

        $user_name = $this->input->get('user_name');

        $mobile = $this->input->get('mobile');

        if (!empty($user_name)) {
            $params['user_name'] = $user_name;
        }

        if (!empty($mobile)) {
            $params['mobile'] = $mobile;
        }

        $list = $this->admin_user_model->get_user_list($params);

        $this->assign('list',$list);

        $this->display('users_list');
    }

    /**
     * 修改密码
     * Author: zhangxin11@leju.com
     */
    public function edit_passwd() {

        if($this->input->post()) {
            $new_passwd_1 = trim($this->input->post('new_passwd_1'));
            $new_passwd_2 = trim($this->input->post('new_passwd_2'));
            $id           = $this->input->post('id');
            if (empty($new_passwd_1) || empty($new_passwd_2)) {
                $this->show_message('密码修改失败，密码不能为空', '/manager/users/edit_passwd?user_id=' . $user_id);
            }

            if ($new_passwd_1 != $new_passwd_2) {
                $this->show_message('密码修改失败，两次密码输入不同', '/manager/users/edit_passwd?user_id=' . $user_id);
            }
            if (!preg_match('/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\\\|\!\@\#\$\%\^\&\*\(\)\_\+\{\}\[\]\,\.\;\:\/\?\`\~\<\>\'\"\=\-]).{8,16}$/', $new_passwd_1)) {
                $this->show_message('密码格式不正确，至少包含大写字母、小写字母、数字、英文符号，缺一不可，8至16位，区分大小写');
            }
            $data = array(
                'updatetime' => date('Y-m-d H:i:s'),
                'passwd' => md5($new_passwd_1),
            );

            $update_res = $this->admin_user_model->updateuser($id, $data);
            if($update_res) {
                $this->show_message('修改成功');
            }
            $this->show_message('修改失败');
        }

        $id = $this->input->get('id');
        $info = $this->admin_user_model->get_info_by_id($id);
        if(empty($info)) {
           $this->show_message('用户不存在');
        }
        $this->assign('id',$id);
        $this->display('users_edit_passwd');
    }

    /**
     * 删除用户
     * Author: zhangxin11@leju.com
     */
    public function delete_user() {
        $id = $this->input->get('id');
        $res = $this->admin_user_model->user_delete($id);
        if($res) {
            $this->show_message('删除成功');
        }
        $this->show_message('删除失败');
    }

    /**
     * 角色分配
     */
    public function distribute_role() {
        $user_id = $this->input->get('id');

        if (!$user_id) {
            $this->show_message('参数错误');
        }

        $user_info = $this->admin_user_model->get_info_by_id($user_id);
        if (empty($user_info)) {
            $this->show_message('用户ID错误');
        }


        //$current_user_id = $this->login_model->get_admin_id();

//        if ($user_id == $current_user_id) {
//            //$this->show_message('没有权限,不能为自己分配权限', '/manager/users/index');
//        }

        if ($this->input->post()) {
            $roles = $this->input->post('role_ids');

            if (empty($roles) || count($roles) == 0) {
                $role_ids = '';
            } else {
                $role_ids = implode(',', $roles);
                //查询选中角色是否至少有1个角色有权限，若没有则提示
                $return = $this->checkRoleRights($role_ids);

                if (!$return) {
                    $this->show_message('请先给角色分配权限！', '/manager/users/rolelist');
                }

            }

            $res = $this->admin_user_model->updateuser($user_id, array('role_ids' => $role_ids));

            if ($res) {
                //操作日志
                $log_data = array('role_ids' => $role_ids, 'id' => $user_id);

                //删除用户缓存
                $this->right_model->delete_user_cache($user_id);

                $this->admin_log_model->log_add($log_data, '分配角色');
                $this->show_message('角色设置成功');
            } else {
                $this->show_message('角色设置失败', '/manager/users/distribute_role?id=' . $user_id);
            }
        }

        $condition = "status = 1";

        $role_list = $this->admin_role_model->getAllRoles($condition, 'create_time desc');


        if (!empty($user_info['role_ids'])) {
            $have_roles = explode(',', $user_info['role_ids']);
            $this->assign('have_roles', $have_roles);
        }

        $this->assign('role_list', $role_list);
        $this->assign('user_info', $user_info);
        $this->display('users_assign_roles');
    }


    /**
     * 查询选中角色是否至少有1个角色有权限，若没有则提示
     * @param string $role_ids 角色id
     * @return boolean
     */
    private function checkRoleRights($role_ids) {
        if (empty($role_ids)) {
            return false;
        }

        $where = "id in ({$role_ids})";
        $result = $this->admin_role_model->getFiledByCondition($where, ' permission');
        $count = count($result);
        $i = 0;
        if ($result) {
            foreach ($result as $row) {
                if ($row['permission']) {
                    $i++;
                }
            }
        }

        if ($i>0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 分配权限
     * Author: zhangxin11@leju.com
     */
    public function assign_role_rights() {

        $role_id = $this->input->get('id');

        if (!$role_id) {
            $this->show_message('参数错误');
        }

        $role_info = $this->admin_role_model->getRoleById($role_id);

        if (!$role_info) {
            $this->show_message('角色ID错误');
        }

        //只有超级管理员可以为角色分配权限
        $current_user_id = $_COOKIE['pet_admin_uid'];

        $user_info = $this->admin_user_model->get_info_by_id($current_user_id);


        if (isset($user_info['is_super']) && $user_info['is_super'] == 'N') {
            $this->show_message('没有权限,超级管理员可以分配角色');
        }

        if ($this->input->post()) {

            $rights = $this->input->post('rights');

            $data = array(
                'permission' => json_encode($rights),
                'update_time' => time(),
            );

            $res = $this->admin_role_model->updateRole($role_id,$data);

            if ($res) {
                //操作日志
//                $log_data = array('rights' => $rights, 'id' => $role_id);
//
//                //删除角色缓存
//                $this->right_model->delete_role_cache($role_id);
//
//                $this->admin_log_model->log_add($log_data, '分配权限');
                $this->show_message('权限设置成功');
            }
            else {
                $this->show_message('权限设置失败');
            }

        }

        $re = $this->admin_user_model->get_auth_list();
        $result = $re['admin'];

        $role_info['permission_set'] = $role_info['permission'] ? json_decode($role_info['permission'],TRUE) : $role_info['permission'];

        $this->assign('permission_list',$result);
        $this->assign('role_info', $role_info);
        $this->display('users_assign_role_rights');

    }



    /**
     * 获取用户详情
     * Author: zhangxin11@leju.com
     */
    public function get_user_info() {

        $id = $this->input->get('id');
        $data['id'] = $id;
        $user_info = $this->admin_user_model->get_user_info($data);
        echo "<pre />";
        print_r($user_info);die;

    }

    /**
     * 添加角色
     * Author: zhangxin11@leju.com
     */
    public function role_form(){

        if($this->input->post()) {
            $role_name = $this->input->post('role_name');
            $role_desc = $this->input->post('role_desc');
            $id        = $this->input->post('id');

            if(empty($role_name) || empty($role_desc)) {
                $this->show_message('存在为空数据，不能添加');
            }

            $where['role_name'] = $role_name;
            $info = $this->admin_role_model->getRole($where);

            $data['role_name'] = $role_name;
            $data['role_desc'] = $role_desc;

            if(empty($id)) {
                if(!empty($info)) {
                    $this->show_message('该角色已添加，请勿重复添加');
                }
                $data['create_time'] = time();
                $res = $this->admin_role_model->addRole($data);
            }else{
                $data['update_time'] = time();
                $res = $this->admin_role_model->updateRole($id,$data);
            }

            if($res) {
                $this->show_message('添加成功');
            }
            $this->show_message('添加失败');

        }
        $type = "";
        $role_info = array();
        $id = $this->input->get('id');
        if(!empty($id)) {
            $where['id'] = $id;
            $role_info = $this->admin_role_model->getRole($where);
            $type = "edit";
        }

        $this->assign('info',$role_info);
        $this->assign('id',$id);
        $this->assign('type',$type);
        $this->display('role_form');

    }

    /**
     * 角色列表
     * Author: zhangxin11@leju.com
     */
    public function role_list() {

        $data = $this->admin_role_model->getAllRoles();
        $this->assign('data',$data);
        $this->display('role_list');
    }

    /**
     * 删除权限
     * Author: zhangxin11@leju.com
     */
    public function delete_role() {

        $id = $this->input->get('id');
        $res = $this->admin_role_model->deleteRole($id);

        if($res) {
            $this->show_message('删除成功');
        }
        $this->show_message('删除失败');
    }

}

