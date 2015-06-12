<?php

#$this->create('index', 'index.php')->actionInclude('geomap/index.php');
$this->create('geomap_index', '/')->actionInclude('geomap/index.php');
$this->create('gpx', 'ajax/gpx.php')->actionInclude('geomap/ajax/gpx.php');
$this->create('listDates', 'ajax/listDates.php')->actionInclude('geomap/ajax/listDates.php');
$this->create('listFiles', 'ajax/listFiles.php')->actionInclude('geomap/ajax/listFiles.php');
$this->create('listImages', 'ajax/listImages.php')->actionInclude('geomap/ajax/listImages.php');
$this->create('position', 'ajax/position.php')->actionInclude('geomap/ajax/position.php');
$this->create('setapikey', 'ajax/setapikey.php')->actionInclude('geomap/ajax/setapikey.php');
$this->create('getapikey', 'js/load.php')->actionInclude('geomap/js/load.php');
?>
