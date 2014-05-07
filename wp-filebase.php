<?php
/*
Plugin Name: WP-Filebase
Plugin URI: http://fabi.me/wordpress-plugins/wp-filebase-file-download-manager/
Description: Adds a powerful downloads manager supporting file categories, download counter, widgets, sorted file lists and more to your WordPress blog.
Author: Fabian Schlieper
Version: 0.3.0.06
Author URI: http://wpfilebase.com/
*/

if(!defined('WPFB'))
{
	define('WPFB', 'wpfb');
	define('WPFB_VERSION', '0.3.0.06');
	define('WPFB_PLUGIN_ROOT', str_replace('\\','/',dirname(__FILE__)).'/');
	if(!defined('ABSPATH')) {
		define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))));
	} else {
		//define('WPFB_PLUGIN_URI', plugins_url('/',__FILE__));
		$wpfb_uri = str_replace(str_replace('\\','/',ABSPATH),get_option('siteurl').'/',WPFB_PLUGIN_ROOT);
		$wpfb_uri = is_ssl() ? str_replace('http://', 'https://', $wpfb_uri) : str_replace('https://', 'http://', $wpfb_uri);
		define('WPFB_PLUGIN_URI', $wpfb_uri);
		unset($wpfb_uri);
	}
	if(!defined('WPFB_PERM_FILE')) define('WPFB_PERM_FILE', 666);
	if(!defined('WPFB_PERM_DIR')) define('WPFB_PERM_DIR', 777);
	define('WPFB_OPT_NAME', 'wpfilebase');
	define('WPFB_PLUGIN_NAME', 'WP-Filebase');
	define('WPFB_TAG_VER', 2);
	
	
	function wpfb_loadclass($cl)
	{
		if(func_num_args() > 1) {
			$args = func_get_args(); // func_get_args can't be used as func param!
			return array_map(__FUNCTION__, $args);
		} else {
			$cln = 'WPFB_'.$cl;
			
			if(class_exists($cln))
				return true;
				
			$p = WPFB_PLUGIN_ROOT . "classes/{$cl}.php";
			$res = (include_once $p);
			if(!$res)
			{
				echo("<p>WP-Filebase Error: Could not include class file <b>'{$cl}'</b>!</p>");
				if(defined('WP_DEBUG') && WP_DEBUG) {
					print_r(debug_backtrace());
				}
			}
			else
			{				
				if(!class_exists($cln))
				{
					echo("<p>WP-Filebase Error: Class <b>'{$cln}'</b> does not exists in loaded file!</p>");
					return false;
				}
				
				if(method_exists($cln, 'InitClass'))
					call_user_func(array($cln, 'InitClass'));
			}
		}
		return $res;
	}
	
	// calls static $fnc of class $cl with $params
	// $cl is loaded automatically if not existing
	function wpfb_call($cl, $fnc, $params=null, $is_args_array=false)
	{
		$cln = 'WPFB_'.$cl;
		$fnc = array($cln, $fnc);
		return (class_exists($cln) || wpfb_loadclass($cl)) ? ($is_args_array ? call_user_func_array($fnc, $params) : call_user_func($fnc, $params)) : null;
	}
	
	function wpfilebase_init()
	{
		wpfb_loadclass('Core');
	}
	
	function wpfilebase_widgets_init()
	{
		wpfb_loadclass('Widget');
	}
	
	function wpfilebase_activate() {
		define('WPFB_NO_CORE_INIT',true);
		wpfb_loadclass('Core','Admin', 'Setup');
		WPFB_Setup::OnActivateOrVerChange(WPFB_Core::$settings->version);
	}
	
	function wpfilebase_deactivate() {
		wpfb_loadclass('Core','Admin','Setup');
		WPFB_Setup::OnDeactivate();
	}
	
	// FIX: setup the OB to truncate any other output when downloading
	if(!empty($_GET['wpfb_dl'])) {
		@define('NGG_DISABLE_RESOURCE_MANAGER', true); // NexGen Gallery
		ob_start();
	}
}

// database settings
global $wpdb;
if(isset($wpdb))
{
	$wpdb->wpfilebase_cats = $wpdb->prefix . 'wpfb_cats';
	$wpdb->wpfilebase_files = $wpdb->prefix . 'wpfb_files';
	$wpdb->wpfilebase_files_id3 = $wpdb->prefix . 'wpfb_files_id3';
}

if(function_exists('add_action')) {
	add_action('init', 'wpfilebase_init');
	add_action('widgets_init', 'wpfilebase_widgets_init' );
	add_action('admin_init', array('WPFB_Core', 'AdminInit'), 10);
	add_action('admin_menu', array('WPFB_Core', 'AdminMenu'));
	register_activation_hook(__FILE__, 'wpfilebase_activate');
	register_deactivation_hook(__FILE__, 'wpfilebase_deactivate');
}
