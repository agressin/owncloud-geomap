<?php
$x = intval($_GET['x']);
$y = intval($_GET['y']);
$z = intval($_GET['z']);
$r = strip_tags($_GET['r']);
if(empty($r)) $r="mapnik";
$server = array();
switch ($r) {
    case 'mapnik':
        $server[] = 'a.tile.openstreetmap.org';
        $server[] = 'b.tile.openstreetmap.org';
        $server[] = 'c.tile.openstreetmap.org';

        $url = 'http://' . $server[array_rand($server)];
        $url .= "/" . $z . "/" . $x . "/" . $y . ".png";
        break;

    case 'osma':
    default:
        $server[] = 'a.tah.openstreetmap.org';
        $server[] = 'b.tah.openstreetmap.org';
        $server[] = 'c.tah.openstreetmap.org';

        $url = 'http://' . $server[array_rand($server)] . '/Tiles/tile.php';
        $url .= "/" . $z . "/" . $x . "/" . $y . ".png";
        break;
}

header("HTTP/1.1 301 Moved Permanently");
header("Location: $url");
exit();
?>