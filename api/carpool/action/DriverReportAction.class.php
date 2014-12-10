<?php

class DriverReportAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->exist('user_name', "carpool.auth");
        $this->exist('user_id', "carpool.auth");
        $this->exist('user_type', "carpool.auth");
        $this->exist('gps');
        $this->exist('devuid');
        
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['user_name'] = $this->requests['user_name'];
        $arr_req['user_id'] = $this->requests['user_id'];
        $arr_req['user_type'] = intval($this->requests['user_type']);
        $arr_req['gps'] = $this->requests['gps'];
        $arr_req['devuid'] = $this->requests['devuid'];
        

        $driver_service = DriverService::getInstance();
        $arr_response = $driver_service->report($arr_req, $arr_opt);        
        
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
