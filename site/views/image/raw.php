<?php
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

/********************************
 *
 * (c) 2016 Netriver Systems Limited
 *
 ********************************/
 
// no direct access
defined ( '_JEXEC' ) or die ( 'Restricted access' );

/**
 * View for displaing scaled images.
 * 
 * @author david
 *
 */
class ScalerViewImageRaw extends JViewBase {
	function render() {
		$app = Factory::getApplication ();
		$doc = Factory::getDocument ();
		$params = ComponentHelper::getParams('com_scaler');
		
		//set up caching. Defaults to enabled with a lifetime of 24 hours
		$cache = Factory::getCache('com_scaler');
		$cache->setCaching($params->get('cache_enabled', true));
		$cache->setLifeTime($params->get('cache_lifetime', 60*60*24));
		
		//get the image data
		$this->model->__sleep(); //kludge to fix the bug in Joomla
		try {
			$item = $cache->call(array($this->model, 'getItem'));
		} catch (Exception $e) {
			//if there is a 404 (not found) error, do something sensible or
			//Joomla tries to render an error page into a JDocumentRaw object and
			//creates errors in the log
			if ($e->getCode() == 404) {
				$app->setHeader('status', 404, true);
				echo $e->getMessage();
				return;
			} else {
				throw $e;
			}
				
		}
		
		//work out the mime type
		$pathparts = pathinfo($item->path);
		$extension = strtolower($pathparts['extension']);
		if ($extension == "jpg" || $extension == "jpeg") {
			$doc->setMimeEncoding ( 'image/jpeg' );
		} elseif ($extension == "png") {
			$doc->setMimeEncoding('image/png');
		} else {
			throw new Exception("File type not understood", 500);
		}
		
		//This is actually the created date but there's not much we can do about that
		//except delete rows from #__scaler_image when an image is updated
		$date = DateTime::createFromFormat("Y-m-d H:i:s", $item->create_date);
		$modified = $date->format('D, d M Y H:i:s \G\M\T');
		
		$doc->setModifiedDate ( $modified );

		$app->setHeader ( 'Last-Modified', $modified, true );
		$app->setHeader ( 'Cache-Control', 'max-age=43200', true );
		$app->setHeader ( 'Expires', gmdate ( 'D, d M Y H:i:s', time () + 43200 ) . ' GMT', true );
		$app->allowCache ( true );

		$headers = apache_request_headers ();
		if (isset ( $headers ['If-Modified-Since'] ) && ($headers ['If-Modified-Since'] == $modified)) {
			// Client's cache IS current, so we just respond '304 Not Modified'.
			$app->setHeader ( 'HTTP/1.1 304 Not Modified', true );
			return;
		}

		// output the scaled image
		echo $item->img;
	}
}