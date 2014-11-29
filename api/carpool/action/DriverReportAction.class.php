<?php

class DriverReportAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        if (!isset($this->requests['user_name']) || !isset($this->requests['user_id']) || !isset($this->requests['user_type']))
        {
            throw new Exception("carpool.auth user is not login");
        }
        if (!isset($this->requests['gps']))
        {
            throw new Exception("carpool.param param error");
        }
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['user_name'] = $this->requests['user_name'];
        $arr_req['user_id'] = $this->requests['user_id'];
        $arr_req['user_type'] = intval($this->requests['user_type']);
        $arr_req['gps'] = $this->requests['gps'];
        

        $driver_service = DriverService::getInstance();
        $arr_response = $driver_service->report($arr_req, $arr_opt);        
        
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
