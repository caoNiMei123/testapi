<?php

/**
 * 无状态简单的均衡冗余算法类
 */
class StrategySimple extends Strategy
{
    /**
     * 简单的选择算法，随机选择，失败后按顺序向下依次选择。
     *
     * @param [in] arrServers   : 待选择的一组机器列表。
     * @param [in] balanceCode   : 使用是否均衡因子。
     * @return mixed true: 选择成功；false: 选择失败。
     **/ 
    public function selectServer($arrServers, $intBalanceCode = false)
    {
        $serverCount = count($arrServers);
		if ($serverCount == 0) {
			return false;
		}
		$index = $this->_currentIndex;
		//随机获取一台机器，如果有指定balanceCode，则第一次使用balanceCode
		if ($this->_isFirstSelect == 1) {
			if ($intBalanceCode !== false) {
				$index = intval($intBalanceCode);
				if ($index >= $serverCount) {
					$index = 0;
				}
			} else {
				$index = rand(0, $serverCount - 1);
			}
		} else {
			$index = ($index + 1) % $serverCount;
		}
		$server = $arrServers[$index];
		$this->_currentIndex = $index;
		$this->_isFirstSelect = 0;
		return $server;
	}
}

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */