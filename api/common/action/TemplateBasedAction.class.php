<?php

class TemplateBasedAction extends BaseAction
{
	/**
	 * Template file for current request, actions extends from TemplateBasedAction
	 * should init it before execute() be called.
	 * 
	 * @var string
	 */
	protected $tpl = '';
	
	/**
	 * Smarty instance.
	 * @var Smarty
	 */
	protected $smarty;
	
    public function initial($initObject)
    {
    	parent::initial($initObject);
		
    	$this->setContentType('text/html');

    	// 判断用户登录结果
		if (isset($this->action_params['checkLogin']) && 
			true == $this->action_params['checkLogin']) {
			if (CommonConfig::$checkLoginResult['succ'] != $this->user_info['result']) {
				if (CommonConfig::$checkLoginResult['login'] == $this->user_info['result']) {
					PassportProxy::redirectLogin();
				} elseif (CommonConfig::$checkLoginResult['err'] == $this->user_info['result']) {
					// TODO: 错误页面
					echo 'TODO 此处是500页，Check login失败';
					die();
				}
			}
		}
    	
    	$this->smarty = ResourceFactory::getSmartyInstance();
    	return true;
    }
    
    protected function doExecute()
    {
    	$this->doPageExecute();
    	$this->assign('tpl', $this->tpl);
		$this->setTplName($this->tpl);
		return true;
    }
    
    protected function doPageExecute() {
    	return true;
    }
	/**
	 * Set smarty template file name.
	 * 
	 * @param string $tplName Template file name
	 * @return void
	 */
	protected function setTplName($tplName)
	{
		$this->context_params['tplname'] = $tplName;
	}
	
	/**
	 * Get smarty template file name.
	 * 
	 * @return string
	 */
	protected function getTplName()
	{
		return $this->context_params['tplname'];
	}
    
	/**
	 * Assign value to a smarty template var.
	 * 
	 * @param string $var	Template variable name
	 * @param mix $value	Template variable value
	 * @return void
	 */
	protected function assign($var, $value)
	{
		if ($this->is_debug) {
			echo "<br><h1>var:$var:<br></h1>";
		}
		$this->smarty->assign($var, $value);
	}

	/**
	 * Display a smarty template page.
	 * 
	 * @param string $tplName Template file name
	 * @return void
	 */
	protected function display($tplName)
	{
		$this->smarty->display(APP_NAME . '/' . $tplName);
	}
	
	/**
	 * Fetch rendered smarty template output.
	 * 
	 * @param string $tplName Template file name
	 * @return string
	 */
	protected function fetch($tplName)
	{
		return $this->smarty->fetch(APP_NAME . '/' . $tplName);
	}
	
	/**
	 * Csrf attack prevention strategy for template based actions.
	 * 
	 * @return void
	 */
	protected function doCsrfAttackPrevention()
	{
		// added by zhanglei18 对于CsrfAttack，统一回复403
        parent::doCsrfAttackPrevention();
        
	    /*
		if (empty($this->tpl)) {
			parent::doCsrfAttackPrevention();
		} else {
			// All action chains for template based pages contain the
			// PageBuilderAction, so we just need to set the template
			// file name.
			$this->setTplName($this->tpl);
		}
		*/
		// added by zhanglei18
	}
}
 
/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */