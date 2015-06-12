<?php
////////////////////////////////////////////////////////////////////////////////
// Projet :
// Auteur : A.Gressin
// Date   : 24/09/2012
// Description : 
////////////////////////////////////////////////////////////////////////////////

/******************************************************************************\
* getPointsByDate("2012_09_22")
* pour obtenir tous les points de la journée
\******************************************************************************/
function getPointsByDate($user_id,$date)
{

	$sql = "select item_id,uid_owner,timestamp,lat,lon,hdop,altitude,speed
				from `*PREFIX*geomap_items`
				where DATEDIFF(timestamp,?)=0 AND uid_owner=?";

	$query = OCP\DB::prepare($sql);

	$query->execute(array($date,$user_id));

	$out=$query->fetchAll();

	$query->closeCursor();
	
	return $out;
}
/******************************************************************************\
* getMapsByDate("2012_09_22")
* 
\******************************************************************************/
function getMapsByDate($id,$date)
{
	$pts = getPointsByDate($id,$date);

	$lat_mean= 0;
	$lat_min = $pts[0]["lat"];
	$lat_max = $pts[0]["lat"];
	$lon_mean= 0;
	$lon_min = $pts[0]["lon"];
	$lon_max = $pts[0]["lon"];
	
	$nb_pt=0;
	$str_map="";

	for($i = 0; $i < count($pts); $i++)
	{
		//$timestamp." ".$lat." ".$lon." ".$hdop." ".$altitude." ".$speed.
		$lat=$pts[$i]["lat"];
		$lon=$pts[$i]["lon"];
		
		$str_map.=$lon." ".$lat.",";
		
		$lat_mean+=$lat;
		$lat_max=max($lat_max,$lat);
		
		$lon_mean+=$lon;
		$lon_max=max($lon_max,$lon);
		
		$nb_pt++;
	}
	$str_map = substr($str_map,0,strlen($str_map)-1);

	$lat_mean/=$nb_pt;
	$lon_mean/=$nb_pt;
	
	$out = array(
			'lat' => $lat_mean,
			'lon' => $lon_mean,
			'nb_pt' => $nb_pt,
			'lat_max' => $lat_max,
			'lon_max' => $lon_max,
			'lat_min' => $lat_min,
			'lon_min' => $lon_min,
			'line' => $str_map
		);
	return $out;
}
/******************************************************************************\
* getPointsByDistance
* pour obtenir tous les points proche de (lat,lon)
\******************************************************************************/
/*
function getPointsByDistance($user_id,$lat,$lon,$dist)
{
	$bdd = connectDatabase();
	
	$req = $bdd->prepare('select id,id_user,time,X(pt) as lat,Y(pt) as lon,hdop,altitude,speed, (GLength( LineString(( PointFromWKB( POINT(?,?))), ( PointFromWKB( pt ) ))))*100 as distance from data having distance<? and id_user=? order by distance');
		
	$req->execute(array($lat,$lon,$dist,$user_id));
	
	$out=$req->fetchAll();

	$req->closeCursor();
	return $out;
}
* */
/******************************************************************************\
* getDates
* pour obtenir toutes les dates disponibles pour un utilisateur
\******************************************************************************/
function getDates($uid_owner)
{
	
	$sql = "select DATE_FORMAT(timestamp,'%Y_%c_%e') AS date
				from `*PREFIX*geomap_items`
				where uid_owner=?
				GROUP BY DATE_FORMAT(timestamp,'%Y_%c_%e')
				ORDER BY  timestamp DESC";

	$query = OCP\DB::prepare($sql);

	$query->execute(array($uid_owner));

	$out =  array();
	while ($donnees = $query->fetch())
		$out[]=$donnees["date"];

	//$query->closeCursor(); // Termine le traitement de la requête

	return $out;
}
/******************************************************************************\
* getLastDate
* pour obtenir la derniere date disponible pour un utilisateur
\******************************************************************************/
/*
function getLastDate($id_user)
{
	$bdd = connectDatabase();
	$req = $bdd->prepare("select DATE_FORMAT(time,'%Y_%c_%e') from data where id_user=? GROUP BY DATE_FORMAT(time,'%Y_%c_%e') ORDER BY  time DESC");
	$req->execute(array($id_user));
		// On affiche chaque entrée une à une

	$donnees = $req->fetch();
	$out=$donnees[0];

	$req->closeCursor(); // Termine le traitement de la requête
	return $out;
}
*/
/******************************************************************************\
* getName
* pour obtenir le nom de l'id user 
\******************************************************************************/
/*
function getName($id_user)
{
	$bdd = connectDatabase();
	$req = $bdd->prepare("select name from user where id=?");
	$req->execute(array($id_user));
		// On affiche chaque entrée une à une
	$out =  array();
	while ($donnees = $req->fetch())
		$out[]=$donnees[0];

	$req->closeCursor(); // Termine le traitement de la requête
	return $out;
}
*/
/******************************************************************************\
* getId
* pour obtenir l'id de l'user name
\******************************************************************************/
/*
function getId($user_name)
{
	$bdd = connectDatabase();
	$req = $bdd->prepare('select id from user where name=?');
	$req->execute(array($user_name));
	// On affiche chaque entrée une à une
	$out =  array();
	while ($donnees = $req->fetch())
		$out[]=$donnees["id"];

	$req->closeCursor(); // Termine le traitement de la requête
	return $out[0];
}
*/
/******************************************************************************\
* addPoint
\******************************************************************************/
function addPoint($user_id,$timestamp,$lat,$lon,$hdop,$altitude,$speed)
{
	
	$sql = "INSERT INTO `*PREFIX*geomap_items` (timestamp, lat, lon, altitude, hdop, speed, uid_owner)
				VALUES (FROM_UNIXTIME(?/1000), ?, ?, ?, ?, ?, ?)";
	$query = OCP\DB::prepare($sql);
	$query->execute(array($timestamp, $lat, $lon, $altitude, $hdop, $speed, $user_id));
	$id_pt = OCP\DB::insertid('*dbprefix*geomap_items');

	return $id_pt;
	
	//refresh_user_position($user_id,$donnees["id"]);
}
/******************************************************************************\
* refresh_user_position
* TODO :
	- calcul distance depuis point précédent
	- stocker quelque part ?
	- system d'alert ?
\******************************************************************************/
/*
function refresh_user_position($user_id,$id_last_position)
{
	$bdd = connectDatabase();
	$req = $bdd->prepare("update user set id_last_position=:id_last_position where id=:user_id ");
	
	$req->execute(array(
		'user_id' => $user_id,
		'id_last_position' => $id_last_position
	));
	$req->closeCursor();
}
*/
/******************************************************************************\
* get_user_last_position
* out  : array("lat","lon") 
\******************************************************************************/
/*
function get_user_last_position($user_id)
{
	$bdd = connectDatabase();
	$req = $bdd->prepare("select X(data.pt) as lat, Y(data.pt) as lon from data,user where user.id=:user_id and data.id=user.id_last_position");
	$req->execute(array('user_id' => $user_id));
	$donnees = $req->fetch();
	
	return $donnees;
}
*/
/******************************************************************************\
* get_all_users_last_position
* out  : array(array("id","name","lat","lon"))
* TODO : limiter aux users ayant partagé leurs position avec $user_id
\******************************************************************************/
/*
function get_all_users_last_position($user_id)
{
	$bdd = connectDatabase();
	
	$req = $bdd->query("select user.id, user.name, X(data.pt) as lat, Y(data.pt) as lon from data,user where data.id=user.id_last_position");
	
	// On affiche chaque entrée une à une
	$out=$req->fetchAll();

	$req->closeCursor();
	
	return $out;
}
*/
/******************************************************************************\
* createDatabase
\******************************************************************************/
/*
function createDatabase()
{
	$bdd = connectDatabase();
	//user : id,name, domicile (point), travail (point), id_last_position (lien avec data)
	$bdd->exec('CREATE TABLE user (
		id int not null primary key auto_increment,
		name varchar(10) not null default "", 
		home_position POINT,
		work_position POINT,
		id_last_position INT default -1, 
		created TIMESTAMP default now()
	)');
	
	//data : id,id_user(lien avec user),time, point (lat, lon), hdop, altitude, speed
	$bdd->exec('CREATE TABLE data (
		id int not null primary key auto_increment,
		id_user int not null, 
		time TIMESTAMP DEFAULT 0, 
		pt POINT,
		hdop FLOAT,
		altitude FLOAT,
		speed FLOAT,
		created TIMESTAMP default now()
	)');
}
*/
/******************************************************************************\
* install
\******************************************************************************/
/*
function install()
{
	$bdd = connectDatabase();
	createDatabase($bdd);
	addUser("Adrien","Point(0,0)","Point(0,0)",-1,"");
}
*/
/******************************************************************************\
* importDatafile
\******************************************************************************/
/*
function importDatafile($filename,$user_id)
{

	if (is_file($filename))
	{
		if ($tabFich = file($filename))
		{
			for($i = 0; $i < count($tabFich); $i++)
			{
				$temp = explode(" ", $tabFich[$i]);
				addPoint($user_id,$temp[0],$temp[1],$temp[2],$temp[3],$temp[4],$temp[5]);
			}
		}
		else
			echo "Le fichier ne peut etre lu...<br>";
	}
}
*/
/*
importDatafile("log/2012_09_08_log_adrien.txt",1);
importDatafile("log/2012_09_10_log_adrien.txt",1);
importDatafile("log/2012_09_16_log_adrien.txt",1);
importDatafile("log/2012_09_21_log_adrien.txt",1);
importDatafile("log/2012_09_24_log_adrien.txt",1);
importDatafile("log/2012_09_14_log_adrien.txt",1);
importDatafile("log/2012_09_20_log_adrien.txt",1);
importDatafile("log/2012_09_22_log_adrien.txt",1);
*/

//echo var_dump(getPointsByDistance(1,"48.84491","2.4244459",1000));

?>
