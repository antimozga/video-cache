<?php

error_reporting(E_ERROR | E_PARSE);

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

setcookie ('PHPSESSID', $_COOKIE['PHPSESSID'], time() + 60 * 60 * 24 * 7, '/');
session_start();

if (isset($_SESSION['ajsner'])) {
    if ($_SESSION['ajsner'] > 100) {
	header($_SERVER["SERVER_PROTOCOL"]." 503 Service Temporarily Unavailable", true, 503);
	$retryAfterSeconds = 240;
	header('Retry-After: ' . $retryAfterSeconds);
	echo '<h1>503 Service Temporarily Unavailable</h1>';
	exit;
    }
}

$time_now = microtime_float();

$time_diff = $time_now - $_SESSION['lkasdas'];

//error_log("access to ".$_SERVER['HTTP_HOST']." ".$_SERVER['REQUEST_URI']." ".$_COOKIE['PHPSESSID']." ".$_SESSION['lkasdas']." ".$time_diff." ".$_SESSION['ajsner']);
//error_log("time diff ".$time_diff);

$_SESSION['lkasdas'] = $time_now;

if ($time_diff < 1) {
    if (!isset($_SESSION['ajsner'])) {
	$_SESSION['ajsner'] = 1;
    } else {
	$_SESSION['ajsner']++;
    }

    if ($_SESSION['ajsner'] > 1) {
	header($_SERVER["SERVER_PROTOCOL"]." 503 Service Temporarily Unavailable", true, 503);
	$retryAfterSeconds = 240;
	header('Retry-After: ' . $retryAfterSeconds);
	echo '<h1>503 Service Temporarily Unavailable</h1>';
	exit;
    }
} else {
	$_SESSION['ajsner'] = 0;
}

include_once('config.php');

function get_source($hash, $type)
{
    global $CACHE_DIR;

    if ($type == "video/mp4") {
	return '<source src="https://video.vtomske.net/'.$CACHE_DIR.'/'.$hash.'.mp4" type="'.$type.'">';
    }

    return NULL;
}

$database = new PDO('sqlite:'.DBASEFILE);

if (!$database) {
    print('<b>Ошибка базы данных.</b>');
    exit(0);
}

$query = "CREATE TABLE IF NOT EXISTS VideoCache " .
	 "(id INTEGER PRIMARY KEY, hash NVARCHAR, type NVARCHAR, last_time INTEGER, time INTEGER);";
$database->exec($query);

$link = 'https://vk.com/video-60130670_456254951';

$hash = md5($link);

$infos = $database->query("SELECT hash, type FROM VideoCache WHERE hash = \"$hash\"");

foreach($infos as $info) {
    $path = $info['hash'];
    $type = $info['type'];

    if ($path) {
	$time = time();
	$database->exec("UPDATE VideoCache SET last_time = $time WHERE hash = \"$hash\"");

	echo get_source($path, $type);
	exit;
    }
}

$url = str_replace('https://vk.com', 'https://m.vk.com', $link);

//printf("%s\n", $url);

$text = file_get_contents($url);

//echo $text;

$dom = new DomDocument();
$dom->loadHTML($text);
$elements = $dom->getElementsByTagName('video');

if (!is_null($elements)) {
    foreach ($elements as $element) {
	$nodes = $element->childNodes;
	foreach ($nodes as $node) {
	    if ($node->nodeName == "source") {
		if ($node->getAttribute('type') == "video/mp4") {
//		    echo $node->getAttribute('src');
		    $vsrc = $node->getAttribute('src');
		    system('wget -q "'.$vsrc.'" -O '.$CACHE_DIR.'/'.$hash.'.mp4');
		    $time = time();
		    $type = $node->getAttribute('type');
		    $database->exec("INSERT INTO VideoCache (hash, type, last_time, time) VALUES('$hash', '$type', '$time', '$time')");
		    echo get_source($hash, $type);
		}
	    }
	}
    }
}

?>
