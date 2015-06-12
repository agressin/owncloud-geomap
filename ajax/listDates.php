<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('geomap');

require_once 'geomap/lib/database.php';

$uid_owner = OCP\User::getUser();
$dates = getDates($uid_owner);

OCP\JSON::encodedPrint(array('dates' => $dates, 'uid' => $uid_owner));