<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('geomap');

require_once 'geomap/lib/geomap.php';

$list= \OCA_geomap\Storage::getGPXs();

OCP\JSON::encodedPrint($list);