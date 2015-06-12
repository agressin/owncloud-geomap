<?php


OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('geomap');
require_once 'geomap/lib/database.php';


$id = isset($_GET['id'])?$_GET['id']:'';
$date = isset($_GET['date'])?$_GET['date']:'';

if($id && $date)
{

	$map = getMapsByDate($id,$date);

} else
{
	bailOut($l->t('id and date are missing.'));
}

OCP\JSON::success(array('data' => json_encode($map)));
