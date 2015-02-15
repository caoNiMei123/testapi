<?php

class SysGetLatestVersionAction extends CarpoolBaseAction
{   
    public function doPost()
    {
        // 1. 基本检查，必选参数是否存在
        
        // 2. 取参数，分成必选和可选
        $arr_req = array();
        $arr_opt = array();
        
        $version_array = VersionConfig::$version;
        
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
