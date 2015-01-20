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

        
        $arr_opt['type'] = intval($this->requests['type']);
        $arr_opt['reason'] = intval($this->requests['reason']);

        if (isset($this->requests['reason']) && intval($this->requests['reason']) == UserService::REASONTYPE_PASSENGER_AUTH)
        {
            
            if (!isset($this->requests['user_name']) || !isset($this->requests['user_id']) || !isset($this->requests['user_type']))
            {
                throw new Exception("carpool.auth user is not login");
            }
            $arr_opt['user_name'] = $this->requests['user_name'];
            $arr_opt['user_id'] = $this->requests['user_id'];

            
        }     

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
