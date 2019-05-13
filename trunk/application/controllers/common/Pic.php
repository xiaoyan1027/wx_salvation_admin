<?php

/**
 * 图片管理
 * @author Administrator
 *
 * 上传图片需绕开公众账号限制
 */
class pic extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('lib_upload');
    }

    /*
     * 上传图片
     * */
    public function upload_pics_to_vote() {
        $message = "";
        if (count($_FILES) > 0) {
            $pic_res = $this->lib_upload->upload_pic();
            if ($pic_res['result']) {
                //output_ok($pic_res['info']['furl']);
                echo json_encode(array('url' => $pic_res['info']['furl']));
                exit;
            } else {
                $message = $pic_res['info'];
            }
        }

        echo json_encode($message);
    }
}