<?php

/**
 * Resouce Factory to get instances of some resouce.
 **/
class ResourceFactory
{
	/**
	 * Smarty instance
	 * @var Smarty
	 */
	private static $smarty = null;

	public static function getSmartyInstance()
	{
		if (self::$smarty === null) {
			$smarty = new Smarty();
			$smarty->setTemplateDir(SMARTY_TEMPLATE_DIR);
			$smarty->setCompileDir(SMARTY_COMPILE_DIR);
			$smarty->setConfigDir(SMARTY_CONFIG_DIR);
			$smarty->setCacheDir(SMARTY_CACHE_DIR);
			$smarty->addPluginsDir(SMARTY_PLUGIN_DIR);
			$smarty->left_delimiter = SMARTY_LEFT_DELIMITER;
			$smarty->right_delimiter = SMARTY_RIGHT_DELIMITER;
			
			self::$smarty = $smarty;
		}
		
		return self::$smarty;
	}
}



/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */