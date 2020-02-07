<?php

use Joomla\CMS\Factory;

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

//sessions
jimport( 'joomla.session.session' );
 
//application
$app = Factory::getApplication();
// Require specific controller if requested
$controller = $app->input->get('controller','default');

// Create the controller
require_once __DIR__ . '/controllers/' . strtolower($controller) . '.php';
$classname  = 'ScalerController'.ucwords($controller);
$controller = new $classname();
// Perform the Request task
$controller->execute();
