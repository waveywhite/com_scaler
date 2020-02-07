<?php

use Joomla\CMS\Factory;

defined ( '_JEXEC' ) or die ( 'Restricted access' );

class ScalerControllerDefault extends JControllerBase {
	public function execute() {
		$app = $this->getApplication();
		$doc = Factory::getDocument();		
		require_once JPATH_COMPONENT_SITE . '/lib/inflector.php';
		$inflector = ScalerInflector::getInstance();
		
		$viewName = $app->input->getWord ( 'view' );

		if (!$viewName)
			throw new Exception(JText::_('COM_SCALER_ERROR_VIEW_NOT_FOUND'), 404);

		$viewFormat = $doc->getType ();
		$layoutName = $app->input->getWord ( 'layout', 'default' );
		
		// Register the layout paths for the view
		$paths = new SplPriorityQueue ();
		$paths->insert ( JPATH_COMPONENT_SITE . '/views/' . $viewName . '/tmpl', 'normal' );
		
		//create model details
		$isPlural = !$inflector->isSingular($viewName);
		$modelName = $isPlural ? $inflector->toSingular($viewName) : $viewName;
		$modelClass = 'ScalerModel' . ucfirst ( $modelName );

		//load the view. Try a generic base view for a particular format if the specific file does not exist
		$viewFile = JPATH_COMPONENT_SITE . '/views/' . $viewName . '/' . $viewFormat . '.php';
		if (file_exists($viewFile)) {
			require_once $viewFile;
			$viewClass = 'ScalerView' . ucfirst ( $viewName ) . ucfirst ( $viewFormat );
		} else {
			$formatName = $isPlural ? $inflector->toPlural($viewFormat) : $viewFormat;
			require_once JPATH_COMPONENT_SITE . '/base/view-' . $formatName . '.php';
			$viewClass = 'ScalerView' . ucfirst( $formatName );
		}
		//load the model
		require_once JPATH_COMPONENT_SITE . '/models/' . $modelName . '.php';
		
		//create the view and set the layout
		$view = new $viewClass ( new $modelClass ($modelName), $paths );
		if (is_a($view, 'JViewHtml'))
			$view->setLayout ( $layoutName );
		
		// Render our view.
		echo $view->render ();
		
		return true;
	}
}