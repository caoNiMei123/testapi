<?php

class HashBaseAction extends LogicBaseAction
{
	public function initial($initObject)
	{
		parent::initial($initObject);
        // check tpl
		if(!isset($this->requests['tpl'])) {
			CLog::warning('tpl param not exist');
			$this->errorDispatch(400);
		}
		$authorizationManager = AuthorizationManager::getInstance();
        $tpl = $authorizationManager->getTpl(trim($this->requests['tpl']));
		if(CommonConfig::CHECK_TPL 
            && false === $tpl) {
			CLog::warning('tpl[%s] not valid and not permit to access friend service', $this->requests['tpl']);
			$this->errorDispatch(403);
		}
        // check sign
        $tpl_secret = $tpl['tpl_secret'];
		if(CommonConfig::CHECK_SIGN) {
			if(!isset($this->requests['sign'])) {
				CLog::warning('tpl param not exist');
				$this->errorDispatch(400);
			}
			if(false === $authorizationManager->verifySignature($this->requests['sign'], $this->requests, $this->requests['tpl'])) {
				CLog::warning('sign[%s] of tpl[%s] not permit to access friend service', $this->requests['sign'], $this->requests['tpl']);
				$this->errorDispatch(403);
			}
		}
        // check uid
        if(!isset($this->requests['uid'])) {
            CLog::warning('uid param not exist');
            $this->errorDispatch(400);
        }
        if(!is_numeric($this->requests['uid'])) {
            CLog::warning('uid param is not valid');
            $this->errorDispatch(400);
        }
        $uid = $this->requests['uid'];
        if ($uid === 0 || $uid === false) {
            CLog::warning('uid[%s] not valid and not permit to access friend service', $this->requests['uid']);
			$this->errorDispatch(403);
        } 
        $this->requests['uid'] = $uid;
		return true;
	}
}
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
