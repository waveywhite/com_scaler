<?php
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

// no direct access
defined('_JEXEC') or die('Restricted access');

abstract class ScalerHelperRoute {

	/**
	 * Return a link for the given view
	 * @param String view the name of the view
	 */
	public static function getRoute($view="image") {
	
		//the basic link without an Itemid
		$link = 'index.php?option=com_scaler';
	
		//search for menu
		$itemid = self::getItemId($view);
	
		if ($itemid)
			$link .= '&Itemid=' . $itemid;
		else
			$link .= '&view=' . $view;
	
		return $link;
	}
	
	
	public static function getItemId($view) {
		$app = Factory::getApplication();
		
		//Set attributes for searching the menus
		$attributes = array(
				'component_id'
		);
		$component = ComponentHelper::getComponent('com_scaler');
		$values = array(
				$component->id
		);
		
		//search through com_rail menus
		$menus = $app->getMenu('site');
		$items = $menus->getItems($attributes, $values);
		foreach ($items as $i) {
			if (isset($i->query['view']) && strtolower($i->query['view']) == strtolower($view)) {
				return $i->id;
			}
		}
		return false;
	}
	
}
