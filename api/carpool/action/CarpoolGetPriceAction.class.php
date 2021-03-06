<?php

class CarpoolGetPriceAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->check_uinfo();
        $this->exist('mileage');
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $arr_req['mileage'] = $this->requests['mileage'];

        $carpool_service = CarpoolService::getInstance();
        $arr_response = $carpool_service->getPrice($arr_req, $arr_opt);  
        $this->set('price', $arr_response['price']);
        
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
