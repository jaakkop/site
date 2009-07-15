<?php
/**
* Front Controller Plugin
 *
* @uses       Zend_Controller_Plugin_Abstract
* @category   Oibs
* @package    Oibs_Controller
* @subpackage Plugins
*/
class Oibs_Controller_Plugin_Language extends Zend_Controller_Plugin_Abstract
{
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$config = Zend_Registry::get('config');
		
		// Get languages
		$locale = new Zend_Locale();		
		$options = array('scan' => Zend_Translate::LOCALE_FILENAME, 'disableNotices' => true);
		
		$translate = @new Zend_Translate('tmx', $config->language->path/*APPLICATION_PATH . '/languages/'*/, 'auto', $options);

		// 
		$params = $this->getRequest()->getParams();
		
		$language = false;
		
		if(isset($params['language']))
		{
			$language = $params['language'];
		}
		
		if($language == false)
		{
			$language = $translate->isAvailable($locale->getLanguage) ? $locale->getLanguage() : $config->language->default;
		}

		if(!$translate->isAvailable($language))
		{
			$language = $config->language->default;
			//throw new Zend_Controller_Action_Exception('This page does not exist', 404);
		}
		//else
		//{
			$locale->setLocale($language);
			$translate->setLocale($locale);
			
			Zend_Form::setDefaultTranslator($translate);
			
			//setcookie('lang', $locale->getLanguage(), null, '/');
			
			Zend_Registry::set('Zend_Locale', $locale);
			Zend_Registry::set('Zend_Translate', $translate);
			Zend_Registry::set('Available_Translations', $translate->getList());	
		//}
	}
}
?>