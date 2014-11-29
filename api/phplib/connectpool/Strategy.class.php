<?php

/*
 * 策略抽象类
 */
abstract class Strategy
{
    protected $_arrConfig = array();
    protected $_isFirstSelect = 1;
    protected $_currentIndex = false;

    public function __construct($arrConfig)
    {
        $this->_arrConfig = $arrConfig;
        return true;
    }
    
    /**
     * 抽象selectServer方法，子类实现该方法进行一组机器中的选择。
     *
     * @param [in] arrServers   : 待选择的一组机器列表。
     * @param [in] balanceCode   : 使用是否均衡因子。
     * @return array
     **/ 
    abstract function selectServer($arrServers, $balanceCode=false);

    /**
     * 默认markFail方法，在连接失败时，需要的策略进行调用。子类重新实现。
     *
     * @param [in] server   : 连接失败了的机器信息。
     * @return bool
     **/ 
    public function markFail($server)
    {
        return true;
    }

    /**
     * 默认markSucc方法，在连接成功时，需要的策略进行调用。子类重新实现。
     *
     * @param [in] server   : 被连接成功了的机器信息。
     * @return bool
     **/ 
    public function markSucc($server)
    {
        return true;
    }
}


/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */