<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('geomap');

require_once 'geomap/lib/geomap.php';

$list= \OCA_geomap\Storage::getJPGs();

OCP\JSON::encodedPrint($list);