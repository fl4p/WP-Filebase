<?php

// ##########################################################
// ##########################################################
// #############    THIS FILE IS DEPRECATED!!    ############
// ##########################################################
// ##########################################################


// ob_start();
define('WPFB_NO_CORE_INIT', true);
define('WP_INSTALLING', true); // make wp load faster

if(empty($_GET['rp'])) // if rel path not set, need to load whole WP stuff to get to path to custom CSS!
	require_once(dirname(__FILE__).'/../../../wp-load.php');

require_once(dirname(__FILE__).'/wp-filebase.php'); // this only loads some wp-filebase stuff, NOT WP!
wpfb_loadclass('Core');

$file = WPFB_Core::GetOldCustomCssPath(stripslashes(@$_GET['rp']));
//echo $file;
//@ob_end_clean();

if(empty($file) || !@file_exists($file) || !@is_writable($file)) // TODO: remove writable check? this is for security!
	$file = WPFB_PLUGIN_ROOT . 'wp-filebase.css';
$ftime = filemtime($file);
header("Content-Type: text/css");
header("Cache-Control: max-age=3600");
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $ftime) . " GMT");
header("Content-Length: " . filesize($file));
if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $ftime) {
	header("HTTP/1.x 304 Not Modified");
	exit;
}

readfile($file);