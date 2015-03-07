<?php

class DriverGetLocationAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->check_uinfo();
        $this->exist('uk');

        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['uk'] = $this->requests['uk'];

        $driver_service = DriverService::getInstance();
        $arr_response = $driver_service->get_location($arr_req, $arr_opt);        
		$this->set('gps', $arr_response['gps']); 
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
