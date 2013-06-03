<?php class WPFB_Misc {
static function GenRewriteRules() {
    global $wp_rewrite;
	$fb_pid = intval(WPFB_Core::$settings->file_browser_post_id);
	if($fb_pid > 0) {
		$is_page = (get_post_type($fb_pid) == 'page');
		$redirect = 'index.php?'.($is_page?'page_id':'p')."=$fb_pid";
		$base = trim(substr(get_permalink($fb_pid), strlen(home_url())), '/');
		$pattern = "$base/(.+)$";
		$wp_rewrite->rules = array($pattern => $redirect) + $wp_rewrite->rules;
	}
}

static function GetTraffic()
{
	$traffic = isset(WPFB_Core::$settings->traffic_stats) ? WPFB_Core::$settings->traffic_stats : array();
	$time = intval(@$traffic['time']);
	$year = intval(date('Y', $time));
	$month = intval(date('m', $time));
	$day = intval(date('z', $time));
	
	$same_year = ($year == intval(date('Y')));
	if(!$same_year || $month != intval(date('m')))
		$traffic['month'] = 0;
	if(!$same_year || $day != intval(date('z')))
		$traffic['today'] = 0;
		
	return $traffic;
}



static function UserRole2Level($role)
{
	switch($role) {
	case 'administrator': return 8;
	case 'editor': return 5;
	case 'author': return 2;
	case 'contributor': return 1;
	case 'subscriber': return 0;
	default: return -1;
	}
}

static function ParseIniFileSize($val) {
    if (is_numeric($val))
        return $val;

	$val_len = strlen($val);
	$bytes = substr($val, 0, $val_len - 1);
	$unit = strtolower(substr($val, $val_len - 1));
	switch($unit) {
		case 'k':
			$bytes *= 1024;
			break;
		case 'm':
			$bytes *= 1048576;
			break;
		case 'g':
			$bytes *= 1073741824;
			break;
	}
	return $bytes;
}

}
