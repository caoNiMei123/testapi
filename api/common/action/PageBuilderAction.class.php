<?php

/**
 * 页面渲染Action，一般作为Action链中的最后一个Action执行
 * 
 **/
class PageBuilderAction extends TemplateBasedAction
{
	public function doExecute()
	{
	    /*
		// Avoid to cache the error page
		if ($this->errno() != CommonConst::SUCCESS) {
			$this->setNoBrowserCache();
		}
		*/
		
		// Assign the common template var
		/*
		$this->assign('islogin', $this->context_params['is_login']);
		$this->assign('userid', $uid);
		$this->assign('portrait', $this->context_params['op_portrait']);
		$this->assign('mobile', $this->context_params['op_mobile']);
		$this->assign('email', $this->context_params['op_email']);
		$this->assign('session', $this->context_params['session']);
		$this->assign('static_domain', STATIC_DOMAIN);
		$this->assign('pass_domain', PASSPORT_DOMAIN);
		$this->assign('current_year', date('Y'));
		$this->assign('bkstoken', $this->context_params['bkstoken']);
		$this->assign('ishttps', $this->isHttpsRequest());
		$this->assign('ispost', $this->is_post);
		$this->assign('errno', $this->errno());
		$this->assign('errmsg', $this->errmsg());
		*/
		
		$this->display($this->getTplName());

		return true;
	}

}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */