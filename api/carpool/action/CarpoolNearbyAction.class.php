<?php

class CarpoolNearbyAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->check_uinfo();
        $this->exist('gps');
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['user_name'] = $this->requests['user_name'];
        $arr_req['user_id'] = $this->requests['user_id'];
        $arr_req['gps'] = $this->requests['gps'];
        
        $carpool_service = CarpoolService::getInstance();
        $arr_response = $carpool_service->nearby($arr_req, $arr_opt);  
        $this->set('list', $arr_response['list']);
        
        
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
