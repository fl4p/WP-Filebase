<?php

if (!defined('WPFB')) {
    exit;
}

/* make sure we loaded the pluggable functions */
require( ABSPATH . WPINC . '/pluggable.php' );

define('WPFB_NO_CORE_INIT', true);
wpfb_loadclass('Core', 'File', 'Category', 'Download');

$item = null;

if (isset($_GET['fid'])) {
    $item = WPFB_File::GetFile(0 + $_GET['fid']);
} elseif (isset($_GET['cid'])) {
    $item = WPFB_Category::GetCat(0 + $_GET['cid']);
}

// consider the 'name' input argument as a secret and use it for authentication
// TODO: better use a signuature with wp_nonce api
if ($item == null || (!$item->CurUserCanAccess(true) && $_GET['name'] != $item->file_thumbnail)) {
    header('X-Fallback-Thumb: 1');
    $img_path = ABSPATH . WPINC . '/images/';
    if (file_exists($img = $img_path . 'crystal/default.png')
        || file_exists($img = $img_path . 'media/default.png')
    ) {
        WPFB_Download::SendFile($img, array('cache_max_age' => -1)); //was 3600 * 12
    } else {
        // single transparent pixel gif
        header('Content-Type: image/gif');
        header('Cache-Control: public');
        echo base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');
    }
    exit;
}


// if no thumbnail, redirect
if (empty($item->file_thumbnail) && empty($item->cat_icon)) {
    header('Cache-Control: public');
    wp_redirect($item->GetIconUrl(), 301/*permanently*/);
    exit;
}

// send thumbnail
WPFB_Download::SendFile($item->GetThumbPath(), array('cache_max_age' => -1));
exit;