<?php 
use Joomla\CMS\Factory;
use Joomla\CMS\Component\Router\RouterInterface;

// no direct access
defined ( '_JEXEC' ) or die ( 'Restricted access' );

class ScalerRouter implements RouterInterface {

	/**
	 * Prepare-method for URLs
	 * This method is meant to validate and complete the URL parameters.
	 * For example it can add the Itemid or set a language parameter.
	 * This method is executed on each URL, regardless of SEF mode switched
	 * on or not.
	 *
	 * @param   array  $query  An associative array of URL arguments
	 *
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 *
	 * @since   3.3
	 */
	public function preprocess($query) {
		return $query;
	}
	
	/**
	 * Build method for URLs
	 * This method is meant to transform the query parameters into a more human
	 * readable form. It is only executed when SEF mode is switched on.
	 *
	 * @param   array  &$query  An array of URL arguments
	 *
	 * @return  array  The URL arguments to use to assemble the subsequent URL.
	 *
	 * @since   3.3
	 */
	public function build(&$query) {
		$segments = array ();
		$view = "image";
		
		if (isset($query['id'])) {
			$segments []= $query['id'];
			unset ($query['id']);
		}
		if (isset($query['width'])) {
			$segments []= $query['width'];
			unset ($query['width']);
		}
		if (isset($query['height'])) {
			$segments []= $query['height'];
			unset ($query['height']);
		}
		if (isset($query['path'])) {
			$segments []= basename($query['path']);
			unset ($query['path']);
		}
		
		if (isset($query['view']) && $view == $query['view']) {
			unset($query['view']);
		}
		
		return $segments;
	}

	/**
	 * Parse method for URLs
	 * This method is meant to transform the human readable URL back into
	 * query parameters. It is only executed when SEF mode is switched on.
	 *
	 * @param   array  &$segments  The segments of the URL to parse.
	 *
	 * @return  array  The URL attributes to be used by the application.
	 *
	 * @since   3.3
	 */
	public function parse(&$segments) {
		$attribs = ['view' => 'image', 'format' => 'raw'];
		$app = Factory::getApplication ();
		$menu = $app->getMenu ();
		$item = $menu->getActive ();
		
		// Only consider the active menu if it is from this component
		if ($item != null && $item->query ['option'] == 'com_scaler') {
			
			if (isset($item->query ['view']))
				$attribs['view'] = $item->query['view'];
			
			if (isset($item->query ['format']))
				$attribs['format'] = $item->query['format'];
		}
		
		$attribs['id'] = array_shift($segments);
		$attribs['width'] = array_shift($segments);
		$attribs['height'] = array_shift($segments);
		$attribs['path'] = implode('/', $segments);
		
		//If we want raw format, we can't rely on the cached document because if a system plugin has already loaded it,
		//it will default to html. Route parsing is called before application dispatch so we need to do it here.
		if (isset($attribs['format']) && $attribs['format'] == "raw") {
			$app->input->set('format', 'raw');
			Factory::$document = null;
			Factory::getDocument();
			$app->loadDocument();
		}
				
		return $attribs;
	}
		
}