<?php

/**
 * 连接抽象类
 **/
abstract class Connection
{
    protected $_curConnection = '';
    protected $_server = array();

    /**
     * 抽象连接方法, 由子类继承实现。
     *
     * @param [in] server   : 被连接机器信息
     * @param [in] intTimeout   : 超时时间，单位ms
     * @param [in] arrAuth   :  某些连接可能用到授权信息
     * @return
     **/ 
    abstract function connect($server, $intTimeout, $arrAuth);
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */