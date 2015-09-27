<?php class WPFB_ExtensionLib {
	
private static function apiRequest($act, $post_data=null, $use_ssl=true) {
	global $wp_version;
	//print_r($post_data);
	$site = rawurlencode(base64_encode(get_option('siteurl')));
	$url = "http".($use_ssl? "s://ssl-account.com" : ":/")."/interface.fabi.me/wpfilebase-pro/$act.php";
	$get_args = array('version' => WPFB_VERSION, 'pl_slug' => 'wp-filebase','pl_ver'=> WPFB_VERSION, 'wp_ver' => $wp_version  , 'site' => $site);
	
	if(empty($post_data)) {
		$res = wp_remote_get($url, $get_args);
	} else {
		$res = wp_remote_post(add_query_arg($get_args, $url), array('body' => $post_data));
	}
	
	//print_r($res);
	
	if(is_wp_error($res)) {
		if($use_ssl) // retry without ssl
			return self::apiRequest ($act, $post_data, false);
		echo "<b>WP-Filebase API request error:</b>";
		print_r($res);
		return false;
	}
	return empty($res['body']) ? false : json_decode($res['body']);
}

static function GetExtensionsVersionNumbers() {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$installed_versions = array();
	foreach(get_plugins() as $p => $details) {
		if(substr($p,0,5) === "wpfb-") {
			$installed_versions[$p] = $details['Version'];
		}
	}
	return $installed_versions;
}


static function GetLatestVersionInfoExt()
{		
	$ext_vers = self::GetExtensionsVersionNumbers();
	$res = self::apiRequest('update-check-ext', array('extensions' => json_encode($ext_vers)));
	return empty($res) ? array() : (array)$res;
}

static function GetLatestVersionInfo(){	return self::apiRequest('update-check'); }


static function QueryAvailableExtensions($bought_extensions_only=false) {
	$ext_vers = self::GetExtensionsVersionNumbers();	
	$res = self::apiRequest('exts', array('bought' => $bought_extensions_only, 'extensions' => json_encode($ext_vers)));
	if(!$res) return false;
	$res = (object)$res;
	$res->info = (array)$res->info;
	foreach($res->plugins as $i => $p) {
		$res->plugins[$i] = (object)$p;
		$res->plugins[$i]->ratings = (array)$res->plugins[$i]->ratings;
		$res->plugins[$i]->icons = (array)$res->plugins[$i]->icons;
	}
	return $res;
}

static function GetApiPluginInfo($slug)
{
	$info = self::apiRequest('version-info', array('plugin_slug' => $slug));
	if(empty($info)) return false;
	if(!empty($info->sections) && is_object($info->sections)) $info->sections = (array) $info->sections;
	return $info;
}

}