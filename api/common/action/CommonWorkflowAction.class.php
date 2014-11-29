<?php

class CommonWorkflowAction extends Action
{
	protected $action_params = array();

	public function initial($initObject)
	{
		if (!is_array($initObject)) {
			$this->action_params = array($initObject);
		} elseif (count($initObject) <= 0) {
			$this->action_params = array(null);
		} else {
			$this->action_params = $initObject;
		}
		return true;
	}

	public function execute(Context $context, array $actionParams = array())
	{
		//template action
		$cnt = count($this->action_params);
		for ($i = 1; $i < $cnt; ++$i) {
			$actionClassName = $this->action_params[$i];
			$action = new $actionClassName;
			$action->initial($this->action_params[0]);
			if ($action->execute($context, $this->action_params[0]) === false) {
				break;
			}
		}

		$pageBuilderAction = new PageBuilderAction();
		$pageBuilderAction->initial($this->action_params[0]);
		$pageBuilderAction->execute($context, $this->action_params[0]);

		return true;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=90 noet: */