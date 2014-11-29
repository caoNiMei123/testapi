<?php

class CarpoolCreateAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        if (!isset($this->requests['user_name']) || !isset($this->requests['user_id']) || !isset($this->requests['user_type'])) {
            throw new Exception("carpool.auth user is not login");
        }
        if (!isset($this->requests['src']) || !isset($this->requests['dest']) || !isset($this->requests['src_gps']) || !isset($this->requests['dest_gps'])) {
            throw new Exception("carpool.param param error");
        }
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['user_name'] = $this->requests['user_name'];
        $arr_req['user_id'] = $this->requests['user_id'];
        $arr_req['user_type'] = intval($this->requests['user_type']);
        $arr_req['src'] = $this->requests['src'];
        $arr_req['dest'] = $this->requests['dest'];
        $arr_req['src_gps'] = $this->requests['src_gps'];
        $arr_req['dest_gps'] = $this->requests['dest_gps'];

        $carpool_service = CarpoolService::getInstance();
        $arr_response = $carpool_service->create($arr_req, $arr_opt);       
        $this->set('pid', $arr_response['pid']);
        $this->set('timeout', $arr_response['timeout']);
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
