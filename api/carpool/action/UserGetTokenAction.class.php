<?php

class UserGetTokenAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->exist('account');
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();

        $arr_opt['sign'] = intval($this->requests['sign']);
        $arr_req['timestamp'] = intval($this->requests['timestamp']);
        $arr_req['account'] = $this->requests['account'];       
        $arr_req['devuid'] = $this->requests['devuid'];
        $user_service = UserService::getInstance();
        $arr_response = $user_service->get_token($arr_req, $arr_opt);
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
