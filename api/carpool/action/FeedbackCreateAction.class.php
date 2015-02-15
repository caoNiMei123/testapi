<?php

class FeedbackCreateAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->check_uinfo();
        $this->exist('type');
        $this->exist('detail');
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['type'] = intval($this->requests['type']);
        $arr_req['detail'] = $this->requests['detail'];
        $arr_req['user_name'] = $this->requests['user_name'];
        $arr_req['user_id'] = $this->requests['user_id'];

        $feed_service = FeedbackService::getInstance();
        $arr_response = $feed_service->create($arr_req, $arr_opt);
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
