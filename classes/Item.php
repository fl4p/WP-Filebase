<?php
class WPFB_Item {

	var $is_file;
	var $is_category;
	
	var $last_parent_id = 0;
	var $last_parent = null;
	
	var $locked = 0;
	
	private $_read_permissions = null;
	
	static $tpl_uid = 0;
	static $id_var;
	
	
	function WPFB_Item($db_row=null)
	{
		if(!empty($db_row))
		{
			foreach($db_row as $col => $val){
				$this->$col = $val;
			}	
		
			$this->is_file = isset($this->file_id);
			$this->is_category = isset($this->cat_id);
		}
	}
	
	function GetId(){return (int)($this->is_file?$this->file_id:$this->cat_id);}	
	function GetName(){return $this->is_file?$this->file_name:$this->cat_folder;}	
	function GetTitle($maxlen=0){
		$t = $this->is_file?$this->file_display_name:$this->cat_name;
		if($maxlen > 3 && strlen($t) > $maxlen) $t = (function_exists('mb_substr') ? mb_substr($t, 0, $maxlen-3,'utf8') : substr($t, 0, $maxlen-3)).'...';
		return $t;
	}	
	function Equals($item){return (isset($item->is_file) && $this->is_file == $item->is_file && $this->GetId() > 0 && $this->GetId() == $item->GetId());}	
	function GetParentId(){return ($this->is_file ? $this->file_category : $this->cat_parent);}	
	function GetParent()
	{
		if(($pid = $this->GetParentId()) != $this->last_parent_id)
		{ // caching
			if($pid > 0) $this->last_parent = WPFB_Category::GetCat($pid);
			else $this->last_parent = null;
			$this->last_parent_id = $pid;
		}
		return $this->last_parent;
	}
	function GetParents()
	{
		$p = $this;
		$parents = array();
		while(!is_null($p = $p->GetParent())) $parents[] = $p;
		return $parents;
	}
	
	function GetOwnerId()
	{
		return (int)($this->is_file ? $this->file_added_by : $this->cat_owner);
	}
	
	function Lock($lock=true) {
		if($lock) $this->locked++;
		else $this->locked = max(0, $this->locked-1);
	}
	
	static function GetByName($name, $parent_id=0)
	{
		global $wpdb;
		
		$name = esc_sql($name);
		$parent_id = intval($parent_id);
		
		$items = WPFB_Category::GetCats("WHERE cat_folder = '$name' AND cat_parent = $parent_id LIMIT 1");
		if(empty($items)){
			$items = WPFB_File::GetFiles2(array('file_name' => $name, 'file_category' => $parent_id), false, null, 1);
			if(empty($items)) return null;
		}

		return reset($items);
	}
	
	static function GetByPath($path)
	{
		global $wpdb;
		$path = trim(str_replace('\\','/',$path),'/');		
		$items = WPFB_Category::GetCats("WHERE cat_path = '".esc_sql($path)."' LIMIT 1");
		if(empty($items)){
			$items = WPFB_File::GetFiles2(array('file_path' => $path), false, null, 1);
			if(empty($items)) return null;
		}

		return reset($items);
	}
	
	// Sorts an array of Items by SQL ORDER Clause ( or shortcode order clause (<file_name)
	static function Sort(&$items, $order_sql) {
		$order_sql = strtr($order_sql, array('&gt;'=>'>','&lt;'=>'<'));
		if(($desc = ($order_sql{0} == '>')) || $order_sql{0} == '<')
			$on = substr($order_sql,1);
		else {
			$p = strpos($order_sql,','); // strip multi order clauses
			if($p >= 0) $order_sql = substr($order_sql, $p + 1);
			$sort = explode(" ", trim($order_sql));
			$on = trim($sort[0],'`');
			$desc = (trim($sort[1]) == "DESC");
		}
		$on	= preg_replace('/[^0-9a-z_]/i', '', $on); //strip hacking
	    $comparer = $desc ? "return -strcmp(\$a->{$on},\$b->{$on});" : "return strcmp(\$a->{$on},\$b->{$on});";
    	usort($items, create_function('$a,$b', $comparer)); 
	}

	function GetEditUrl()
	{
		$fc = ($this->is_file?'file':'cat');
		return admin_url("admin.php?page=wpfilebase_{$fc}s&action=edit{$fc}&{$fc}_id=".$this->GetId());
	}
	
	function GetLocalPath($refresh=false){return WPFB_Core::UploadDir() . '/' . $this->GetLocalPathRel($refresh);}	
	function GetLocalPathRel($refresh=false)
	{		
		if($this->is_file) $cur_path =& $this->file_path;
		else $cur_path =& $this->cat_path;

		if($refresh)
		{			
			if(($parent = $this->GetParent()) != null)	$path = $parent->GetLocalPathRel($refresh) . '/';
			else $path = '';			
			$path .= $this->is_file ? $this->file_name : $this->cat_folder;
			
			if($cur_path != $path) {
				$cur_path = $path; // by ref!!
				if(!$this->locked) $this->DBSave();
			}
			
			return $path;			
		} else {
			if(empty($cur_path)) return $this->GetLocalPathRel(true);
			return $cur_path;	
		}
	}
	
	protected function TriggerLockedError() {
		trigger_error("Cannot save locked item '".$this->GetName()."' to database!", E_USER_WARNING);
		return false;		
	}

	function DBSave()
	{
		global $wpdb;
		
		if($this->locked > 0) {
			$this->TriggerLockedError();
			return array('error' => 'Item locked.');
		}
		
		$values = array();
		
		$id_var = ($this->is_file?'file_id':'cat_id');
		
		$vars = get_class_vars(get_class($this));
		foreach($vars as $var => $def)
		{
			$pos = strpos($var, ($this->is_file?'file_':'cat_'));
			if($pos === false || $pos != 0 || $var == $id_var || is_array($this->$var) || is_object($this->$var))
				continue;			
			$values[$var] = $this->$var; // no & ref here, this causes esc of actual objects data!!!!
		}
		
		if($this->is_file) {
			$cvars = WPFB_Core::GetCustomFields(true);
			foreach($cvars as $var => $cn)
				$values[$var] = empty($this->$var) ? '' : $this->$var;
		}
		
		
		$update = !empty($this->$id_var);
		$tbl = $this->is_file?$wpdb->wpfilebase_files:$wpdb->wpfilebase_cats;
		if ($update)
		{
			if( !$wpdb->update($tbl, $values, array($id_var => $this->$id_var) ))
			{
				if(!empty($wpdb->last_error))
					return array( 'error' => 'Failed to update DB! ' . $wpdb->last_error);
			}
		} else {		
			if( !$wpdb->insert($tbl, $values) )
				return array( 'error' =>'Unable to insert item into DB! ' . $wpdb->last_error);				
			$this->$id_var = (int)$wpdb->insert_id;		
		}
		
		return array( 'error' => false, $id_var => $this->$id_var, 'id' => $this->$id_var);
	}
	
	function IsAncestorOf($item)
	{			
		$p = $item->GetParent();
		if ($p == null) return false;
		if ($this->Equals($p)) return true;
		return $this->IsAncestorOf($p);
	}
	
	function CurUserCanAccess($for_tpl=false, $user = null)
	{
		$user = is_null($user) ? wp_get_current_user() : (empty($user->roles) ? new WP_User($user) : $user);
		$user->get_role_caps();
		
		if( ($for_tpl && !WPFB_Core::$settings->hide_inaccessible) || in_array('administrator',$user->roles) || ($this->is_file && $this->CurUserIsOwner($user)) )
			return true;
		if(WPFB_Core::$settings->private_files && $this->GetOwnerId() != 0 && !$this->CurUserIsOwner($user)) // check private files
			return false;
		$frs = $this->GetReadPermissions();
		if(empty($frs)) return true; // item is for everyone!
		foreach($user->roles as $ur) { // check user roles against item roles
			if(in_array($ur, $frs))
				return true;
		}
		return false;
	}
	
	function CurUserCanEdit($user = null)
	{
		if(is_null($user)) $user = wp_get_current_user ();
		// current_user_can('manage_options') checks if user is admin!
		return $this->CurUserIsOwner($user) || user_can($user, 'manage_options') || (!WPFB_Core::$settings->private_files && user_can($user, $this->is_file ? 'edit_others_posts' : 'manage_categories'));
	}
	
	function GetUrl($rel=false, $to_file_page=false)
	{ // TODO: rawurlencode??
		$ps = WPFB_Core::$settings->disable_permalinks ? null : get_option('permalink_structure');
		if($this->is_category || $to_file_page) {
			$url = get_permalink(WPFB_Core::$settings->file_browser_post_id);	
			// todo: rawurlencode here?
			if(!empty($ps)) $url .= strtr($this->GetLocalPathRel(), array('#'=>'%23',' '=>'%20')).'/';
			elseif($this->GetId() > 0) $url = add_query_arg(array(($this->is_file?"wpfb_file":"wpfb_cat") => $this->GetId()), $url);
			if($this->is_category) $url .= "#wpfb-cat-$this->cat_id";	
		} else {
			if(!empty($ps)) $url = home_url(strtr(WPFB_Core::$settings->download_base.'/'.$this->GetLocalPathRel(), array('#'=>'%23',' '=>'%20')));
			else $url = home_url('?wpfb_dl='.$this->file_id);			
		}
		if($rel) {
			$url = substr($url, strlen(home_url()));
			if($url{0} == '?') $url = 'index.php'.$url;
			else $url = substr($url, 0); // remove trailing slash! TODO?!
		}
		return $url;
	}
	
	function GenTpl($parsed_tpl=null, $context='')
	{
		if($context!='ajax')
			WPFB_Core::$load_js = true;
		
		if(empty($parsed_tpl))
		{
			$tpo = $this->is_file?'template_file_parsed':'template_cat_parsed';
			$parsed_tpl = WPFB_Core::GetOpt($tpo);
			if(empty($parsed_tpl))
			{
				$parsed_tpl = wpfb_call('TplLib', 'Parse', WPFB_Core::GetOpt($this->is_file?'template_file':'template_cat'));
				WPFB_Core::UpdateOption($tpo, $parsed_tpl); 
			}
		}
		/*
		if($this->is_file) {
			global $wpfb_file_paths;
			if(empty($wpfb_file_paths)) $wpfb_file_paths = array();
			$wpfb_file_paths[(int)$this->file_id] = $this->GetLocalPathRel();
		}
		*/
		self::$tpl_uid++;
		$f =& $this;
		return eval("return ($parsed_tpl);");
	}
	
	function GenTpl2($tpl_tag=null, $load_js=true)
	{
		static $tpl_funcs = array('file' => array(), 'cat' => array());
		
		if(empty($tpl_tag)) $tpl_tag = 'default';
		if($load_js) WPFB_Core::$load_js = true;	
			
		$type = $this->is_file ? 'file' : 'cat';
		
		if(empty($tpl_funcs[$type][$tpl_tag]))
		{
			$parsed_tpl = WPFB_Core::GetParsedTpl($this->is_file?'file':'cat', $tpl_tag);
			if(empty($parsed_tpl)) return "Template $type :: $tpl_tag does not exist!";
			$tpl_funcs[$type][$tpl_tag] = WPFB_Core::CreateTplFunc($parsed_tpl);
		}
		
		self::$tpl_uid++;
			
		return $tpl_funcs[$type][$tpl_tag]($this);
	}
	
	function GetThumbPath($refresh=false)
	{
		static $base_dir = '';
		if(empty($base_dir) || $refresh)
			$base_dir = (empty(WPFB_Core::$settings->thumbnail_path) ? WPFB_Core::UploadDir() : path_join(ABSPATH, WPFB_Core::$settings->thumbnail_path)) . '/';
			
		if($this->is_file) {
			if(empty($this->file_thumbnail)) return null;			
			return  dirname($base_dir . $this->GetLocalPathRel()) . '/' . $this->file_thumbnail;
		} else {		
			if(empty($this->cat_icon)) return null;
			return $base_dir . $this->GetLocalPathRel() . '/' . $this->cat_icon;
		}
	}
	
	function GetIconUrl($size=null) {
		// todo: remove file operations!
		
		if($this->is_category)
		{
			// add mtime for cache updates
			return empty($this->cat_icon) ? (($size=='small')?(WP_CONTENT_URL.WPFB_Core::$settings->folder_icon):(WPFB_PLUGIN_URI.'images/crystal_cat.png')) : WPFB_PLUGIN_URI."wp-filebase_thumb.php?cid=$this->cat_id&t=".@filemtime($this->GetThumbPath());
		}

		if(!empty($this->file_thumbnail) /* && file_exists($this->GetThumbPath())*/) // speedup
		{
			return WPFB_PLUGIN_URI . 'wp-filebase_thumb.php?fid='.$this->file_id.'&name='.$this->file_thumbnail; // name var only for correct caching!
		}
				
		$type = $this->GetType();
		$ext = substr($this->GetExtension(), 1);
		
		$img_path = ABSPATH . WPINC . '/images/';
		$img_url = get_option('siteurl').'/'. WPINC .'/images/';
		$custom_folder = '/images/fileicons/';
		
		// check for custom icons
		if(file_exists(WP_CONTENT_DIR.$custom_folder.$ext.'.png'))
			return WP_CONTENT_URL.$custom_folder.$ext.'.png';		
		if(file_exists(WP_CONTENT_DIR.$custom_folder.$type.'.png'))
			return WP_CONTENT_URL.$custom_folder.$type.'.png';
		

		if(file_exists($img_path . 'crystal/' . $ext . '.png'))
			return $img_url . 'crystal/' . $ext . '.png';
		if(file_exists($img_path . 'crystal/' . $type . '.png'))
			return $img_url . 'crystal/' . $type . '.png';	
				
		if(file_exists($img_path . $ext . '.png'))
			return $img_url . $ext . '.png';
		if(file_exists($img_path . $type . '.png'))
			return $img_url . $type . '.png';
		
		// fallback to default
		if(file_exists($img_path . 'crystal/default.png'))
			return $img_url . 'crystal/default.png';		
		if(file_exists($img_path . 'default.png'))
			return $img_url . 'default.png';
		
		// fallback to blank :(
		return $img_url . 'blank.gif';
	}
	
	// for a category this return an array of child files
	// for a file an array with a single element, the file itself
	function GetChildFiles($recursive=false, $sorting=null, $check_permissions = false)
	{
		if($this->is_file)
			return array($this->GetId() => $this);
		
		if($check_permissions && !$this->CurUserCanAccess()) return array();
		
		// if recursive, include secondary category links with GetSqlCatWhereStr
		$where = $recursive ? WPFB_File::GetSqlCatWhereStr($this->cat_id) : '(file_category = '.$this->cat_id.')';

		$files = WPFB_File::GetFiles2($where, $check_permissions, $sorting);
		if($recursive) {
			$cats = $this->GetChildCats(true);
			foreach(array_keys($cats) as $i)
				$files += $cats[$i]->GetChildFiles(false, $sorting, $check_permissions);
		}		
		return $files;
	}
	
	function GetReadPermissions() {
		if(!is_null($this->_read_permissions)) return $this->_read_permissions; //caching
		$rs = $this->is_file?$this->file_user_roles:$this->cat_user_roles;
		return ($this->_read_permissions = empty($rs) ? array() : array_filter((is_string($rs) ? explode('|', $rs) : (array)$rs)));
	}
	
	function SetReadPermissions($roles)
	{
		if(!is_array($roles)) $roles = explode('|',$roles);
		$this->_read_permissions = $roles =  array_filter(array_filter(array_map('trim',$roles),'strlen')); // remove empty
		$roles = implode('|', $roles);
		if($this->is_file) $this->file_user_roles = $roles;
		else $this->cat_user_roles = $roles;
		if(!$this->locked) $this->DBSave();
	}
	
		
	function CurUserIsOwner($user=null) {
		global $current_user;
		$uid = empty($user) ? (empty($current_user->ID) ? 0 : $current_user->ID) : $user->ID;
		return ($uid > 0 && $this->GetOwnerId() == $uid);
	}
	
	function ChangeCategoryOrName($new_cat_id, $new_name=null, $add_existing=false, $overwrite=false)
	{
		// 1. apply new values (inherit permissions if nothing (Everyone) set!)
		// 2. check for name collision and rename
		// 3. move stuff
		// 4. notify parents
		// 5. update child paths
		if(empty($new_name)) $new_name = $this->GetName();
		$this->Lock(true);
		
		$new_cat_id = intval($new_cat_id);
		$old_cat_id = $this->GetParentId();
		$old_path_rel = $this->GetLocalPathRel(true);
		$old_path = $this->GetLocalPath();
		$old_name = $this->GetName();
		if($this->is_file) $old_thumb_path = $this->GetThumbPath();
		
		$old_cat = $this->GetParent();
		$new_cat = WPFB_Category::GetCat($new_cat_id);
		if(!$new_cat) $new_cat_id = 0;
		
		$cat_changed = $new_cat_id != $old_cat_id;
		$name_changed = $new_name != $old_name;
		
		if($this->is_file) {
			$this->file_category = $new_cat_id;
			$this->file_name = $new_name;
			$this->file_category_name = ($new_cat_id==0) ? '' : $new_cat->GetTitle();
		} else {
			$this->cat_parent = $new_cat_id;
			$this->cat_folder = $new_name;
		}
		
		// inherit user roles
		if(count($this->GetReadPermissions()) == 0) 
			$this->SetReadPermissions(($new_cat_id != 0) ? $new_cat->GetReadPermissions() : WPFB_Core::$settings->default_roles);
		
		// flush cache
		$this->last_parent_id = -1; 

		$new_path_rel = $this->GetLocalPathRel(true);
		$new_path = $this->GetLocalPath();

		if($new_path_rel != $old_path_rel) {
			$i = 1;
			if(!$add_existing) {
				$name = $this->GetName();
				if($overwrite) {
					if(@file_exists($new_path)) {
						$ex_file = WPFB_File::GetByPath($new_path_rel);
						if(!is_null($ex_file))
							$ex_file->Remove();
						else 
							@unlink($new_path);
					}
				} else {
					// rename item if filename collision (ignore if coliding with $this)
					while(@file_exists($new_path) || (!is_null($ex_file = WPFB_File::GetByPath($new_path_rel)) && !$this->Equals($ex_file))) {
						$i++;	
						if($this->is_file) {
							$p = strrpos($name, '.');
							$this->file_name = ($p <= 0) ? "$name($i)" : (substr($name, 0, $p)."($i)".substr($name, $p));
						} else
							$this->cat_folder = "$name($i)";				
						
						$new_path_rel = $this->GetLocalPathRel(true);
						$new_path = $this->GetLocalPath();
					}
				}
			}
			
			// finally move it!
			if(!empty($old_name) && @file_exists($old_path)) {
				if($this->is_file && $this->IsLocal()) {
					if(!@rename($old_path, $new_path))
						return array( 'error' => sprintf('Unable to move file %s!', $old_path));
					@chmod($new_path, octdec(WPFB_PERM_FILE));
					
					// move thumb
					if(!empty($old_thumb_path) && @is_file($old_thumb_path)) {
						$thumb_path = $this->GetThumbPath();
						if($i > 1) {
							$p = strrpos($thumb_path, '-');
							if($p <= 0) $p = strrpos($thumb_path, '.');
							$thumb_path = substr($thumb_path, 0, $p)."($i)".substr($thumb_path, $p);
							$this->file_thumbnail = basename($thumb_path);			
						}
						if(!is_dir(dirname($thumb_path))) WPFB_Admin::Mkdir(dirname($thumb_path));
						if(!@rename($old_thumb_path, $thumb_path)) return array( 'error' =>'Unable to move thumbnail! '.$thumb_path);
						@chmod($thumb_path, octdec(WPFB_PERM_FILE));
					}
				} else {
					if(!@is_dir($new_path)) wp_mkdir_p($new_path);
					wpfb_loadclass('FileUtils');
					if(!@WPFB_FileUtils::MoveDir($old_path, $new_path))
						return array( 'error' => sprintf('Could not move folder %s to %s', $old_path, $new_path));
				}
			} else {
				if($this->is_category) {
					if(!@is_dir($new_path) && !wp_mkdir_p($new_path))
						return array('error' => sprintf(__( 'Unable to create directory %s. Is it\'s parent directory writable?'), $new_path));		
				}
			}
			
			$all_files = $this->GetChildFiles(true); // all children files (recursively)
			if(!empty($all_files)) foreach($all_files as $file) {
				if($cat_changed) {
					if($old_cat) $old_cat->NotifyFileRemoved($file); // notify parent cat to remove files
					if($new_cat) $new_cat->NotifyFileAdded($file);
				}
				$file->GetLocalPathRel(true); // update file's path
			}
			
			if($this->is_category) {
				$cats = $this->GetChildCats(true);
				if(!empty($cats)) foreach($cats as $cat) {
					$cat->GetLocalPathRel(true); // update cats's path
				}
			}
		}
		
		$this->Lock(false);
		if(!$this->locked) $this->DBSave();
		return array('error'=>false);
		
		/*
		 * 		// create the directory if it doesnt exist
		// move file
		if($this->IsLocal() && !empty($old_file_path) && @is_file($old_file_path) && $new_file_path != $old_file_path) {
			if(!@rename($old_file_path, $new_file_path)) return array( 'error' => sprintf('Unable to move file %s!', $this->GetLocalPath()));
			@chmod($new_file_path, octdec(WPFB_PERM_FILE));
		}
		 */
	}
	
	protected static function GetPermissionWhere($owner_field, $permissions_field, $user=null) {		
		//$user = is_null($user) ? wp_get_current_user() : (empty($user->roles) ? new WP_User($user) : $user);
		$user = is_null($user) ? wp_get_current_user() : $user;
		$user->get_role_caps();
				
		static $permission_sql = '';
		if(empty($permission_sql)) { // only generate once per request
			if(in_array('administrator',$user->roles)) $permission_sql = '1=1'; // administrator can access everything!
			elseif(WPFB_Core::$settings->private_files) {
				$permission_sql = "$owner_field = 0 OR $owner_field = " . (int)$user->ID;
			} else {
				$permission_sql = "$permissions_field = ''";
				$roles = $user->roles;
				foreach($roles as $ur) {
					$ur = esc_sql($ur);
					// assuming mysql ft_min_word_len is 4:
					$permission_sql .= (strlen($ur) < 4) ? " OR $permissions_field LIKE '%$ur|%' OR $permissions_field LIKE '%|$ur|%' OR $permissions_field LIKE '%|$ur%'"
																	 : " OR MATCH($permissions_field) AGAINST ('{$ur}' IN BOOLEAN MODE)";
				}
				if($user->ID > 0)
					$permission_sql .= " OR ($owner_field = " . (int)$user->ID . ")";
			}
		}
		return $permission_sql;
	}
}

?>