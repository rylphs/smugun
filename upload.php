<?php
//require_once "SmugmugClient.php";
//require_once "Uploader.php";

$path = $argv[1];

$folders = explode("/", $path);
$folder = $folders[count($folders)-2];

/*$files = glob($dir+'/*.log', GLOB_BRACE);
foreach($files as $file){
	unlink($file);
}
$uploader = new Uploader($dir);
$uploader->startProcessing();*/

?>
