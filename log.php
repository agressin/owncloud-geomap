<?php
//Lien : owncloud/public.php?service=geomap
// lat
// lon 
// timestamp
// hdop
// altitude
// speed
// id

OCP\JSON::checkAppEnabled('geomap');
require_once 'geomap/lib/database.php';

if(isset($_GET["lat"])){
	$lat = $_GET["lat"];
} else {
	$lat = 0;
}
//echo $lat;
if(isset($_GET["lon"])){
	$lon = $_GET["lon"];
} else {
	$lon = 0;
}
//echo $lon;
//miliseconde depuis 1er Janvier 1970
// $timestamp/100 -> date('d/m/Y', $timestamp).' a '.date('H:i:s', $timestamp)
if(isset($_GET["timestamp"])){
	$timestamp = $_GET["timestamp"];
} else {
	$timestamp = 0;
}

if(isset($_GET["hdop"])){
	$hdop = $_GET["hdop"];
} else {
	$hdop = 0;
}

if(isset($_GET["altitude"])){
	$altitude = $_GET["altitude"];
} else {
	$altitude = 0;
}

// m/s
// ./1000*60*60 -> km/h
if(isset($_GET["speed"])){
	$speed = $_GET["speed"];
} else {
	$speed = 0;
}

if(isset($_GET["id"])){
	$id = $_GET["id"];
} else {
	$id = "adrien";
}
echo addPoint($id,$timestamp,$lat,$lon,$hdop,$altitude,$speed);

?>
