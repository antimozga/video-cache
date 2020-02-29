<?php

include_once('config.php');

$database = new PDO("sqlite:videocache.sqlite");

if (!$database) {
    print('<b>Ошибка базы данных.</b>');
    exit(0);
}

$query = "CREATE TABLE IF NOT EXISTS VideoCache " .
	 "(id INTEGER PRIMARY KEY, hash NVARCHAR, type NVARCHAR, time INTEGER);";
$database->exec($query);

$link = 'https://vk.com/video-60130670_456254951';

$hash = md5($link);

$url = str_replace('https://vk.com', 'https://m.vk.com', $link);

printf("%s\n", $url);

$text = file_get_contents($url);

echo $text;

$dom = new DomDocument();
$dom->loadHTML($text);
$elements = $dom->getElementsByTagName('video');

if (!is_null($elements)) {
    foreach ($elements as $element) {
	echo "<br/>". $element->nodeName. ": ";

	$nodes = $element->childNodes;
	foreach ($nodes as $node) {
	    if ($node->nodeName == "source") {
		if ($node->getAttribute('type') == "video/mp4") {
		    echo $node->getAttribute('src');
		    $vsrc = $node->getAttribute('src');
		    system('wget "'.$vsrc.'" -O '.$CACHE_DIR.'/'.$hash.'.mp4');
		    $time = time();
		    $type = $node->getAttribute('type');
		    $database->exec("INSERT INTO VideoCache (hash, type, time) VALUES('$hash', '$type', '$time')");
		}
	    }
	}
    }
}

?>
