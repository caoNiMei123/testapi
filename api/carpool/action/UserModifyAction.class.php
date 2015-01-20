<?php

class UserModifyAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->check_uinfo();
               
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();       
        $arr_req['user_id'] = $this->requests['user_id'];
        $arr_req['user_type'] = intval($this->requests['user_type']);
        $arr_req['user_name'] = $this->requests['user_name'];
        $arr_opt['name'] = $this->requests['name'];
        $arr_opt['sex'] = $this->requests['sex'];
        $arr_opt['car_num'] = $this->requests['car_num'];
        $arr_opt['car_engine_num'] = $this->requests['car_engine_num'];
        
        $user_service = UserService::getInstance();
        $arr_response = $user_service->modify($arr_req, $arr_opt);
        
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
