<?php
/**
 * 微信支付调取方法
 *
 */
class lib_weixin_pay{
    private $wx_pay_helper;
    private $common_util;
    private $md5_sign_util;
    private $_appid;
    private $_appsecret;
    private $_token;
    public function __construct($config){
        $this->_appid = trim($config['appid']);
        $this->_appsecret = trim($config['appsecret']);
        $this->_token = trim($config['token']);
        $this->wx_pay_helper = new lib_weixin_payhelper($config);
        $this->common_util = new CommonUtil();
        $this->md5_sign_util = new MD5SignUtil();
    }

    /**
     * 组装订单详情信息包
     * 参数类型均为 string
     * @param $body 商品描述
     * @param $partner 注册时分配的财付通商户号 partnerId
     * @param $out_trade_no 商户系统内部的订单号， 32 个字符内、可包含字母，确保在商户系统唯一；
     * @param $total_fee 订单总金额，单位为分
     * @param $notify_url 在支付完成后，接收微信通知支付结果的 URL，需给绝对路径， 255 字符内 , 格式如:http://wap.tenpay.com/tenpay.asp；
     */
    public function create_biz_package($body, $out_trade_no, $total_fee, $notify_url, $goods_tag = '', $risk_info = ''){
        $this->wx_pay_helper->setParameter("bank_type", "WX");
        $this->wx_pay_helper->setParameter("body", $body);
        $this->wx_pay_helper->setParameter("out_trade_no", $out_trade_no);
        $this->wx_pay_helper->setParameter("total_fee", $total_fee);
        $this->wx_pay_helper->setParameter("fee_type", "1");
        $this->wx_pay_helper->setParameter("notify_url", $notify_url);
        $this->wx_pay_helper->setParameter("goods_tag",$goods_tag);
        $this->wx_pay_helper->setParameter("risk_info",$risk_info);

        $ip = get_client_ip();

        if(strpos($ip, ',') !== FALSE) {
            $ip = substr($ip, 0, strpos($ip, ','));//解决代理IP太长造成购买失败问题
        }
        $this->wx_pay_helper->setParameter("spbill_create_ip", $ip);
        $this->wx_pay_helper->setParameter("input_charset", "UTF-8");
        $ret = $this->wx_pay_helper->create_biz_package();
        return $ret;
    }

    /**
     * 回调后，验证参数合法性
     */
    public function check_sign($get_data){
        if(!$get_data){
            return false;
        }
        if(!isset($get_data['sign']) || empty($get_data['sign'])){
            return false;
        }
        $sign = $get_data['sign'];
        if(isset($get_data['site'])){
            unset($get_data['site']);
        }
        if(isset($get_data['ctl'])){
            unset($get_data['ctl']);
        }
        if(isset($get_data['act'])){
            unset($get_data['act']);
        }
        if(isset($get_data['wx_id'])){
            unset($get_data['wx_id']);
        }
        unset($get_data['sign']);
        $format_str = $this->common_util->formatQueryParaMap($get_data, '');
        $res = $this->md5_sign_util->verifySignature($format_str, $sign, $this->wx_pay_helper->_partnerkey);
        return $res;
    }
    /**
     * 发货通知 delivernotify
     * @param $openid 用户openid；
     * @param $transaction_id 微信交易订单号
     * @param $out_trade_no 云购订单号
     * @param $deliver_status 是发货状态，1 表明成功，0 表明失败，失败时需要在 deliver_msg 填上失败原因
     * @param $deliver_msg 是发货状态信息，失败时可以填上 UTF8 编码的错诨提示信息，比如“该商品已退款”
     */
    public function delivernotify($openid, $transaction_id, $out_trade_no, $access_token, $deliver_status = '1', $deliver_msg = 'ok'){
        $this->wx_pay_helper->setParameter("openid", $openid);
        $this->wx_pay_helper->setParameter("transid", $transaction_id);
        $this->wx_pay_helper->setParameter("out_trade_no", $out_trade_no);
        $this->wx_pay_helper->setParameter("deliver_status", $deliver_status);
        $this->wx_pay_helper->setParameter("deliver_msg", $deliver_msg);
        $post_data = $this->wx_pay_helper->create_delivernotify_data();
        $url = 'https://api.weixin.qq.com/pay/delivernotify?access_token='.$access_token;
        $lib_http = new lib_http();
        $ret = $lib_http->http($url, 'POST', $post_data);
        if(! is_array($ret)){
            $ret = json_decode($ret, true);
        }
        return $ret;
    }
    
    /**
     * 订单查询
     * @param unknown $access_token
     * @param unknown $out_trade_no
     * @return Ambigous <mixed, string>
     */
    public function orderquery($access_token, $out_trade_no)
    {
        $this->wx_pay_helper->setParameter("out_trade_no", $out_trade_no);
        $post_data = $this->wx_pay_helper->create_orderquery_data();        
        
        //http://api.weixin.qq.com/cgi-bin/pay/orderquery?access_token=xxxxxx
        $url = 'https://api.weixin.qq.com/pay/orderquery?access_token='.$access_token;
        $lib_http = new lib_http();
        $ret = $lib_http->http($url, 'POST', $post_data);
        if(! is_array($ret)){
            $ret = json_decode($ret, true);
        }
        return $ret;
    }
}

