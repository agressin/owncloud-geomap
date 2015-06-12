<?php

/******************************************************************************/
// toRadians
/******************************************************************************/
function toRadians($degrees)
{
	return (float)($degrees * 3.1415926535897932385 / 180);
}
/******************************************************************************/
// date_getDecimals
/******************************************************************************/
function date_getDecimals($date)
{
	if (preg_match('(\.([0-9]{2})Z?)', $date, $matches))
	{
		return (float)((float)$matches[1] / 100);
	}
	else
	{
		return 0;
	}
}
/******************************************************************************/
// my_date_diff
// en seconde ?
/******************************************************************************/
function my_date_diff($old_date, $new_date) {

	$t1 = strtotime($new_date);
	$t2 = strtotime($old_date);
	
	// milliceconds fix
	$t1 += date_getDecimals($new_date);
	$t2 += date_getDecimals($old_date);

	$offset = (float)($t1 - $t2);
  
	//echo "$offset = $new_date - $old_date; ".strtotime($new_date)." ".strtotime($old_date)." <br />";
  
  return $offset;
}
/******************************************************************************/
// calculateDistance
/******************************************************************************/
function calculateDistance($lat1,$lon1,$ele1,$lat2,$lon2,$ele2)
{
	$alpha = (float)sin((float)toRadians((float) $lat2 - (float) $lat1) / 2);
	$beta = (float)sin((float)toRadians((float) $lon2 - (float) $lon1) / 2);
	//Distance in meters
	$a = (float) ( (float)$alpha * (float)$alpha) +  (float) ( (float)cos( (float)toRadians($lat1)) * (float)cos( (float)toRadians($lat2)) * (float)$beta * (float)$beta );
	$dist = 2 * 6369628.75 * (float)atan2((float)sqrt((float)$a), (float)sqrt(1 - (float) $a));
	$d = (float)sqrt((float)pow((float)$dist, 2) + pow((float) $lat1 - (float)$lat2, 2));	
	return sqrt((float)pow((float)$ele1-(float)$ele2,2)+(float)pow((float)$d,2));
}
/******************************************************************************/
// parseXml
// In :
//	- filePath
//	- gpxOffset
// Out :
//  - points : la liste  des points lat[],lon[],ele[],dist[],speed[],hr[],cad[]
/******************************************************************************/
function parseXml($filePath, $gpxOffset)
{
	$gpx = simplexml_load_file($filePath);
	return parseXmlFromXml($gpx,$gpxOffset);
}
/******************************************************************************/
function parseXmlFromXml($xml, $gpxOffset)
{
	$points = null;
	
	$points->lat = array();
	$points->lon = array();
	$points->ele = array();
	//$points->time = array();
	$points->dist = array();
	$points->speed = array();
	$points->hr = array();
	$points->cad = array();
	
	$points->distTotal=0;
	$points->eleTotalPos=0;
	$points->eleTotalNeg=0;
	//$points->timeTotal=0;
	
	$gpx = new SimpleXMLElement($xml);
	
	if($gpx === FALSE)
		return;
	
	$gpx->registerXPathNamespace('10', 'http://www.topografix.com/GPX/1/0'); 
	$gpx->registerXPathNamespace('11', 'http://www.topografix.com/GPX/1/1'); 
	$gpx->registerXPathNamespace('gpxx', 'http://www.garmin.com/xmlschemas/GpxExtensions/v3'); 		
	$gpx->registerXPathNamespace('gpxtpx', 'http://www.garmin.com/xmlschemas/TrackPointExtension/v1'); 
	
	$nodes = $gpx->xpath('//trkpt | //10:trkpt | //11:trkpt | //11:rtept');
	
	if ( count($nodes) > 0 )	
	{
		$lastLat = 0;
		$lastLon = 0;
		$lastEle = 0;
		$lastTime = 0;
		$firstTime = 0;
		$dist = 0;
		$elePos = 0;
		$eleNeg = 0;
		$lastOffset = 0;
		$speedBuffer = array();
	
		// normal case
		foreach($nodes as $trkpt)
		{
			$lat = $trkpt['lat'];
			$lon = $trkpt['lon'];
			$ele = $trkpt->ele;
			$time = $trkpt->time;
			$speed = (float)$trkpt->speed;
			$hr = 0;
			$cad = 0;
			
			if (isset($trkpt->extensions))
			{				
				$_hr = @$trkpt->extensions->xpath('gpxtpx:TrackPointExtension/gpxtpx:hr/text()');
				if ($_hr)
				{
					foreach ($_hr as $node) {
						$hr = (float)$node;
					}
				}
				
				$_cad = @$trkpt->extensions->xpath('gpxtpx:TrackPointExtension/gpxtpx:cad/text()');
				if ($_cad)
				{
					foreach ($_cad as $node) {
						$cad = (float)$node;
					}
				}
			}

			if ($lastLat == 0 && $lastLon == 0)
			{
				//Base Case
				
				array_push($points->lat,  (float)$lat);
				array_push($points->lon,  (float)$lon);
				array_push($points->ele,  (float)round($ele,2));
				array_push($points->dist, (float)round($dist,2));
				array_push($points->speed, 0);
				array_push($points->hr, $hr);
				array_push($points->cad, $cad);
				
				$lastLat=$lat;
				$lastLon=$lon;
				$lastEle=$ele;				
				$lastTime=$time;
				$firstTime=$time;
			}
			else
			{
				//Normal Case
				$offset = calculateDistance((float)$lat, (float)$lon, (float)$ele, (float)$lastLat, (float)$lastLon, (float)$lastEle);
				$dist = $dist + $offset;

				$offsetEle =  $ele - $lastEle;
				if( $offsetEle > 0 )
					$elePos = $elePos + $offsetEle;
				else
					$eleNeg = $eleNeg - $offsetEle;

				
				if ($speed == 0)
				{
					$datediff = (float)my_date_diff($lastTime,$time);
					if ($datediff>0)
					{
						$speed = $offset / $datediff;
					}
				}
				
				array_push($speedBuffer, $speed);
				
				if (((float) $offset + (float) $lastOffset) > $gpxOffset)
				{
					//Bigger Offset -> write coordinate
					$avgSpeed = 0;
					
					foreach($speedBuffer as $s)
					{ 
						$avgSpeed += $s;
					}
					
					$avgSpeed = $avgSpeed / count($speedBuffer);
					$speedBuffer = array();
					
					$lastOffset=0;
					
					array_push($points->lat,   (float)$lat );
					array_push($points->lon,   (float)$lon );
					array_push($points->ele,   (float)round($ele, 2) );
					array_push($points->dist,  (float)round($dist, 2) );
					array_push($points->speed, (float)round($avgSpeed, 1) );
					array_push($points->hr, $hr);
					array_push($points->cad, $cad);
					
				}
				else
				{
					//Smoller Offset -> continue..
					$lastOffset = (float) $lastOffset + (float) $offset ;
				}
			}
			$lastLat=$lat;
			$lastLon=$lon;
			$lastEle=$ele;
			$lastTime=$time;	
		}
		unset($nodes);
		
		$points->distTotal = $dist;
		$points->eleTotalPos = $elePos;
		$points->eleTotalNeg = $eleNeg;
		$points->timeTotal = (float)my_date_diff($firstTime,$lastTime);
	}
	else
	{
	
		$nodes = $gpx->xpath('//gpxx:rpt');
	
		if ( count($nodes) > 0 )	
		{
		
			$lastLat = 0;
			$lastLon = 0;
			$lastEle = 0;
			$dist = 0;
			$lastOffset = 0;
		
			// Garmin case
			foreach($nodes as $rpt)
			{ 
			
				$lat = $rpt['lat'];
				$lon = $rpt['lon'];
				if ($lastLat == 0 && $lastLon == 0)
				{
					//Base Case
					array_push($points->lat,   (float)$lat );
					array_push($points->lon,   (float)$lon );
					array_push($points->ele,   0 );
					array_push($points->dist,  0 );
					array_push($points->speed, 0 );
					array_push($points->hr,    0 );
					array_push($points->cad,   0 );
					$lastLat=$lat;
					$lastLon=$lon;
				}
				else
				{
					//Normal Case
					$offset = calculateDistance($lat, $lon, 0,$lastLat, $lastLon, 0);
					$dist = $dist + $offset;
					if (((float) $offset + (float) $lastOffset) > $gpxOffset)
					{
						//Bigger Offset -> write coordinate
						$lastOffset=0;
						array_push($points->lat,   (float)$lat );
						array_push($points->lon,   (float)$lon );
						array_push($points->ele,   0 );
						array_push($points->dist,  0 );
						array_push($points->speed, 0 );	
						array_push($points->hr,    0 );
						array_push($points->cad,   0 );
					}
					else
					{
						//Smaller Offset -> continue..
						$lastOffset= (float) $lastOffset + (float) $offset;
					}
				}
				$lastLat=$lat;
				$lastLon=$lon;
			}
			unset($nodes);
		}
		else
		{	
			echo "Empty Gpx or not supported File!";
		}
	}
	//unset($gpx);
	return $points;
}	
/******************************************************************************/
// getPoints
// In :
// - gpxPath
// - gpxOffset
// - donotreducegpx : pour réduire le nombre de points à 200
// Out :
// - points la liste des points
// old : [lat,lon,ele,time,speed]
// new : lat[],lon[],ele[],dist[],speed[],hr[],cad[]
/******************************************************************************/
function getPoints($gpxPath,$gpxOffset = 10, $donotreducegpx = false)
{

	if (file_exists($gpxPath))
	{
		$points = parseXml($gpxPath, $gpxOffset);
	
		// reduce the points to around 200 to speedup
		if ( ! $donotreducegpx )
		{
			$points = reducePoints($points);
		}
		return $points;	
	}
	else
	{
		echo "File $gpxPath not found!";
		return array();
	}
}
/******************************************************************************/
function getPointsFromXml($xml,$gpxOffset = 10, $donotreducegpx = false)
{
	//$gpx = new SimpleXMLElement($xml);
	
	$points = parseXmlFromXml($xml, $gpxOffset);
	
	// reduce the points to around 200 to speedup
	if ( ! $donotreducegpx )
	{
		$points = reducePoints($points);
	}
	return $points;
}
/******************************************************************************/
function reducePoints($points)
{
	$points_temp = null;
	
	$points_temp->lat = array();
	$points_temp->lon = array();
	$points_temp->ele = array();
	$points_temp->dist = array();
	$points_temp->speed = array();
	$points_temp->hr = array();
	$points_temp->cad = array();
	
	$points_temp->distTotal   = $points->distTotal;
	$points_temp->eleTotalPos = $points->eleTotalPos;
	$points_temp->eleTotalNeg = $points->eleTotalNeg;
	$points_temp->timeTotal   = $points->timeTotal;
		
	$count=sizeof($points->lat);
	if ($count>200)
	{
		$f = round($count/200);
		if ($f>1)
		{
			for($i=1;$i<$count;$i++)
			{
				if ($i % $f == 0)
				{
						array_push($points_temp->lat,$points->lat[$i]);
						array_push($points_temp->lon,$points->lon[$i]);
						array_push($points_temp->ele,$points->ele[$i]);
						array_push($points_temp->dist,$points->dist[$i]);
						array_push($points_temp->speed,$points->speed[$i]);	
						array_push($points_temp->hr,$points->hr[$i]);
						array_push($points_temp->cad,$points->cad[$i]);
				}
			}
			$points = $points_temp;
		}
	}
	return $points;
}
/******************************************************************************/
// getWayPoints
// In :
// - gpxPath
// Out :
// - points la liste des points [lat,lon,ele,name,desc,sym,type]
/******************************************************************************/	
function getWayPoints($gpxPath)
{

	if (file_exists($gpxPath))
	{
		$gpx = simplexml_load_file($gpxPath);
		return getWayPointsFromXml($gpxPath, $gpxOffset);			
	}
	else
	{
		echo "File $gpxPath not found!";
		return array();
	}
}
function getWayPointsFromXml($xml)
{
	$points = array();
	//$gpx = simplexml_load_file($gpxPath);	
	//$gpx = new SimpleXMLElement($xml);
	$gpx->registerXPathNamespace('10', 'http://www.topografix.com/GPX/1/0'); 
	$gpx->registerXPathNamespace('11', 'http://www.topografix.com/GPX/1/1'); 
	$nodes = $gpx->xpath('//wpt | //10:wpt | //11:wpt');
	
	if ( count($nodes) > 0 )	
	{
		// normal case
		foreach($nodes as $wpt)
		{
			$lat = $wpt['lat'];
			$lon = $wpt['lon'];
			$ele = $wpt->ele;
			$time = $wpt->time;
			$name = $wpt->name;
			$desc = $wpt->desc;
			$sym = $wpt->sym;
			$type = $wpt->type;
			array_push($points, array((float)$lat,(float)$lon,(float)$ele,$time,$name,$desc,$sym,$type));
		}
	}
	return $points;
}
/******************************************************************************\
* parseGPX
In :
	$gpx : url du fichier gpx
	$showSpeed : récupère ou non la vitesse 
	$uomspeed : unité de la vitesse default : m/s;  1 : km/h
	$showW : récupère ou non les Waypoints 
Out :
	$maps : tableau des pts [lat,lon],...
	$graph : tableaux : dist[],elev[],speed[],hr[],cad[]
	$waypoints : tableau [lat,lon,...],..
/******************************************************************************/
function parseGPX($gpx,$donotreducegpx) {
/*
	$sitePath = sitePath();
	$gpx = trim($gpx);

	if (strpos($gpx, "http://") !== 0)
	{
		$gpx = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $gpx);
		$gpx = $sitePath . $gpx;
	}
	else
	{
		$gpx = downloadRemoteFile($gpx);
	}

	if ($gpx == '')
	{
		return "No gpx found";
	}
*/
	$points = getPoints( $gpx, $pointsoffset, $donotreducegpx);
	if ($showW == true)
	{
		//$lat,$lon,$ele,$time,$name,$desc,$sym,$type
		$wpoints = getWayPoints($gpx);
	}
	else
		$wpoints = array();
	
	return pointsTabToJSON($points,$wpoints);
}
/******************************************************************************/
function parseGPXFromXml($xml,$donotreducegpx) {

	//$gpx = new SimpleXMLElement($xml);

	$points = getPointsFromXml( $xml, $pointsoffset, $donotreducegpx);
	if ($showW == true)
	{
		//$lat,$lon,$ele,$time,$name,$desc,$sym,$type
		$wpoints = getWayPointsFromXml($xml);
	}
	else
		$wpoints = array();
	
	return pointsTabToJSON($points,$wpoints);
}
/******************************************************************************/
function pointsTabToJSON($points,$wpoints)
{
	$points_maps = 'LINESTRING (';
	
	$waypoints = '';
	$data_graph = null;
	$data_graph->dist = array();
	$data_graph->ele = array();
	$data_graph->speed = array();
	$data_graph->hr = array();
	$data_graph->cad = array();
	
	$hr_max =0;
	$cad_max=0;
	$speed_max=0;
	$ele_max=0;
	
	$count=sizeof($points->lat);
	for($i=1;$i<$count;$i++)
	{
		// lat, lon
		$points_maps .= (float)$points->lon[$i].' '.(float)$points->lat[$i].',';

		
		$_ele = $points->ele[$i];
		if($_ele>$ele_max)
			$ele_max=$_ele;
		array_push($data_graph->ele,(float)$_ele);
		$_hr = $points->hr[$i];
		if($_hr>$hr_max)
			$hr_max=$_hr;
		array_push($data_graph->hr,(float)$_hr);
		$_cad = $points->cad[$i];
		if($_cad>$hr_cad)
			$hr_cad=$_cad;
		array_push($data_graph->cad,(float)$_cad);
		
		//dist
		$_dist = $points->dist[$i];
		array_push($data_graph->dist,(float)$_dist);
	
		//speed
		$_speed = $points->speed[$i]; // default m/s
		if($_speed>$speed_max)
			$speed_max=$_speed;
		if ($uomspeed == '1') // km/h
		{
			$_speed *= 3.6;
		}
		array_push($data_graph->speed,(float)$_speed);
	}
	
	if($hr_max == 0)
		$data_graph->hr = array();
	if($cad_max == 0)
		$data_graph->cad = array();
	if($speed_max == 0)
		$data_graph->speed = array();
	if($ele_max == 0)
		$data_graph->ele = array();
	
	if ($showW == true)
	{
		foreach ($wpoints as $p) {
			$waypoints .=
					'{
						lat : '.(float)$p[0].',
						lon : '.(float)$p[1].',
						name : "'.urldecode($p[4]).'",
						desc : "'.urldecode($p[5]).'",
						type : "'.urldecode($p[7]).'"
					},';
		}
	}

	$p="/,$/";
	$points_maps = preg_replace($p, "", $points_maps);
	$points_maps.=')';
		
	$waypoints = preg_replace($p, "", $waypoints);
	
	$info  = array();
	$info['distTotal']   = $points->distTotal;
	$info['eleTotalPos'] = $points->eleTotalPos;
	$info['eleTotalNeg'] = $points->eleTotalNeg;
	$info['timeTotal']   =  date(' H:i:s', $points->timeTotal);
	
	$out['maps']=$points_maps;
	$out['graph']=$data_graph;
	$out['waypoints']=$waypoints;
	$out['info']=$info;
	return $out;
}
?>
