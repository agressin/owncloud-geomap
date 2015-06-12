<?php

OCP\User::checkAdminUser();

OCP\Util::addScript( "geomap", "admin" );

$tmpl = new OCP\Template( 'geomap', 'settings');

$tmpl->assign('apiKey', OCP\Config::getSystemValue( "apiKey", '' ));
$tmpl->assign('apiVersion', OCP\Config::getSystemValue( "apiVersion", '' ));

return $tmpl->fetchPage();
