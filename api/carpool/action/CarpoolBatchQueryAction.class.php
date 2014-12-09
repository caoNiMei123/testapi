<?php

class CarpoolBatchQueryAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        if (!isset($this->requests['user_name']) || !isset($this->requests['user_id']) || !isset($this->requests['user_type']))
        {
            throw new Exception("carpool.auth user is not login");
        }
        if (!isset($this->requests['list']) )
        {
            throw new Exception("carpool.param no list");
        }
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['list'] = $this->requests['list'];
        $arr_req['user_id'] = $this->requests['user_id'];
        $arr_req['user_type'] = intval($this->requests['user_type']);
        $arr_req['user_name'] = $this->requests['user_name'];
        
        $carpool_service = CarpoolService::getInstance();
        $arr_response = $carpool_service->batch_query($arr_req, $arr_opt);   
        $this->set('list', $arr_response['list']);
          
        
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */