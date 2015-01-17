<?php

class ImageThumbnailAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->exist('uk');
        $this->exist('timestamp');
        $this->exist('sign');        
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['uk'] = intval($this->requests['uk']);
        $arr_req['timestamp'] = $this->requests['timestamp'];
        $arr_req['sign'] = $this->requests['sign'];
        
        

        $image_service = ImageService::getInstance();
        $arr_response = $image_service->thumbnail($arr_req, $arr_opt);
        echo $arr_response;
        return;
        
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
