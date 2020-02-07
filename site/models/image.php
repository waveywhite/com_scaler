<?php
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;

/********************************
 *
 * (c) 2016 Netriver Systems Limitd
 *
 ********************************/

//no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

/**
 * Model for image scaling.
 * 
 * 1. On page render call request to log a scaling request and obtain a URL.
 * 2. The URL directs to this component which will provide a rendered image on demand
 * 3. By default this model caches data, unless configured not to.
 * 
 * This component is intended to operate behind a CDN. It is not possible to formulate a
 * URL to get an arbitrarily sized image because the image must be requested first on page render.
 * 
 * @author david
 *
 */
class ScalerModelImage extends JModelBase
{
	/**
	 * ScalerModelImage instance
	 * @var ScalerModelImage
	 */
	static protected $instance = null;
	
	
	protected $imagick = null;

	/**
	 * Component parameters
	 * @var \Joomla\Registry\Registry
	 */
	protected $params = null;
	/**
	 * Store for request results, to avoid duplication
	 * @var array
	 */
	protected static $requests = array();
	
	/**
	 * Store for pathids, to avoid duplication
	 * @var array
	 */
	protected static $pathids = array();

	function __construct()
	{
		parent::__construct();
		$this->params = ComponentHelper::getParams('com_scaler');
		
		$app = Factory::getApplication();
		
		$this->state->set('path', $app->input->get('path', null, 'PATH'));
		$this->state->set('width', $app->input->get('width', null, 'UINT'));
		$this->state->set('height', $app->input->get('height', null, 'UINT'));
		$this->state->set('id', $app->input->get('id', null, 'UINT'));
		
	}

	
	/**
	 * Return the instance of this model
	 * @return ScalerModelImage
	 */
	static function getInstance() {
		if (!self::$instance) {
			self::$instance = new ScalerModelImage();
		}
		return self::$instance;
	}


	/**
	 * Stores a scaled image request and returns a path for that request
	 *
	 * @param string $path The path to the image
	 * @param array $sizes An array of size specifications, where index 0 specifies the width and index 1 the height.
	 * @throws Exception if the sizes are not greater than zero
	 * @return boolean
	 */
	public function request($path, $width, $height)
	{
		if (!is_numeric($width) || $width < 1 || !is_numeric($height) || $height < 1) {
			throw new Exception(sprintf("Invalid image size (%d, %d)", $width, $height), 500);
		}
		
		$pathkey = $path . ':' . $width . ':' . $height;
		if (!isset(self::$requests[$pathkey])) {
			$db = Factory::getDbo();
			$date = date("Y-m-d H:i:s");
			
			$query = $db->getQuery(true);
			$path_hash = crc32($path);
			
			//Get the ID for this path
			if (!isset(self::$pathids[$path])) {
				//see if there is an existing path entry for this image
				$query->select($db->qn('id'))
					->from('#__scaler_path AS scp')
					->where($db->qn('path_hash') . '=' . $path_hash)
					->where($db->qn('path') . '=' . $db->q($path));
				
				$db->setQuery($query);
				$pathid = $db->loadResult('#__scaler_path');
						
				//if there is no existing path entry, create a new one
				if (!$pathid) {
					$query = $db->getQuery(true);
					$query->insert('#__scaler_path')
						->set($db->qn('path_hash') . '=' . $path_hash)
						->set($db->qn('path') . '=' . $db->q($path));
					$db->setQuery($query);
					$db->execute();
					$pathid = $db->insertid();
				}
				self::$pathids[$path] = $pathid;
			} else {
				$pathid = self::$pathids[$path];
			}
			
			$query = array();
			
			//Insert, unless there is a duplicate in which case just update the request date
			$query []= "INSERT INTO " . $db->qn('#__scaler_image');
			$query []= "( " . $db->qn('path_id') . ', ' . $db->qn('width') . ', ' . $db->qn('height') . ', ' . $db->qn('request_date') . ', ' . $db->qn('create_date') . " )";
			$query []= "VALUES ( " . $pathid . ', ' . $width . ', ' . $height . ', ' . $db->q($date) . ', ' . $db->q($date) . " )";
			$query []= "ON DUPLICATE KEY UPDATE " . $db->qn('request_date') . ' = VALUES( ' . $db->qn('request_date') . ' )';
					
			$db->setQuery(implode(' ', $query));
			$db->execute();
			
			$livedomain = $this->params->get('live_domain');
			$prefix = '';
			if ($livedomain) {
				$host = Uri::getInstance()->getHost();
				if ( $host == $livedomain || empty($host)) {
					$prefix = rtrim($this->params->get('cdn_prefix', ''), '/');
				}
			}
	
			require_once __DIR__ . '/../helpers/route.php';
			$link = Route::_(ScalerHelperRoute::getRoute() . '&path=' . urlencode($path) . '&width=' . $width . '&height=' . $height . '&id=' . $pathid); 
			self::$requests[$pathkey] = $prefix . $link;
		}
		return self::$requests[$pathkey];
	}


	/**
	 * Deletes all image resources related to a given original path
	 *
	 * @param array ids array of row IDs to delete
	 * @throws Exception if the current user is not authorised to edit this model
	 * @return boolean
	 */
	public function delete($path)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		
		$query->delete('#__scaler_image')->where($db->qn('path') . ' = ' . $db->q($path));
		$db->setQuery($query);
		$db->execute();
		
		return true;;
	}


	/**
	 * Returns the image data if the request has a match in the images table
	 * 
	 * @return object with parameters path, width, height, request_date, create_date and img (the data)
	 *
	 */
	public function getItem()
	{
		$width = $this->state->get('width');
		$height = $this->state->get('height');
		$id = $this->state->get('id');
		
		if (!$id || ! $width || ! $height) {
			throw new Exception("Invalid parameters", 400);
		}
		
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		
		$query->select($db->qn(
			array(
				'path_id',
				'width',
				'height',
				'request_date',
				'create_date',
				'scp.path'
			)
		))
			->from('#__scaler_image AS sci')
			->leftJoin('#__scaler_path AS scp ON scp.id = sci.path_id')
			->where($db->qn('path_id') . '=' . (int) $id)
			->where($db->qn('width') . '=' . (int) $width)
			->where($db->qn('height') . '=' . (int) $height);

		$db->setQuery($query);
		$data = $db->loadObject();
		
		if (!$data) {
			throw new Exception("Image not configured", 404);
		}
		
		$path = $data->path;
		
			
		//see if it's available
		if (!file_exists(JPATH_ROOT . '/' . $path)) {
			throw new Exception("Source image not found: " . $path, 404);
		}
		
		$imagick = new Imagick(JPATH_ROOT . '/' . $path);
		
		$imagick->cropthumbnailimage($width, $height);
		
		$format = $imagick->getimageformat();
		if ($format == 'JPEG' || $format == 'JPG') {
			$imagick->setimagecompressionquality($this->params->get('jpeg_quality', 70));
			$imagick->stripimage();
		}
		
		$data->img = $imagick->getImageBlob();
		return $data;
	}


	/**
	 * Build query and where for protected _getList function and return a list
	 *
	 * @return array An array of results.
	 */
	public function getItems()
	{
		//TODO return all items with a given value of path
	}

	
	/**
	 * Override sleep to specify which items to serialise. This is important when calling
	 * models using the object cache (JFactory::getCache()), to create unique keys based on
	 * the full state of the object
	 */
	function __sleep() {
		$this->_statedata = $this->state->toArray();
		$this->_paramsdata = $this->params->toArray();
		return array('_statedata', 	'_paramsdata', '_total', 'pagination', 'limitstart', 'limit', 'name', 'table', 'params');
	}
}
