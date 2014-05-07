<?php
error_reporting(0);
ini_set( 'display_errors', 0 );

define('SUPPRESS_LOADING_OUTPUT', empty($_REQUEST['noob']));
define('NGG_DISABLE_RESOURCE_MANAGER', true); // NexGen Gallery: ne resource manager

if(defined('DOING_AJAX') && DOING_AJAX)
	define('WP_DEBUG_DISPLAY', false);


function wpfb_on_shutdown()
{
	 $error = error_get_last( );
	 if( $error && ($error['type'] == E_ERROR || $error['type'] == E_RECOVERABLE_ERROR || $error['type'] == E_PARSE) /*$error['type'] != E_STRICT && $error['type'] != E_NOTICE && $error['type'] != E_WARNING && $error['type'] != E_DEPRECATED*/ ) {
		 $func = function_exists('wpfb_ajax_die') ? 'wpfb_ajax_die' : 'wp_die';
		 $func(json_encode($error));
	 } else { return true; }
}
register_shutdown_function('wpfb_on_shutdown');

if((!empty($_REQUEST['fastload']) || (defined('FASTLOAD'))) && !defined('WP_INSTALLING'))
	define('WP_INSTALLING', true); // make wp load faster

if(SUPPRESS_LOADING_OUTPUT)
{
	define('WPFB_OB_LEVEL_PL', @ob_get_level());
	@ob_start();
}

if ( defined('ABSPATH') )
	require_once(ABSPATH . 'wp-load.php');
else
	require_once(dirname(__FILE__).'/../../../wp-load.php');

error_reporting(0);
ini_set( 'display_errors', 0 );

// check if WP-Filebase is active
/*
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
$wpfb_rpath = basename(untrailingslashit(dirname(__FILE__))).'/wp-filebase.php';
if(!is_plugin_active($wpfb_rpath))
	wp_die("WP-Filebase ($wpfb_rpath) not active.<!-- FATAL ERROR: WP-Filebase DISABLED -->");
 * 
 */


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


if(SUPPRESS_LOADING_OUTPUT) {
	//@ob_flush(); @flush();
	// restore ob_level
	while( (@ob_get_level() > WPFB_OB_LEVEL_PL) &&  @ob_end_clean()){} // destroy all ob buffers
}


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
	add_filter('wp_die_ajax_handler', create_function('$v','return "wpfb_ajax_die";'));
}
