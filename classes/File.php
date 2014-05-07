<?php
wpfb_loadclass('Item');

class WPFB_File extends WPFB_Item {
	
	const THUMB_REGEX = '/^-([0-9]+)x([0-9]+)\.(jpg|jpeg|png|gif)$/i';

	var $file_id = 0;
	var $file_name;
	var $file_path;
	var $file_size = 0;
	var $file_date;
	var $file_mtime = 0;
	var $file_hash;
	var $file_remote_uri;
	var $file_thumbnail;
	var $file_display_name;
	var $file_description;
	var $file_tags; // 0.2.9.9
	var $file_version;
	var $file_author;
	var $file_language;
	var $file_platform;
	var $file_requirement;
	var $file_license;
	var $file_user_roles;
	var $file_offline = 0;
	var $file_direct_linking = 0;
	var $file_force_download = 0;
	var $file_category = 0;
	var $file_category_name;
	var $file_update_of = 0; // TODO
	var $file_post_id = 0;
	var $file_attach_order = 0;
	var $file_wpattach_id = 0;
	var $file_added_by = 0;
	var $file_hits = 0;
	var $file_ratings = 0; // TODO
	var $file_rating_sum = 0; // TODO
	var $file_last_dl_ip;
	var $file_last_dl_time;
	
	
	//var $file_edited_time;
	
	//var $file_meta;
	
	static $cache = array();
	//static $cache_complete = false;
	
	static function InitClass()
	{
		global $wpdb;
		self::$id_var = 'file_id';
	}			
		
	static function GetFiles($extra_sql = '')
	{
		global $wpdb;
		$files = array();		
		$results = $wpdb->get_results("SELECT `$wpdb->wpfilebase_files`.* FROM $wpdb->wpfilebase_files $extra_sql");
		if(!empty($results)) {
			foreach(array_keys($results) as $i) {				
				$id = (int)$results[$i]->file_id;
				self::$cache[$id] = new WPFB_File($results[$i]);	
				$files[$id] = self::$cache[$id];
			}
		}
		
		unset($results);//
		
		return $files;
	}
	
	public static function GetReadPermsWhere($user=null)
	{
		return self::GetPermissionWhere('file_added_by', 'file_user_roles', $user);
	}
	
	static function GetSqlCatWhereStr($cat_id)
	{
		if(is_array($cat_id))
			return implode("OR", array_map(array(__CLASS__,__FUNCTION__), $cat_id));
		
		$cat_id = (int)$cat_id;
		return " (`file_category` = $cat_id) ";
	}
	
	private static function genSelectSql($where, $check_permissions, $order = null, $limit = -1, $offset = -1)
	{
		global $wpdb, $current_user;
		
		// parse where
		if(empty($where)) $where_str = '1=1';
		elseif(is_array($where)) {
			$where_str = '';
			foreach($where as $field => $value) {
				if($where_str != '') $where_str .= "AND ";
				if(is_numeric($value)) $where_str .= "$field = $value ";
				else $where_str .= "$field = '".esc_sql($value)."' ";
			}
		} else $where_str =& $where;
		
		if($check_permissions != false) {
			if($check_permissions === 'edit') {
				
				$can_edit_others = ((current_user_can('edit_others_posts') && !WPFB_Core::$settings->private_files)||current_user_can('manage_options'));
				$edit_cond = $can_edit_others ? "1=1" : ("file_added_by = ".((int)$current_user->ID));
				
				
				$where_str = "($where_str) AND (".self::GetReadPermsWhere().") AND ($edit_cond)";
			} else
				$where_str = "($where_str) AND (".self::GetReadPermsWhere(is_object($check_permissions) ? $check_permissions : null).") AND file_offline = '0'";
		}
			
		
		// join id3 table if found in where clause
		$join_str = (strpos($where_str, $wpdb->wpfilebase_files_id3) !== false) ? " LEFT JOIN $wpdb->wpfilebase_files_id3 ON ( $wpdb->wpfilebase_files_id3.file_id = $wpdb->wpfilebase_files.file_id ) " : "";
		
		// parse order
		if(empty($order))
		$order_str = '';
		elseif(is_array($order)) {
			$order_str = 'ORDER BY ';
			foreach($order as $field => $dir)
			$order_str .= "$field " . ((strtoupper($dir)=="DESC")?"DESC":"ASC") . ", ";
			$order_str .= "$wpdb->wpfilebase_files.file_id ASC";
		} else $order_str = "ORDER BY $order";
		
		if($offset > 0) $limit_str = "LIMIT ".((int)$offset).", ".((int)$limit);
		elseif($limit > 0) $limit_str = "LIMIT ".((int)$limit);
		else $limit_str = '';
		
		//echo "$wpdb->wpfilebase_files $join_str WHERE ($where_str) $order_str $limit_str";
		return "$wpdb->wpfilebase_files $join_str WHERE ($where_str) $order_str $limit_str";
	}
	
/**
 * Queries Files
 *
 * @param array|string $where Associative Where Array or SQL Expression
 * @param bool $check_permissions Whether to check permissions
 * @param array|string $order File Sorting Array or SQL Expression
 * @param int $limit Description
 * @param int $offset Description
 * @return array Array of File Objects.
 */
	static function GetFiles2($where = null, $check_permissions = false, $order = null, $limit = -1, $offset = -1)
	{
		global $wpdb;
		$files = array();
		$results = $wpdb->get_results("SELECT `$wpdb->wpfilebase_files`.* FROM ". self::genSelectSql($where, $check_permissions, $order, $limit, $offset));
		if(!empty($results)) {
			foreach(array_keys($results) as $i) {
				$id = (int)$results[$i]->file_id;
				self::$cache[$id] = new WPFB_File($results[$i]);
				$files[$id] = self::$cache[$id];
			}
		} elseif(!empty($wpdb->last_error) && current_user_can('upload_files')) {
			echo "<b>Database error</b>: ".$wpdb->last_error; // print debug only if usr can upload
		}
		
		unset($results);//
		return $files;
	}
	
	/**
	 * Get file by id
	 *
	 * @access public
	 *
	 * @param int $id ID 
	 * @return WPFB_File
	 */
	static function GetFile($id)
	{		
		$id = (int)($id);		
		if(isset(self::$cache[$id]) || WPFB_File::GetFiles("WHERE file_id = $id")) return self::$cache[$id];
		return null;
	}
	
	static function GetNumFiles($sql_or_cat = -1)
	{
		global $wpdb;
		static $n = -1;
		if($sql_or_cat == -1 && $n >= 0) return $n;
		if(is_numeric($sql_or_cat)) $sql_or_cat = (($sql_or_cat>=0)?" WHERE file_category = $sql_or_cat":"");
		$nn = $wpdb->get_var("SELECT COUNT($wpdb->wpfilebase_files.file_id) FROM $wpdb->wpfilebase_files $sql_or_cat"); 
		if($sql_or_cat == -1) $n = $nn;
		return $nn; 
	}
	
	static function GetNumFiles2($where, $check_permissions = true)
	{
		global $wpdb;
		return (int)$wpdb->get_var("SELECT COUNT(`{$wpdb->wpfilebase_files}`.`file_id`) FROM ".self::genSelectSql($where, $check_permissions));
	}
	
	static function GetAttachedFiles($post_id, $show_all=false)
	{
		$post_id = intval($post_id);
		return WPFB_File::GetFiles2(array('file_post_id' => $post_id), !$show_all && WPFB_Core::$settings->hide_inaccessible, WPFB_Core::GetSortSql(null, true));
	}
	
	static function GetByPost($post_id)
	{
		global $wpdb;
		$row = $wpdb->get_row("SELECT `$wpdb->wpfilebase_files`.* FROM $wpdb->wpfilebase_files WHERE file_wpattach_id = ".(int)$post_id." LIMIT 1");
		return empty($row) ? null : new WPFB_File($row);
	}
	
	function WPFB_File($db_row=null) {		
		parent::WPFB_Item($db_row);
		$this->is_file = true;
	}
	
	function DBSave()
	{ // validate some values before saving (fixes for mysql strict mode)
		if($this->locked > 0) return $this->TriggerLockedError();	
		$ints = array('file_category','file_post_id','file_attach_order','file_wpattach_id','file_added_by','file_update_of','file_hits','file_ratings','file_rating_sum');
		foreach($ints as $i) $this->$i = (int)($this->$i);
		$this->file_offline = (int)!empty($this->file_offline);
		$this->file_direct_linking = (int)$this->file_direct_linking;
		$this->file_force_download = (int)!empty($this->file_force_download);
		if(empty($this->file_last_dl_time)) $this->file_last_dl_time = '0000-00-00 00:00:00';
		$this->file_size = 0 + $this->file_size;
		$r = parent::DBSave();
		return $r;
	}
	
	// gets the extension of the file (including .)
	function GetExtension() { return strtolower(strrchr($this->file_name, '.')); }
	
	function GetType()
	{
		$ext = substr($this->GetExtension(), 1);
		if( ($type = wp_ext2type($ext)) ) return $type;		
		return $ext;
	}	
	
	function CreateThumbnail($src_image='', $del_src=false)
	{
		wpfb_loadclass('FileUtils');
		
		$src_set = !empty($src_image) && file_exists($src_image);
		$tmp_src = $del_src;
		if(!$src_set)
		{
			if(file_exists($this->GetLocalPath()))
				$src_image = $this->GetLocalPath();
			elseif($this->IsRemote()) {
				// if remote file, download it and use as source
				require_once(ABSPATH . 'wp-admin/includes/file.php');
				$res = wpfb_call('Admin', 'SideloadFile', $this->GetRemoteUri());
				$src_image = $res['file'];
				$tmp_src = true;
			}
		}
		
		if(!file_exists($src_image) || @filesize($src_image) < 3) {
			if($tmp_src) @unlink($src_image);
			return;
		}
		
		$ext = trim($this->GetExtension(), '.');
		$src_size = array();
		
		if(!WPFB_FileUtils::FileHasImageExt($this->file_name) && 
		!($src_set && WPFB_FileUtils::IsValidImage($src_image, $src_size))) { // check if valid image
			if($tmp_src) @unlink($src_image);
			return;
		}
		$this->DeleteThumbnail(); // delete old thumbnail
		
		$thumb_size = (int)WPFB_Core::$settings->thumbnail_size;
		if($thumb_size == 0) {
			if($tmp_src) @unlink($src_image);
			return;
		}
	
		$thumb = WPFB_FileUtils::CreateThumbnail($src_image, $thumb_size);

		
		$success = (!empty($thumb) && !is_wp_error($thumb) && is_string($thumb) && file_exists($thumb));

		if(!$src_set && !$success) {
			$this->file_thumbnail = null;
		} else {
			// fallback to source image WARNING: src img will be moved or deleted!
			if($src_set && !$success)
				$thumb = $src_image;
			
			$this->file_thumbnail = basename(trim($thumb , '.')); // FIX: need to trim . when image has no extension
			
			if(!is_dir(dirname($this->GetThumbPath()))) WPFB_Admin::Mkdir(dirname($this->GetThumbPath()));
			if(!@rename($thumb, $this->GetThumbPath())) {
				$this->file_thumbnail = null;
				@unlink($thumb);
			} else
				@chmod($this->GetThumbPath(), octdec(WPFB_PERM_FILE));
		}
		
		if($tmp_src) @unlink($src_image);
	}

	function GetPostUrl() { return empty($this->file_post_id) ? '' : WPFB_Core::GetPostUrl($this->file_post_id).'#wpfb-file-'.$this->file_id; }
	function GetFormattedSize() { return wpfb_call('Output', 'FormatFilesize', $this->file_size); }
	function GetFormattedDate($f='file_date') { return (empty($this->$f) || $this->$f == '0000-01-00 00:00:00') ? null : mysql2date(WPFB_Core::$settings->file_date_format, $this->$f); }
	function GetModifiedTime($gmt=false) { return $this->file_mtime + ($gmt ? ( get_option( 'gmt_offset' ) * 3600 ) : 0); }
	
	
	// only deletes file/thumbnail on FS, keeping DB entry
	function Delete($keep_thumb=false)
	{
		if(!$keep_thumb)
			$this->DeleteThumbnail();
		
		$this->file_remote_uri = null;
		
		if($this->IsLocal() && @unlink($this->GetLocalPath()))
		{
			$this->file_name = null;
			$this->file_size = 0;
			$this->file_date = null;		
			return true;
		}		
		return false;
	}	
	
	function DeleteThumbnail()
	{
		$thumb = $this->GetThumbPath();
		if(!empty($thumb) && file_exists($thumb)) @unlink($thumb);			
		$this->file_thumbnail = null;
		if(!$this->locked) $this->DBSave();
	}	

	// completly removes the file from DB and FS
	function Remove($bulk=false)
	{	
		global $wpdb;
		
		$id = (int)$this->file_id;	
		
		if($this->file_category > 0 && ($parent = $this->GetParent()) != null)
			$parent->NotifyFileRemoved($this);
	
		// remove file entry
		$wpdb->query("DELETE FROM $wpdb->wpfilebase_files WHERE file_id = $id");
		
		$wpdb->query("DELETE FROM $wpdb->wpfilebase_files_id3 WHERE file_id = $id");
		
			
		if(!$bulk)
			self::UpdateTags();
		
		$this->Lock(true); // prevent Delete() from saving to DB!
		
		return $this->Delete();
	}
	
	
	private function getInfoValue($path)
	{
		if(!isset($this->info)) // caching
		{
			global $wpdb;
			if($this->file_id <= 0) return join('->', $path);			
			$info = $wpdb->get_var("SELECT value FROM $wpdb->wpfilebase_files_id3 WHERE file_id = $this->file_id");
			$this->info = is_null($info) ? 0 : unserialize(base64_decode($info));
		}
		
		if(empty($this->info))
			return null;
		
		$val = $this->info;
		foreach($path as $p)
		{
			if(!isset($val[$p])) {
				if(isset($val[0]) && count($val) == 1) // if single array skip to first element
					$val = $val[0];
				else
					return null;				
			}
			$val = $val[$p];
		}		
		if(is_array($val)) $val = join(', ', $val);
		if($p == 'bitrate') {
			$val /= 1000;
			$val = round($val).' kBit/s';
		}
		return $val;
	}
    
    public function get_tpl_var($name)
    {		
		switch($name) {
			case 'file_url':			return htmlspecialchars($this->GetUrl());
			case 'file_url_rel':		return htmlspecialchars(WPFB_Core::$settings->download_base . '/' . str_replace('\\', '/', $this->GetLocalPathRel()));
			case 'file_post_url':		return htmlspecialchars(!($url = $this->GetPostUrl()) ? $this->GetUrl() : $url);			
			case 'file_icon_url':		return htmlspecialchars($this->GetIconUrl());
			case 'file_small_icon':		return '<img src="'.esc_attr($this->GetIconUrl('small')).'" alt="'.esc_attr(sprintf(__('Icon of %s',WPFB),$this->file_display_name)).'" style="vertical-align:middle;width:auto;'.((WPFB_Core::$settings->small_icon_size > 0) ? ('height:'.WPFB_Core::$settings->small_icon_size.'px;') : '').'" />';
			case 'file_size':			return $this->GetFormattedSize();
			case 'file_path':			return htmlspecialchars($this->GetLocalPathRel());
			
			case 'file_category':		return htmlspecialchars(is_object($cat = $this->GetParent()) ? $cat->cat_name : '');
			case 'cat_small_icon':		return is_null($cat = $this->GetParent()) ? '' : ('<img src="'.htmlspecialchars($cat->GetIconUrl('small')).'" alt="'.esc_attr(sprintf(__('Icon of %s',WPFB),$cat->cat_name)).'" style="width:auto;height:'.WPFB_Core::$settings->small_icon_size.'px;vertical-align:middle;" />');
			case 'cat_icon_url':		return is_null($cat = $this->GetParent()) ? '' : htmlspecialchars($cat->GetIconUrl());
			case 'cat_url':				return is_null($cat = $this->GetParent()) ? '' : htmlspecialchars($cat->GetUrl());
			case 'cat_id':				return $this->file_category;
			
			case 'file_cat_folder': 	return htmlspecialchars(is_object($cat = $this->GetParent()) ? $cat->cat_folder : '');
			
			case 'file_languages':		return wpfb_call('Output','ParseSelOpts', array('languages', $this->file_language),true);
			case 'file_platforms':		return wpfb_call('Output','ParseSelOpts', array('platforms', $this->file_platform),true);
			case 'file_requirements':	return wpfb_call('Output','ParseSelOpts', array('requirements', $this->file_requirement, true),true);
			case 'file_license':		return wpfb_call('Output','ParseSelOpts', array('licenses', $this->file_license, true), true);
			
			//case 'file_required_level':	return ($this->file_required_level - 1);
			case 'file_user_can_access': return $this->CurUserCanAccess();
			
			case 'file_description':	return nl2br($this->file_description);
			case 'file_tags':			return esc_html(str_replace(',',', ',trim($this->file_tags,',')));
			
			case 'file_date':
			case 'file_last_dl_time':	return htmlspecialchars($this->GetFormattedDate($name));
			
			case 'file_extension':		return strtolower(substr(strrchr($this->file_name, '.'), 1));
			case 'file_type': 			return wpfb_call('Download', 'GetFileType', $this->file_name);
			
			case 'file_url_encoded':	return htmlspecialchars(urlencode($this->GetUrl()));
			
			case 'file_added_by':		return (empty($this->file_added_by) || !($usr = get_userdata($this->file_added_by))) ? '' : esc_html($usr->display_name);
			
			case 'uid':					return self::$tpl_uid;
			
		}
		
    	if(strpos($name, 'file_info/') === 0)
		{
			$path = explode('/',substr($name, 10));
			return esc_html($this->getInfoValue($path));
		} elseif(strpos($name, 'file_custom') === 0) // dont esc custom
			return isset($this->$name) ? $this->$name : '';
		
		// string length limit:
		if(!isset($this->$name) && ($p=strpos($name, ':')) > 0) {
			$maxlen = (int)substr($name, $p+1);
			$name = substr($name, 0, $p);
			$str = $this->get_tpl_var($name);			
			if($maxlen > 3 && strlen($str) > $maxlen) $str = (function_exists('mb_substr') ? mb_substr($str, 0, $maxlen-3,'utf8') : mb_substr($str, 0, $maxlen-3)).'...';
			return $str;
		}
		
		return isset($this->$name) ? esc_html($this->$name) : '';
    }
	
	function DownloadDenied($msg_id) {
		if(WPFB_Core::$settings->inaccessible_redirect && !is_user_logged_in()) {
			//auth_redirect();
			$redirect = (WPFB_Core::$settings->login_redirect_src && wp_get_referer()) ? wp_get_referer() : $this->GetUrl();
			$login_url = wp_login_url($redirect, true); // force re-auth
			wp_redirect($login_url);
			exit;
		}
		$msg = WPFB_Core::GetOpt($msg_id);
		if(!$msg) $msg = $msg_id;
		elseif(@preg_match('/^https?:\/\//i',$msg)) {
			wp_redirect($msg); // redirect if msg is url
			exit;
		}
		wp_die((empty($msg)||!is_string($msg)) ? __('Cheatin&#8217; uh?') : $msg);
		exit;
	}
	
	// checks permissions, tracks download and sends the file
	function Download()
	{
		global $wpdb, $current_user, $user_ID;
		
		@error_reporting(0);
		wpfb_loadclass('Category', 'Download');
		$downloader_ip = preg_replace( '/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);
		get_currentuserinfo();
		$logged_in = (!empty($user_ID));
		$user_role = $logged_in ? reset($current_user->roles) : null; // get user's highest role (like in user-eidt.php)
		$is_admin = current_user_can('manage_options'); 
		
		// check user level
		if(!$this->CurUserCanAccess())
			$this->DownloadDenied('inaccessible_msg');
		
		// check offline
		if($this->file_offline && !$is_admin)
			wp_die(WPFB_Core::$settings->file_offline_msg);
		
		// check referrer
		if($this->file_direct_linking != 1) {			
			// if referer check failed, redirect to the file post
			if(!WPFB_Download::RefererCheck()) {
				$url = WPFB_Core::GetPostUrl($this->file_post_id);
				if(empty($url)) $url = home_url();
				wp_redirect($url);
				exit;
			}
		}
		
		
		// check traffic
		if($this->IsLocal() && !WPFB_Download::CheckTraffic($this->file_size)) {
			header('HTTP/1.x 503 Service Unavailable');
			wp_die(WPFB_Core::$settings->traffic_exceeded_msg);
		}

		// check daily user limit
		if(!$is_admin && WPFB_Core::$settings->daily_user_limits) {
			if(!$logged_in)
				$this->DownloadDenied('inaccessible_msg');
			
			$today = intval(date('z'));
			$usr_dls_today = intval(get_user_option(WPFB_OPT_NAME . '_dls_today'));
			$usr_last_dl_day = intval(date('z', intval(get_user_option(WPFB_OPT_NAME . '_last_dl'))));
			if($today != $usr_last_dl_day)
				$usr_dls_today = 0;
			
			// check for limit
			$dl_limit = intval(WPFB_Core::GetOpt('daily_limit_'.$user_role));
			if($dl_limit > 0 && $usr_dls_today >= $dl_limit)
				$this->DownloadDenied(sprintf(WPFB_Core::$settings->daily_limit_exceeded_msg, $dl_limit));			
			
			$usr_dls_today++;
			update_user_option($user_ID, WPFB_OPT_NAME . '_dls_today', $usr_dls_today);
			update_user_option($user_ID, WPFB_OPT_NAME . '_last_dl', time());
		}			
		
		// count download
		if(!$is_admin || !WPFB_Core::$settings->ignore_admin_dls) {
			$last_dl_time = mysql2date('U', $this->file_last_dl_time , false);
			if(empty($this->file_last_dl_ip) || $this->file_last_dl_ip != $downloader_ip || ((time() - $last_dl_time) > 86400))
				$wpdb->query("UPDATE " . $wpdb->wpfilebase_files . " SET file_hits = file_hits + 1, file_last_dl_ip = '" . $downloader_ip . "', file_last_dl_time = '" . current_time('mysql') . "' WHERE file_id = " . (int)$this->file_id);
		}
		
		// external hooks
		do_action( 'wpfilebase_file_downloaded', $this->file_id );
		
		// download or redirect
		$bw = 'bitrate_' . ($logged_in?'registered':'unregistered');
		if($this->IsLocal())
			WPFB_Download::SendFile($this->GetLocalPath(), array(
				'bandwidth' => WPFB_Core::$settings->$bw,
				'etag' => $this->file_hash,
				'md5_hash' => WPFB_Core::$settings->fake_md5 ? null : $this->file_hash, // only send real md5
				'force_download' => $this->file_force_download,
				'cache_max_age' => 10
			));
		else {
			//header('HTTP/1.1 301 Moved Permanently');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
			header('Location: '.$this->GetRemoteUri());
		}
		
		exit;
	}
	

	function GetRemoteUri() {
			return $this->file_remote_uri;
	}
	
	function SetPostId($id)
	{
		$id = intval($id);
		if($this->file_post_id == $id) return;
		$this->file_post_id = $id;	
		if($id > 0)
			$this->file_attach_order = count(self::GetAttachedFiles($id)) + 1;		
		if(!$this->locked) $this->DBSave();
	}
	
	function SetModifiedTime($mysql_date_or_timestamp)
	{
		if(!is_numeric($mysql_date_or_timestamp)) $mysql_date_or_timestamp = mysql2date('U', $mysql_date_or_timestamp);
		if($this->IsLocal()) {
			if(!@touch($this->GetLocalPath(), $mysql_date_or_timestamp))
				return false;
			$this->file_mtime = filemtime($this->GetLocalPath());
		} else {
			$this->file_mtime = $mysql_date_or_timestamp;
		}
		if(!$this->locked) $this->DBSave();
		return $this->file_mtime;
	}
	
	function SetTags($tags) {
		if(is_string($tags)) $tags = explode(',', $tags);
		$tags = array_unique(array_map('trim',(array)$tags));
		$this->file_tags = ','.implode(',',$tags).',';
		if(!$this->locked) $this->DBSave();
		self::UpdateTags($this);
	}
	
	function GetTags() {
		return explode(',', trim($this->file_tags,','));
	}
	
	static function UpdateTags($cur_file=null)
	{
		$tags = array();
		$files = self::GetFiles2((empty($cur_file) ? "" : "file_id <> $cur_file->file_id AND ") . "file_tags <> ''", false);
		if(!empty($cur_file)) $files[$cur_file->file_id] = $cur_file;
		foreach($files as $file) {
			$fts = $file->GetTags();
			foreach($fts as $ft) {
				$tags[$ft] = isset($tags[$ft]) ? ($tags[$ft]+1) : 1;
			}
		}
		ksort($tags);		
		update_option(WPFB_OPT_NAME.'_ftags', $tags);
	}
	
	
	
	function GetWPAttachmentID() {
		return $this->file_wpattach_id;
		//global $wpdb;
		//return $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid = %s", $this->GetUrl()) );
	}
	
	function IsRemote() { return !empty($this->file_remote_uri); }	
	function IsLocal() { return empty($this->file_remote_uri); }
}

?>