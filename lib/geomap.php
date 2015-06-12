<?php
/**
 * ownCloud - my App
 *
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
 * You should have received a copy of the GNU Lesser General Public 
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 * 
 */

namespace OCA_geomap;

class Storage {

	public static function getGPXs() {
		$files=array();
		$list=\OC\Files\Filesystem::search('.gpx');
		//$list=\OC\Files\Filesystem::searchByMime('text/gpx');
		
		foreach($list as $l)
		{
			$size=\OC\Files\Filesystem::filesize($l['path']);
			if($size > 0)
			{
				$info=pathinfo($l['path']);
			
				$entry=array('url'=>$l['path'],'name'=>$info['filename'],'size'=>$size,'mtime'=>$mtime);
			
				$files[]=$entry;
			}
		}
	
		return $files;
	}

    public static function getJPGs() {
		$files=array();
		//$list=\OC\Files\Filesystem::search('.jpg');
		$list=\OC\Files\Filesystem::searchByMime('image');
		
		foreach($list as $l)
		{
			$size=\OC\Files\Filesystem::filesize($l['path']);
			if($size > 0)
			{
                $exif = exif_read_data(\OC\Files\Filesystem::getLocalFile($l['path']),"EXIF");
                if(array_key_exists("GPSLongitude",$exif))
                {
                    $lon = \OCA_geomap\Storage::getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
                    $lat = \OCA_geomap\Storage::getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
        			$info=pathinfo($l['path']);
        			$entry=array(   'url'=>$l['path'],
                                    'name'=>$info['filename'],
                                    'size'=>$size,
                                    'mtime'=>$mtime,
                                    'lon'=>$lon,
                                    'lat'=>$lat);
        		
        			$files[]=$entry;
                }

			}
		}
	
		return $files;
	}
    
    public static function getGps($exifCoord, $hemi) {

        $degrees = count($exifCoord) > 0 ? \OCA_geomap\Storage::gps2Num($exifCoord[0]) : 0;
        $minutes = count($exifCoord) > 1 ? \OCA_geomap\Storage::gps2Num($exifCoord[1]) : 0;
        $seconds = count($exifCoord) > 2 ? \OCA_geomap\Storage::gps2Num($exifCoord[2]) : 0;
    
        $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;
    
        return $flip * ($degrees + $minutes / 60 + $seconds / 3600);

    }

    public static function gps2Num($coordPart) {
    
        $parts = explode('/', $coordPart);
    
        if (count($parts) <= 0)
            return 0;
    
        if (count($parts) == 1)
            return $parts[0];
    
        return floatval($parts[0]) / floatval($parts[1]);
    }

}
