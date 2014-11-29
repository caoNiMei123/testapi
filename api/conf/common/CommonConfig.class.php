<?php

class CommonConfig
{
	// 是否检查tpl合法性
	const CHECK_TPL = true;
	
	// 是否检查签名合法性
    // 上线需要改成true
	const CHECK_SIGN = false;
	
	public static $arrExceptionReturnMap = array(
        'carpool.auth'     => array('errno'=>CommonConst::EC_CM_NO_PERMISSION, 'httpCode'=>'403'),
		'carpool.param'    => array('errno'=>CommonConst::EC_CM_PARAM_ERROR, 'httpCode'=>'400'),
		'carpool.internal' => array('errno'=>CommonConst::EC_CM_SERVICE_INVALID, 'httpCode'=>'500'),
		'carpool.reject'   => array('errno'=>CommonConst::EC_CM_SERVER_REJECT, 'httpCode'=>'409'),
		'carpool.apinotsupport' => array('errno'=>CommonConst::EC_CM_API_UNSUPPORTED, 'httpCode'=>'404'),
    );


    
    
    //检查openapi是否为https访问，上线时需要改为true
    const CHECK_OPENAPI_HTTPS = false;
    
    //是否启动限流
    const CHECK_RESTRICT = false;
    
    
   
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
