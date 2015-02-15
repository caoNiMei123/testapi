<?php

class ImageUploadAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->check_uinfo();
    
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['user_name'] = $this->requests['user_name'];
        $arr_req['user_id'] = $this->requests['user_id'];
        $arr_req['file'] = file_get_contents('php://input');

        $image_service = ImageService::getInstance();
        $image_service->upload($arr_req, $arr_opt);
        
        
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
