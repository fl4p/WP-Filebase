<?php
/**
 * Accepts file uploads from swfupload or other asynchronous upload methods.
 *
 */

define('DOING_AJAX', true);
define('TMP_FILE_MAX_AGE', 3600*3);
define('FRONTEND_UPLOAD', !empty($_REQUEST['frontend_upload']) && $_REQUEST['frontend_upload'] !== "false");
define('WP_ADMIN', !FRONTEND_UPLOAD);

require_once('wpfb-load.php');

// global vars like this have to be set after wp-load.php, because they sometimes get unset?!
$frontend_upload = FRONTEND_UPLOAD;
$file_add_now = !empty($_REQUEST['file_add_now']) && $_REQUEST['file_add_now'] !== "false";


// Flash often fails to send cookies with the POST or upload, so we need to pass it in GET or POST instead
if ( is_ssl() && empty($_COOKIE[SECURE_AUTH_COOKIE]) && !empty($_REQUEST['auth_cookie']) )
	$_COOKIE[SECURE_AUTH_COOKIE] = $_REQUEST['auth_cookie'];
elseif ( empty($_COOKIE[AUTH_COOKIE]) && !empty($_REQUEST['auth_cookie']) )
	$_COOKIE[AUTH_COOKIE] = $_REQUEST['auth_cookie'];
if ( empty($_COOKIE[LOGGED_IN_COOKIE]) && !empty($_REQUEST['logged_in_cookie']) )
	$_COOKIE[LOGGED_IN_COOKIE] = $_REQUEST['logged_in_cookie'];
unset($current_user);

if(!WP_DEBUG) {
	send_nosniff_header();
	error_reporting(0);
}
@header('Content-Type: text/plain; charset=' . get_option('blog_charset'));


if($frontend_upload) {	
	if($file_add_now) {
		wp_die('Unsupported upload!');
	} else {
		if(!WPFB_Core::$settings->frontend_upload && !current_user_can('upload_files'))
			wpfb_ajax_die(__('You do not have permission to upload files.'));
	}
} else {
	wpfb_loadclass('Admin');
	if ( !WPFB_Admin::CurUserCanUpload()  )
		wpfb_ajax_die(__('You do not have permission to upload files.'));
		
	check_admin_referer(WPFB.'-async-upload');
}

wpfb_loadclass('Admin');

if(!empty($_REQUEST['delupload']))
{
	$del_upload = @json_decode(stripslashes($_REQUEST['delupload']));
	if($del_upload && is_file($tmp = WPFB_Core::UploadDir().'/.tmp/'.str_replace(array('../','.tmp/'),'',$del_upload->tmp_name)))
		echo (int)@unlink($tmp);

	// delete other old temp files
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	$tmp_files = list_files(WPFB_Core::UploadDir().'/.tmp');
	foreach($tmp_files as $tmp) {
		if((time()-filemtime($tmp)) >= TMP_FILE_MAX_AGE)
			@unlink($tmp);
	}
	exit;
}

if(empty($_FILES['async-upload']))
	wp_die(__('No file was uploaded.', WPFB).' (ASYNC)');	


if(!@is_uploaded_file($_FILES['async-upload']['tmp_name'])
	|| !($tmp = WPFB_Admin::GetTmpFile($_FILES['async-upload']['name'])) || !@move_uploaded_file($_FILES['async-upload']['tmp_name'], $tmp))
{
	wpfb_ajax_die(sprintf(__('&#8220;%s&#8221; has failed to upload due to an error'), esc_html($_FILES['async-upload']['name']) ));
}
$_FILES['async-upload']['tmp_name'] = trim(substr($tmp, strlen(WPFB_Core::UploadDir())),'/');

$json = json_encode($_FILES['async-upload']);

if($file_add_now) {
	
	$file_data = array('file_flash_upload' => $json, 'file_category' => 0);
	if(!empty($_REQUEST['presets'])) {
		$presets = array();
		parse_str(stripslashes($_REQUEST['presets']), $presets);
		WPFB_Admin::AdaptPresets($presets);
		$file_data = array_merge($file_data, $presets);
	}
	
	$result = WPFB_Admin::InsertFile($file_data, false);
	if(empty($result['error'])) {
		$json = json_encode(array_merge((array)$result['file'], array(
			 'file_thumbnail_url' => $result['file']->GetIconUrl(),
			 'file_edit_url' => $result['file']->GetEditUrl(),
			 'file_cur_user_can_edit' => $result['file']->CurUserCanEdit(),
			 'file_download_url' => $result['file']->GetUrl(),
			 'nonce' => wp_create_nonce(WPFB.'-updatefile'.$result['file_id'])
		)));
	} else {
		wpfb_ajax_die($result['error']);
	}
}

@header('Content-Type: application/json; charset=' . get_option('blog_charset'));
@header('Content-Length: '.strlen($json));
echo $json;
