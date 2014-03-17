<?php

define('FASTLOAD', true);
require_once('wpfb-load.php');
wpfb_loadclass('Core','File','Category','Download');


$item = null;

if(isset($_GET['fid'])) {
	$fid = intval($_GET['fid']);
	
	if($fid == 0) {
		$img_path = ABSPATH . WPINC . '/images/';
		if(file_exists($img = $img_path.'crystal/default.png')
			|| file_exists($img = $img_path.'default.png')
			|| file_exists($img = $img_path.'blank.gif')
		) WPFB_Download::SendFile($img, array('cache_max_age' => 3600 * 12));
		exit;
	}
	
	$item = WPFB_File::GetFile($fid);
} elseif(isset($_GET['cid']))
	$item = WPFB_Category::GetCat(intval($_GET['cid']));
	
if($item == null || !$item->CurUserCanAccess(true))
	exit;


// if no thumbnail, redirect
if(empty($item->file_thumbnail) && empty($item->cat_icon))
{
	header('Location: ' . $item->GetIconUrl());
	exit;
}

// send thumbnail
WPFB_Download::SendFile($item->GetThumbPath(), array('cache_max_age' => 3600 * 12));
