<?php

use Joomla\CMS\Factory;

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.installer.installer');
jimport('joomla.installer.helper');

class com_scalerInstallerScript {

	/**
	 * Constructor
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	public function __constructor(JAdapterInstance $adapter) {
		
	}
	
	private $oldRelease = 0;
	
	
	/**
	 * Method to install the component
	 *
	 * @param mixed $parent The class calling this method
	 * @return void
	*/
	function install(JAdapterInstance $parent) {
		echo JText::_('COM_RAIL_INSTALL_SUCCESS');
	}
	
	/**
	 * Method to update the component
	 *
	 * @param mixed $parent The class calling this method
	 * @return void
	 */
	function update(JAdapterInstance $parent) {
		jimport( 'joomla.filesystem.file' );
		jimport( 'joomla.application.component.controller' );
		
		Factory::getApplication()->enqueueMessage('Updating com_scaler to version '.
			$parent->get('manifest')->version);
		
		return true;
	}
	
	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @param mixed $parent The class calling this method
	 * @return void
	 */
	function preflight($route, JAdapterInstance $parent) {
		$this->oldRelease = $this->getParam ( 'version' );
		
		$files = array ();
		
		//example of how to clean up files. If the old version is before
		//a specified version, list files that are no longer wanted
		
// 		if (version_compare ( $this->oldRelease, "0.1.2" ) < 0) {
// 			$files []=  JPATH_ADMINISTRATOR . "/components/com_rail/file/path.php";
// 		}
				
		$this->cleanUpFiles ( $files );
		
		return true;
	}
	 
	/**
	 * method to run after an install/update/uninstall method
	 *
	 * @param mixed $parent The class calling this method
	 * @return void
	 */
	function postflight($route, JAdapterInstance $parent) {
		return true;
	}
	
	
	/**
	 * get a variable from the manifest file (actually, from the manifest cache).
	*/
	function getParam( $name ) {
		$db = Factory::getDbo();
		$db->setQuery('SELECT `manifest_cache` FROM #__extensions WHERE `name` = "rail" AND `type` = "component"');
		$manifest = json_decode( $db->loadResult(), true );
		return $manifest[ $name ];
	}
	
	/**
	 * Removes the files in the given array. Use in preflight to clean up files that
	 * are no longer needed.
	 * @param array $files
	 */
	function cleanUpFiles( $files ) {
		foreach ($files as $file) {
			if (!file_exists($file))
				continue;
			if (filetype($file) === 'dir')
				JFolder::delete($file);
			else
				JFile::delete($file);
			Factory::getApplication()->enqueueMessage('Deleted '.$file);
		}
	}

}
