<?php

class UserLoginAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        if (!isset($this->requests['account']))
        {
            throw new Exception("carpool.param user_id not exist");
        }
        
        if (!isset($this->requests['secstr']) || !isset($this->requests['type']))
        {
            throw new Exception("carpool.param secstr or type not exist");
        }
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['account'] = $this->requests['account'];
        $arr_req['secstr'] = $this->requests['secstr'];     
        $arr_req['type'] = $this->requests['type'];     

        $user_service = UserService::getInstance();
        $arr_response = $user_service->login($arr_req, $arr_opt);
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
