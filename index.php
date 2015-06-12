<?php

/**
* ownCloud - geomap
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/


require_once 'lib/geomap.php';

// Check if we are a user
OCP\User::checkLoggedIn();
OCP\JSON::checkAppEnabled('geomap');
OCP\App::setActiveNavigationEntry( 'geomap' );


$tmpl = new OCP\Template('geomap', 'main', 'user');


OCP\Util::addStyle('geomap', 'main');

script('geomap', 'geomap');


script('geomap', array(
        'openlayers/OpenLayers',
        'flot/jquery.flot.min',
        'flot/jquery.flot.crosshair.min'
));

/*

OCP\Util::addScript('geomap/3rdparty', 'flot/jquery.flot.min');
OCP\Util::addScript('geomap/3rdparty', 'flot/jquery.flot.crosshair.min');
*/

$tmpl->printPage();
