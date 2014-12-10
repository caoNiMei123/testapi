<?php

class UserReportAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->exist('user_name', "carpool.auth");
        $this->exist('user_id', "carpool.auth");
        $this->exist('user_type', "carpool.auth");
        $this->exist('client_id');
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['client_id'] = $this->requests['client_id'];
        $arr_req['user_name'] = $this->requests['user_name'];
		$arr_req['user_id'] = $this->requests['user_id'];
		$arr_req['user_type'] = intval($this->requests['user_type']);
		$arr_req['devuid'] = $this->requests['devuid'];

        $user_service = UserService::getInstance();
        $arr_response = $user_service->report($arr_req, $arr_opt);
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
