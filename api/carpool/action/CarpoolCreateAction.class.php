<?php

class CarpoolCreateAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->exist('user_name', "carpool.auth");
        $this->exist('user_id', "carpool.auth");
        $this->exist('user_type', "carpool.auth");
        $this->exist('src');
        $this->exist('dest');
        $this->exist('src_gps');
        $this->exist('dest_gps');
        
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
        $arr_req['devuid'] = $this->requests['devuid'];

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
