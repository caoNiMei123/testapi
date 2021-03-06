<?php

class CarpoolListAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->check_uinfo();
        $this->exist('type');
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['user_name'] = $this->requests['user_name'];
        $arr_req['user_id'] = $this->requests['user_id'];
        $arr_req['type'] = intval($this->requests['type']);
        $arr_opt['start'] = $this->requests['start'];
        $arr_opt['limit'] = $this->requests['limit'];
        $arr_opt['status'] = $this->requests['status'];
        
        $carpool_service = CarpoolService::getInstance();
        $arr_response = $carpool_service->getList($arr_req, $arr_opt);  
        $this->set('list', $arr_response['list']);
        $this->set('has_more', $arr_response['has_more']);
        
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
