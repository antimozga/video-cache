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

    if ($_SESSION['ajsner'] > 35) {
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

function get_source($hash, $type, $width, $height)
{
    global $CACHE_DIR;
    global $SERVER_URL;
    global $link;

    $size = "";

    if ($width > 0) {
	$size = "width=\"$width\"";
    }

    if ($height > 0) {
	$size = "$size height=\"$height\"";
    }

    echo '<html><body style="margin: 0; border: 0; position: relative; overflow: hidden">';

    if ($type == "video/mp4") {
//	echo '<video class="videocache" '.$size.' poster="'.$SERVER_URL.'/testcard.png" controls><source src="'.$SERVER_URL.'/'.$CACHE_DIR.'/'.$hash.'.mp4" type="'.$type.'"></video>';
	echo '<video class="videocache" '.$size.' title="'.$link.'" controls><source src="'.$SERVER_URL.'/'.$CACHE_DIR.'/'.$hash.'.mp4" type="'.$type.'"></video>';
	echo '<a style="position: absolute; top: 0; left: 0; z-index: 1; font-size: smaller;" target="_blank" href="'.$link.'">'.$link.'</a>';
    } else {
	echo '<video class="videocache" '.$size.' title="'.$link.'" controls><source src="'.$SERVER_URL.'/novideo.mp4" type="video/mp4"></video>';
    }

    echo '</body></html>';
}

function show_error($width, $height)
{
    global $SERVER_URL;
    global $link;

    $size = "";

    if ($width > 0) {
	$size = "width=\"$width\"";
    }

    if ($height > 0) {
	$size = "$size height=\"$height\"";
    }

    echo '<html><body style="margin: 0; border: 0">';

    echo '<video class="videocache" '.$size.' title="'.$link.'" controls><source src="'.$SERVER_URL.'/novideo.mp4" type="video/mp4"></video>';

    echo '</body></html>';
}

function download_video($vsrc, $hash, $type, $time, $width, $height)
{
    global $database;
    global $CACHE_DIR;

    $database->exec("INSERT INTO VideoCache (hash, type, last_time, time) VALUES('$hash', '$type', '$time', '$time')");
    system('wget -q "'.$vsrc.'" -O '.$CACHE_DIR.'/'.$hash.'.mp4', $retval);
    if ($retval == 0) {
	get_source($hash, $type, $width, $height);
    } else {
	$database->exec("DELETE FROM VideoCache WHERE hash = \"$hash\"");
	show_error($width, $height);
    }
}

$database = new PDO('sqlite:'.DBASEFILE);

if (!$database) {
    print('<b>Ошибка базы данных.</b>');
    exit(0);
}

$query = "CREATE TABLE IF NOT EXISTS VideoCache " .
	 "(id INTEGER PRIMARY KEY, hash NVARCHAR, type NVARCHAR, last_time INTEGER, time INTEGER);";
$database->exec($query);

$video_width  = 0;
$video_height = 0;

if (!isset($_REQUEST['url'])) {
    show_error($video_width, $video_height);
    exit;
}

if (isset($_REQUEST['w'])) {
    $video_width = $_REQUEST['w'];
    $video_width = ($video_width * 10) / 10;
}

if (isset($_REQUEST['h'])) {
    $video_height = $_REQUEST['h'];
    $video_height = ($video_height * 10) / 10;
}

$link = addslashes($_REQUEST['url']);

//$link = 'https://vk.com/video-60130670_456254951';
//$link = 'https://www.tiktok.com/@kimtaerini/video/6796843229443542273';

$hash = md5($link);

$infos = $database->query("SELECT hash, type FROM VideoCache WHERE hash = \"$hash\"");

foreach($infos as $info) {
    $path = $info['hash'];
    $type = $info['type'];

    if ($path) {
	$time = time();
	$database->exec("UPDATE VideoCache SET last_time = $time WHERE hash = \"$hash\"");

	get_source($path, $type, $video_width, $video_height);
	exit;
    }
}

define ('VK_PREFIX',  'https://vk.com/video');
define ('VK1_PREFIX', 'https://m.vk.com/video');
define ('VK2_PREFIX', 'https://vk.com/feed?z=video');

define ('TIKTOK_PREFIX', 'https://www.tiktok.com/');
define ('TIKTOK1_PREFIX', 'https://vm.tiktok.com/');

if (substr($link, 0, strlen(VK_PREFIX))  == VK_PREFIX  ||
    substr($link, 0, strlen(VK1_PREFIX)) == VK1_PREFIX ||
    substr($link, 0, strlen(VK2_PREFIX)) == VK2_PREFIX) {

    if (substr($link, 0, strlen(VK_PREFIX)) == VK_PREFIX) {
	$url = str_replace('https://vk.com', 'https://m.vk.com', $link);
    } else if (substr($link, 0, strlen(VK2_PREFIX)) == VK2_PREFIX) {
	$url = str_replace('https://vk.com/feed?z=video', 'https://m.vk.com/video', $link);
    }

    $options  = array('http' => array('user_agent' => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36\r\n"));
    $context  = stream_context_create($options);
    $text = file_get_contents($url, false, $context);

    if ($text == "") {
	show_error($video_width, $video_height);
	exit;
    }

    $dom = new DomDocument();
    $dom->loadHTML($text);
    $elements = $dom->getElementsByTagName('video');

    if (!is_null($elements)) {
	foreach ($elements as $element) {
	    $nodes = $element->childNodes;
	    foreach ($nodes as $node) {
		if ($node->nodeName == "source") {
		    if ($node->getAttribute('type') == "video/mp4") {
			$vsrc = $node->getAttribute('src');
			$time = time();
			$type = $node->getAttribute('type');

			download_video($vsrc, $hash, $type, $time, $video_width, $video_height);
			break;
		    }
		}
	    }
	}
    }
} else if (substr($link, 0, strlen(TIKTOK_PREFIX))  == TIKTOK_PREFIX ||
	   substr($link, 0, strlen(TIKTOK1_PREFIX)) == TIKTOK1_PREFIX) {

    if (substr($link, 0, strlen(TIKTOK1_PREFIX)) == TIKTOK1_PREFIX) {
	$options  = array('http' => array('user_agent' => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36\r\n"));
	$context  = stream_context_create($options);
	$text = file_get_contents($link, false, $context);

	if ($text == "") {
	    show_error($video_width, $video_height);
	    exit;
	}

	$dom = new DomDocument();
	$dom->loadHTML($text);
	$elements = $dom->getElementsByTagName('link');

	$link = "";

	if (!is_null($elements)) {
	    foreach ($elements as $element) {
		if ($element->getAttribute('rel') == 'canonical') {
		    $link = $element->getAttribute('href');
		    break;
		}
	    }
	}

	if ($link == "") {
	    show_error($video_width, $video_height);
	    exit;
	}
    }

    $options  = array('http' => array('user_agent' => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36\r\n"));
    $context  = stream_context_create($options);
    $text = file_get_contents($link, false, $context);

    if ($text == "") {
	show_error($video_width, $video_height);
	exit;
    }

    $dom = new DomDocument();
    $dom->loadHTML($text);
    $elements = $dom->getElementsByTagName('video');

    if (!is_null($elements)) {
	foreach ($elements as $element) {
	    $vsrc = $element->getAttribute('src');
	    if ($vsrc != "") {
		$time = time();
		$type = 'video/mp4';

		download_video($vsrc, $hash, $type, $time, $video_width, $video_height);
		exit;
	    }
	}
    }

    show_error($video_width, $video_height);
} else {
    show_error($video_width, $video_height);
}

?>
