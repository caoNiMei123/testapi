<?php
/***************************************************************************
 * 
 * 
 **************************************************************************/
 
 
 
/**
 * @file get_url.php
 * @author gaowei
 * @date 2015/01/05 11:23:48
 * @brief 
 *  
 **/

require_once "./sdk.class.php";
$user_id = $argv[1];

$oss_sdk_service = new ALIOSS();
$oss_sdk_service->set_host_name("oss-cn-beijing.aliyuncs.com");
$head_bucket = 'real-pin';
$head_object = 'head_' . $user_id;
$response = $oss_sdk_service->get_sign_url($head_bucket, $head_object, 3600);            
echo "$response\n";



/* vim: set expandtab ts=4 sw=4 sts=4 tw=100 */
?>
