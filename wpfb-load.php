<?php

define('SUPPRESS_LOADING_OUTPUT', empty($_REQUEST['noob']));

error_reporting(0);

function wpfb_on_shutdown()
{
	 $error = error_get_last( );
	 if( $error && $error['type'] != E_STRICT && $error['type'] != E_NOTICE && $error['type'] != E_WARNING  ) {
		 $func = function_exists('wpfb_ajax_die') ? 'wpfb_ajax_die' : 'wp_die';
		 $func(json_encode($error));
	 } else { return true; }
}
register_shutdown_function('wpfb_on_shutdown');

if((!empty($_REQUEST['fastload']) || (defined('FASTLOAD'))) && !defined('WP_INSTALLING'))
	define('WP_INSTALLING', true); // make wp load faster

if(SUPPRESS_LOADING_OUTPUT)
	@ob_start();

if ( defined('ABSPATH') )
	require_once(ABSPATH . 'wp-load.php');
else
	require_once(dirname(__FILE__).'/../../../wp-load.php');

error_reporting(0);

if(defined('WP_ADMIN') && WP_ADMIN) {
	require_once(ABSPATH.'wp-admin/admin.php');
} else {
	if(!function_exists('get_current_screen')) {
		function get_current_screen() { return null; }
	}
	if(!function_exists('add_meta_box')) {
		function add_meta_box() { return null; }
	}
}


if(SUPPRESS_LOADING_OUTPUT && ob_get_level() > 1)
	@ob_end_clean();


if(defined('WP_INSTALLING') && WP_INSTALLING) {
	require_once(dirname(__FILE__).'/wp-filebase.php'); // load wp-filebase only, no other plugins
	wpfb_loadclass('Core');
}

function wpfb_ajax_die($msg,$title='',$args='') {
	@ob_end_clean();
	echo '<div class="error-div">
	<strong>' . $msg . '</strong></div>';
	exit;	
}


if(defined('DOING_AJAX') && DOING_AJAX) {
	error_reporting(0);
	add_filter('wp_die_ajax_handler', create_function('$v','return "wpfb_ajax_die";'));
}

