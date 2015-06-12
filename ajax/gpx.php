<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('geomap');
require_once 'geomap/lib/GPX.php';


$url = isset($_GET['url'])?$_GET['url']:'';

if($url) {
	$filename = OCP\Util::sanitizeHTML($url);	
	$data = \OC\Files\Filesystem::file_get_contents( $filename );
	// Out : maps, graph, waypoints 
	$tab = parseGPXFromXml($data);

} else {
	bailOut($l->t('url is missing.'));
}

OCP\JSON::success(array('data' => array('gpxJSON'=>json_encode($tab))));
