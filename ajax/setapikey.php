<?php
/**
 * Copyright (c) 2011, Frank Karlitschek <karlitschek@kde.org>
 * Copyright (c) 2012, Florian Hülsmann <fh@cbix.de>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCP\User::checkAdminUser();
OCP\JSON::callCheck();

$out="";

if(isset($_POST['apiKey']))
{
	OCP\Config::setSystemValue( 'apiKey', $_POST['apiKey'] );
	$out .=" apiKey. ";
}
if(isset($_POST['apiVersion']))
{
	OCP\Config::setSystemValue( 'apiVersion', $_POST['apiVersion'] );
	$out .=" apiVersion. ";
}
echo $out;
