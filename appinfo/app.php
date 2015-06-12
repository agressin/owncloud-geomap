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

OCP\App::registerAdmin( 'geomap', 'settings' );

OCP\App::addNavigationEntry( array(
	'id' => 'geomap',
	'order' => 74,
	//'href' => OCP\Util::linkTo( 'geomap', 'index.php' ),
    'href' => OCP\Util::linkToRoute('geomap_index'),
	'icon' => OCP\Util::imagePath( 'geomap', 'geomap.png' ),
	'name' => 'GeoMap'
    //'name' => \OC_L10N::get('myapp')->t('My App')
));


