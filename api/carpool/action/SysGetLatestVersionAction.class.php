<?php

class SysGetLatestVersionAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        $this->exist('type');
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        $user_type = intval($this->requests['type']);
        if($user_type = UserService::USERTYPE_DRIVER)
        {
            $version_array = VersionConfig::$driver_version;
        }
        else
        {
            $version_array = VersionConfig::$passenger_version;
        }    
        $this->set('version', $version_array['version']);
        $this->set('url', $version_array['url']);
        $this->set('detail', $version_array['detail']);
        $this->set('force_update', $version_array['force_update']);

        
        
    }
    
    public function doGet()
    {
        $this->doPost();
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
