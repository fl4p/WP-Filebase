<?php

class WPFB_Admin {

	static $MIN_SIZE_FOR_PROGRESSBAR = 2097152; //2MiB

	const MAX_USERS_PER_ROLE_DISPLAY = 50;

	static function InitClass() {
		wpfb_loadclass('AdminLite', 'Item', 'File', 'Category', 'FileUtils');
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_script(WPFB . '-admin', WPFB_PLUGIN_URI . 'js/admin.js', array(), WPFB_VERSION);

		wp_enqueue_style('widgets');

		add_filter('human_time_diff', array(__CLASS__, 'HumanTimeFilter'), 10, 4);

		require_once(ABSPATH . 'wp-admin/includes/file.php');

		// make sure that either wp-filebase or wp-filebase pro is enabled bot not both!
		if (!function_exists('is_plugin_active')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if (is_plugin_active('wp-filebase-pro/wp-filebase.php'))			deactivate_plugins('wp-filebase/wp-filebase.php');

		if (!empty($_GET['action']) && $_GET['action'] === 'install-extensions')
			add_thickbox();

		if (isset($_GET['no-ob'])) {
			self::DisableOutputBuffering();
		}

	}

	static function SettingsSchema() {
		return apply_filters('wpfilebase_settings_schema', wpfb_call('Settings', 'Schema'));;
	}

	/**
	 * @param $catarr
	 * @return array
	 */
	static function InsertCategory($catarr) {
		$catarr = wp_parse_args($catarr, array(
			'cat_id' => 0,
			'cat_name' => '',
			'cat_description' => '',
			'cat_parent' => 0,
			'cat_folder' => '',
			'cat_order' => 0,
			'cat_wp_term_id' => -1));
		extract($catarr, EXTR_SKIP);
		$data = (object) $catarr;

		$cat_id = intval($data->cat_id);
		$cat_parent = intval($data->cat_parent);
		$update = ($cat_id > 0); // update or creating??
		$add_existing = !empty($add_existing);
		/* @var $cat WPFB_Category */
		$cat = $update ? WPFB_Category::GetCat($cat_id) : new WPFB_Category(array('cat_id' => 0));
		$cat->Lock(true);

		// some validation
		if (empty($cat_name) && empty($cat_folder))
			return array('error' => __('You must enter a category name or a folder name.', 'wp-filebase'));
		if (!$add_existing && !empty($cat_folder) && (!$update || $cat_folder != $cat->cat_folder)) {
			$cat_folder = preg_replace('/\s/', ' ', $cat_folder);
			if (!preg_match('/^[0-9a-z-_.+,\'\s()%]+$/i', $cat_folder))
				return array('error' => __('The category folder name contains invalid characters.', 'wp-filebase'));
		}
		wpfb_loadclass('Output');
		if (empty($cat_name))
			$cat_name = WPFB_Core::$settings->no_name_formatting ? $cat_folder : WPFB_Output::Filename2Title($cat_folder, false);
		elseif (empty($cat_folder))
			$cat_folder = strtolower(str_replace(' ', '_', $cat_name));


		$cat->cat_name = trim($cat_name);
		$cat->cat_description = trim($cat_description);
		$cat->cat_exclude_browser = (int) !empty($cat_exclude_browser);
		$cat->cat_order = 0 + ($cat_order);
		if ($data->cat_wp_term_id > -1)
			$cat->cat_wp_term_id = 0 + $data->cat_wp_term_id;


		// handle parent cat
		if ($cat_parent <= 0 || $cat_parent == $cat_id) {
			$cat_parent = 0;
			$pcat = null;
		} else {
			$pcat = WPFB_Category::GetCat($cat_parent);
			if ($pcat == null || ($update && $cat->IsAncestorOf($pcat)))
				$cat_parent = $cat->cat_parent;
		}

		if ($add_existing)
			$cat->cat_folder = $cat_folder;

		// renaming cloud synced categories not supported yet
		if($update && ($cat->cat_parent != $cat_parent || $cat->cat_folder != $cat_folder)) {
			if($cat->GetParent() && $cat->GetParent()->getCloudSync())
				return array('error' => __('Cannot change category inside a cloud sync!', 'wp-filebase'));
		}

		// this will (eventually) inherit permissions:
		$result = $cat->ChangeCategoryOrName($cat_parent, $cat_folder, $add_existing);
		if (is_array($result) && !empty($result['error']))
			return $result;

		// explicitly set permissions:
		if (!empty($data->cat_perm_explicit) && isset($data->cat_user_roles))
			$cat->SetReadPermissions((empty($data->cat_user_roles) || count(array_filter($data->cat_user_roles)) == 0) ? array() : $data->cat_user_roles);

		$current_user = wp_get_current_user();
		if (!$update && !empty($current_user))
			$cat->cat_owner = $current_user->ID;
		if (empty($cat->cat_owner))
			$cat->cat_owner = 0;

		// apply permissions to children
		if ($update && !empty($cat_child_apply_perm)) {
			$cur = $cat->GetReadPermissions();
			$childs = $cat->GetChildFiles(true);
			foreach ($childs as $child) {
				$child->SetReadPermissions($cur);
			}

			$childs = $cat->GetChildCats(true);
			foreach ($childs as $child) {
				$child->Lock(true);
				$child->SetReadPermissions($cur);
				$child->Lock(false);
				$child->DBSave();
			}
		}

		// icon
		if (!empty($cat_icon_delete)) {
			@unlink($cat->GetThumbPath());
			$cat->cat_icon = null;
		}
		if (!empty($cat_icon) && @is_uploaded_file($cat_icon['tmp_name']) && !empty($cat_icon['name'])) {
			$ext = strtolower(substr($cat_icon['name'], strrpos($cat_icon['name'], '.') + 1));
			if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
				if (!empty($cat->cat_icon))
					@unlink($cat->GetThumbPath());
				$cat->cat_icon = '_caticon.' . $ext;
				$cat_icon_dir = dirname($cat->GetThumbPath());
				if (!is_dir($cat_icon_dir))
					self::Mkdir($cat_icon_dir);
				if (!@move_uploaded_file($cat_icon['tmp_name'], $cat->GetThumbPath()))
					return array('error' => __('Unable to move category icon!', 'wp-filebase') . ' ' . $cat->GetThumbPath());
				@chmod($cat->GetThumbPath(), octdec(WPFB_PERM_FILE));
			}
		}
		elseif ($add_existing) {
			static $folder_icons = array('_caticon.jpg', '_caticon.png', '_caticon.gif', 'folder.jpg', 'folder.png', 'folder.gif', 'cover.jpg');
			$cat_path = $cat->GetLocalPath();
			foreach ($folder_icons as $fi) {
				$fi = "$cat_path/$fi";
				if (is_file($fi)) {
					$ext = strtolower(substr($fi, strrpos($fi, '.') + 1));
					$cat->cat_icon = "_caticon.$ext";
					$cat_icon_dir = dirname($cat->GetThumbPath());
					if (!is_dir($cat_icon_dir))
						self::Mkdir($cat_icon_dir);
					if (!@rename($fi, $cat->GetThumbPath()))
						return array('error' => __('Unable to move category icon!', 'wp-filebase') . ' ' . $cat->GetThumbPath());
					break;
				}
			}
		}

		// save into db
		$cat->Lock(false);
		$result = $cat->DBSave();
		if (is_array($result) && !empty($result['error']))
			return $result;
		$cat_id = 0+$result['cat_id'];
		WPFB_Category::$cache[$cat_id] = $cat;

		return array('error' => false, 'cat_id' => $cat_id, 'cat' => $cat);
	}

	private static function fileApplyMeta(&$file, &$data) {
		// set  meta
		if (!empty($data->file_languages))
			$file->file_language = implode('|', $data->file_languages);
		if (!empty($data->file_platforms))
			$file->file_platform = implode('|', $data->file_platforms);
		if (!empty($data->file_requirements))
			$file->file_requirement = implode('|', $data->file_requirements);

		if (isset($data->file_tags))
			$file->SetTags($data->file_tags);

		$file->file_offline = (int) (!empty($data->file_offline));

		if (!isset($data->file_direct_linking))
			$data->file_direct_linking = WPFB_Core::$settings->default_direct_linking;
		$file->file_direct_linking = intval($data->file_direct_linking);

		if (isset($data->file_post_id))
			$file->SetPostId(intval($data->file_post_id));

		$file->file_author = isset($data->file_author) ? $data->file_author : WPFB_Core::$settings->default_author;

		$var_names = array('file_remote_uri', 'file_description', 'file_hits', 'file_license'
		);
		for ($i = 0; $i < count($var_names); $i++) {
			$vn = $var_names[$i];
			if (isset($data->$vn))
				$file->$vn = $data->$vn;
		}

		// custom fields!
		$custom_defaults = array();
		$var_names = array_keys(WPFB_Core::GetCustomFields(true, $custom_defaults));
		for ($i = 0; $i < count($var_names); $i++) {
			$vn = $var_names[$i];
			$file->$vn = isset($data->$vn) ? $data->$vn : $custom_defaults[$vn];
		}


	}

	static function InsertFile($data, $in_gui = false) {
		if (!is_object($data))
			$data = (object) $data;

		$file_id = isset($data->file_id) ? (int) $data->file_id : 0;
		$file = null;
		if ($file_id > 0) {
			$file = WPFB_File::GetFile($file_id);
			if ($file == null)
				$file_id = 0;
		}
		$update = ($file_id > 0 && $file != null && $file->is_file);
		if (!$update)
			$file = new WPFB_File(array('file_id' => 0));
		$file->Lock(true);
		$add_existing = !empty($data->add_existing); // if the file is added by a sync (not uploaded)

		if (!$add_existing)
			self::SyncCustomFields();  // dont sync custom fields when file syncing!

		if (!empty($data->file_flash_upload)) { // check for flash upload and validate!
			$file_flash_upload = json_decode($data->file_flash_upload, true);
			$file_flash_upload['tmp_name'] = WPFB_Core::UploadDir() . '/' . str_replace('../', '', $file_flash_upload['tmp_name']);
			if (is_file($file_flash_upload['tmp_name']))
				$data->file_upload = $file_flash_upload;
		}
		// are we uploading a file?
		$upload = (!$add_existing && ((@is_uploaded_file($data->file_upload['tmp_name']) || !empty($data->file_flash_upload)) && !empty($data->file_upload['name'])));
		$remote_upload = (!$add_existing && !$upload && !empty($data->file_remote_uri) && (!$update || $file->file_remote_uri != $data->file_remote_uri));
		$remote_redirect = !empty($data->file_remote_redirect) && !empty($data->file_remote_uri);
		if ($remote_redirect) {
			$remote_scan = !empty($data->file_remote_scan);
		}

		// if we change the actual file on disk
		$change = $upload || $remote_upload;

		if ($update && ($change || (!empty($data->file_rename) && $data->file_rename != $file->file_name)) && $file->IsScanLocked()) {
			return array('error' => sprintf(__('%s is currently locked. Please try again in %s.', 'wp-filebase'), $file, human_time_diff(time(), $file->file_scan_lock)));
		}

		// are we uploading a thumbnail?
		$upload_thumb = (!$add_existing && @is_uploaded_file($data->file_upload_thumb['tmp_name']));

		if ($upload_thumb && !(WPFB_FileUtils::FileHasImageExt($data->file_upload_thumb['name']) && WPFB_FileUtils::IsValidImage($data->file_upload_thumb['tmp_name'])))
			return array('error' => __('Thumbnail is not a valid image!.', 'wp-filebase'));

		if ($remote_upload) {
			unset($file_src_path);
			$remote_file_info = self::GetRemoteFileInfo($data->file_remote_uri);
			if (is_wp_error($remote_file_info))
				return array('error' => sprintf(__('Could not get file information from %s!', 'wp-filebase'), $data->file_remote_uri) . ' (' . $remote_file_info->get_error_message() . ')');
			$file_name = $remote_file_info['name'];
			if ($remote_file_info['size'] > 0)
				$file->file_size = $remote_file_info['size'];
			if ($remote_file_info['time'] > 0)
				$file->SetModifiedTime($remote_file_info['time']);
		} else {
			$file_src_path = $upload ? $data->file_upload['tmp_name'] : ($add_existing ? $data->file_path : null);
			$file_name = $upload ? str_replace('\\', '', $data->file_upload['name']) : ((empty($file_src_path) && $update) ? $file->file_name : substr(strrchr (str_replace('\\','/',$file_src_path),'/'),1)); // no basename here!
		}


		if ($file->IsRemote())
			$data->file_rename = null;


		// VALIDATION
		$current_user = wp_get_current_user();
		if (empty($data->frontend_upload) && !$add_existing && empty($current_user->ID))
			return array('error' => __('Could not get user id!', 'wp-filebase'));

		if (!$update && !$add_existing && !$upload && !$remote_upload)
			return array('error' => __('No file was uploaded.', 'wp-filebase'));

		// check extension
		if ($upload || $add_existing) {
			if (!self::IsAllowedFileExt($file_name)) {
				if (isset($file_src_path))
					@unlink($file_src_path);
				return array('error' => sprintf(__('The file extension of the file <b>%s</b> is forbidden!', 'wp-filebase'), $file_name));
			}
		}
		// check url
		if ($remote_upload && !preg_match('/^(https?|file):\/\//', $data->file_remote_uri))
			return array('error' => __('Only HTTP links are supported.', 'wp-filebase'));


		// do some simple file stuff
		if ($update && (!empty($data->file_delete_thumb) || $upload_thumb))
			$file->DeleteThumbnail(); // delete thumbnail if user wants to	
		if ($update && ($upload || $remote_upload))
			$file->Delete(true); // if we update, delete the old file (keep thumb!)



			
// handle display name and version
		if (isset($data->file_version))
			$file->file_version = $data->file_version;
		if (isset($data->file_display_name))
			$file->file_display_name = $data->file_display_name;
		$result = self::ParseFileNameVersion($file_name, $file->file_version);
		if (empty($file->file_version))
			$file->file_version = $result['version'];
		if (empty($file->file_display_name))
			$file->file_display_name = $result['title'];

		// handle category & name
		$file_category = isset($data->file_category) ? (is_object($data->file_category) ? $data->file_category->GetId() : (0 + $data->file_category)) : 0;
		$new_cat = null;
		if ($file_category > 0 && ($new_cat = WPFB_Category::GetCat($file_category)) == null)
			$file_category = 0;

		if(!$update || !$file->IsCloudHosted()) {
			// this inherits permissions as well:
			$result = $file->ChangeCategoryOrName($file_category,
				empty($data->file_rename) ? $file_name : $data->file_rename,
				$add_existing, ! empty($data->overwrite));
			if (is_array($result) && ! empty($result['error'])) {
				return $result;
			}
		}

		$prev_read_perms = $file->file_offline ? array('administrator') : $file->GetReadPermissions();
		// explicitly set permissions:
		if (!empty($data->file_perm_explicit) && isset($data->file_user_roles))
			$file->SetReadPermissions((empty($data->file_user_roles) || count(array_filter($data->file_user_roles)) == 0) ? array() : $data->file_user_roles);

		// if there is an uploaded file 
		if ($upload) {
			$file_dest_path = $file->GetLocalPath();
			$file_dest_dir = dirname($file_dest_path);
			if (@file_exists($file_dest_path))
				return array('error' => sprintf(__('File %s already exists. You have to delete it first!', 'wp-filebase'), $file->GetLocalPath()));
			if (!is_dir($file_dest_dir))
				self::Mkdir($file_dest_dir);
			// try both move_uploaded_file for http, rename for flash uploads!
			if (!(move_uploaded_file($file_src_path, $file_dest_path) || rename($file_src_path, $file_dest_path)) || !@file_exists($file_dest_path))
				return array('error' => sprintf(__('Unable to move file %s! Is the upload directory writeable?', 'wp-filebase'), $file->file_name) . ' ' . $file->GetLocalPathRel());
		} elseif ($remote_upload) {
			if (!$remote_redirect || $remote_scan) {
				$tmp_file = self::GetTmpFile($file->file_name);
				$result = self::SideloadFile($data->file_remote_uri, $tmp_file, $in_gui ? $remote_file_info['size'] : -1);
				if (is_array($result) && !empty($result['error']))
					return $result;
				if (!rename($tmp_file, $file->GetLocalPath()))
					return array('error' => "Could not rename temp file $tmp_file -> {$file->GetLocalPath()} !");
				if(!$remote_redirect)
					$data->file_remote_uri = '';
			}
		} elseif (!$add_existing && !$update) {
			return array('error' => __('No file was uploaded.', 'wp-filebase'));
		}

		// handle date/time stuff
		if (!empty($data->file_date)) {
			$file->file_date = $data->file_date;
		} elseif ($add_existing || empty($file->file_date)) {
			$file->file_date = file_exists($file->GetLocalPath())
					  ? gmdate('Y-m-d H:i:s', min(filemtime($file->GetLocalPath()),time()) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ))
					  : current_time('mysql');
		}

		if (!$update) {
			// since 4.4, wpdb will acutally set NULL values, so make sure everything is an empty string
			$file->file_hash = '';
			$file->file_remote_uri = '';

			$file->file_tags = '';
			$file->file_license = '';
			$file->file_password = '';
			$file->file_last_dl_ip = '';

			$file->file_added_by = empty($current_user) ? 0 : $current_user->ID;
		}

		// set owner
		if(!empty($data->file_added_by)) {
			$user = get_user_by('login', $data->file_added_by);
			if($user && $user->exists()) {
				$file->file_added_by = $user->ID;
			}
		}

		self::fileApplyMeta($file, $data);

		// save into db
		$file->Lock(false);
		$result = $file->DBSave();
		if (is_array($result) && !empty($result['error']))
			return $result;
		$file_id = (int) $result['file_id'];

		if (!$update) { // on new file, remove any existing data
			global $wpdb;
			$wpdb->query("DELETE FROM $wpdb->wpfilebase_files_id3 WHERE file_id = $file_id");
		}

		if (!empty($data->no_scan) && !empty($data->add_rsync)) {
			$file->file_rescan_pending = 1;
		}
			
		// get file info
		if ((!$update || !$remote_redirect) && is_file($file->GetLocalPath())) {
			$old_size = $file->file_size;
			$old_mtime = $file->file_mtime;
			$old_hash = $file->file_hash;

			$file->file_size = isset($data->file_size) ? $data->file_size : WPFB_FileUtils::GetFileSize($file->GetLocalPath());
			$file->file_mtime = filemtime($file->GetLocalPath());
			$size_or_mtime_changed = ($old_size != $file->file_size || $old_mtime != $file->file_mtime);

			$file->file_hash = empty($data->no_scan) ? WPFB_Admin::GetFileHash($file->GetLocalPath()) : ($size_or_mtime_changed ? '' : $old_hash);

			// TODO: revise conditions / make more readable
			if (!empty($data->no_scan) && ($upload || $add_existing || $size_or_mtime_changed)) {
				$file->file_rescan_pending = 1;
			}
			// only analyze files if changedAdd
			elseif (empty($data->no_scan) && ($upload || !$update || $file->file_hash != $old_hash)) {
				wpfb_loadclass('Sync');
				WPFB_Sync::ScanFile($file, false, !$remote_redirect ); // dont do async scan if temporary file
			}
		} else {
			if (isset($data->file_size))
				$file->file_size = $data->file_size;
			if (isset($data->file_hash))
				$file->file_hash = $data->file_hash;
		}

		if ($remote_redirect) {
			if (file_exists($file->GetLocalPath()))
				@unlink($file->GetLocalPath()); // when download redircet the actual files is not needed anymore
		} else {
			// set permissions
			is_file($file->GetLocalPath()) && @chmod($file->GetLocalPath(), octdec(WPFB_PERM_FILE));
			$file->file_remote_uri = $data->file_remote_uri = ''; // no redirection, URI is not neede anymore		
		}

		// handle thumbnail
		if ($upload_thumb) {
			$file->DeleteThumbnail(); // delete the old thumbnail (if existing)
			$thumb_dest_path = dirname($file->GetLocalPath()) . '/thumb_' . $data->file_upload_thumb['name'];
			if (@move_uploaded_file($data->file_upload_thumb['tmp_name'], $thumb_dest_path)) {
				$file->CreateThumbnail($thumb_dest_path, true);
			}
		} else if ($upload || $remote_upload || $add_existing) {
			if ($add_existing && !empty($data->file_thumbnail)) {
				$file->file_thumbnail = $data->file_thumbnail; // we already got the thumbnail on disk!		
			} elseif (empty($file->file_thumbnail) && !$upload_thumb && (!$remote_redirect || $remote_scan) && empty($data->no_scan)) {
				// WPFB_Sync::ScanFile should've done this, this may never be reached
				$file->CreateThumbnail(); // check if the file is an image and create thumbnail
			}
		}

		do_action($update ? 'wpfilebase_file_updated' : 'wpfilebase_file_added', $file, $data);
		do_action('wpfilebase_file_edited', $file, $data);

		// save into db again
		$result = $file->DBSave();
		if (is_array($result) && !empty($result['error']))
			return $result;

		return array('error' => false, 'file_id' => $file_id, 'file' => $file);
	}

	static function ParseFileNameVersion($file_name, $file_version = null) {
		$fnwv = substr($file_name, 0, strrpos($file_name, '.')); // remove extension
		if (empty($file_version)) {
			$matches = array();
			/* match github-style files */
			if(preg_match('/^(.+?)-v?([0-9]{1,3}\.[0-9]{1,3}[-\.0-9a-zA-Z]*)-0-[0-9a-z]+$/', $fnwv, $matches))
			{
				$fnwv = $matches[1];
				$file_version = $matches[2];
			}
			elseif (preg_match('/[-_\.]v?([0-9]{1,3}\.[0-9]{1,3}(\.[0-9]{1,3}){0,2})(-[a-zA-Z_0-9]+)?$/', $fnwv, $matches)
				&& !preg_match('/^[\.0-9]+-[\.0-9]+$/', $fnwv)) { // FIX: don't extract ver from 01.01.01-01.02.03.mp3
				$file_version = $matches[1];
				if ((strlen($fnwv) - strlen($matches[0])) > 1)
					$fnwv = substr($fnwv, 0, -strlen($matches[0]));
			}
		} elseif (substr($fnwv, -strlen($file_version)) == $file_version) {
			$fnwv = trim(substr($fnwv, 0, -strlen($file_version)), '-');
		}
		$title = WPFB_Core::$settings->no_name_formatting ? $fnwv : wpfb_call('Output', 'Filename2Title', array($fnwv, false), true);
		return array('title' => empty($title) ? $file_name : $title, 'version' => $file_version);
	}

// size, type, name, time, etag
	static function GetRemoteFileInfo($url, $follow_redirects=5) {
		wpfb_loadclass('Download');

		if (parse_url($url, PHP_URL_SCHEME) === 'file' && is_readable($url)) {
			return array(
				 'name' => basename($url),
				 'size' => filesize($url),
				 'type' => WPFB_Download::GetFileType($url),
				 'time' => filemtime($url)
			);
		}

		$info = array();
		$path = parse_url($url, PHP_URL_PATH);

		require_once( ABSPATH . WPINC . "/http.php" );

		$response = wp_remote_head($url, array_merge(array('timeout' => 10)));
		if (is_wp_error($response))
			return $response;

		$headers = wp_remote_retrieve_headers($response);
		if (empty($headers))
			return new WP_Error('get_remote_file_info', 'Headers not set!');

		if($response['response']['code'] >= 300 && $response['response']['code'] <= 399) {
			if(empty($headers['location']))
				return new WP_Error('get_remote_file_info', "HTTP {$response['code']} but no location header!");
				
			if($follow_redirects > 0)
				return self::GetRemoteFileInfo($headers['location'], $follow_redirects - 1);
			
			$info['location'] = $headers['location'];
		}
 
		$info['size'] = isset($headers['content-length']) ? $headers['content-length'] : -1;
		$info['type'] = isset($headers['content-type']) ? strtolower($headers['content-type']) : null;
		if(($p = strpos($info['type'], ';')) > 0) $info['type'] = substr($info['type'], 0, $p);
		$info['time'] = isset($headers['last-modified']) ? @strtotime($headers['last-modified']) : 0;
		$info['etag'] = isset($headers['etag']) ? trim($headers['etag'],'"') : '';

		$info['_response'] = $response;
		
		// check for filename header
		if (!empty($headers['content-disposition'])) {
			$matches = array();
			if (preg_match('/filename="?([^"]+)"?/', $headers['content-disposition'], $matches) == 1)
				$info['name'] = $matches[1];
		}

		if (empty($info['name']))
			$info['name'] = basename($path);

		// compare extension type with http header content-type, if they are different deterime proper extension from http content-type
		$exType = WPFB_Download::GetFileType($info['name']);
		if ($exType != $info['type'] && ($e = WPFB_Download::FileType2Ext($info['type'])) != null)
			$info['name'] .= '.' . $e;

		return $info;
	}

	/**
	 * @param string|WPFB_File    $url
	 * @param null $dest_file
	 * @param int  $size_for_progress
	 *
	 * @return array
	 */
	public static function SideloadFile($url, $dest_file = null, $size_for_progress = 0) {
		if(is_object($url)) {
			$file = $url;
			try { $url = $file->GetRemoteUri(false); }
			catch(Exception $e) {
				return array('error' => "Could not get URL of file $file: {$e->getMessage()}");
			}
		}


		//WARNING: The file is not automatically deleted, The script must unlink() the file.
		@ini_set('max_execution_time', '0');
		@set_time_limit(0);
		require_once(ABSPATH . 'wp-admin/includes/file.php');

		if (!$url)
			return array('error' => __('Invalid URL Provided.'));

		if (empty($dest_file)) { // if no dest file set, create temp file
			$fi = self::GetRemoteFileInfo($url);
			if (is_wp_error($fi))
				return array('error' => sprintf(__('Could not get file information from %s!', 'wp-filebase'), $url) . ' (' . $fi->get_error_message() . ')');
			if (!($dest_file = self::GetTmpFile($fi['name'])))
				return array('error' => __('Could not create Temporary file.'));
		}

		if ($size_for_progress >= self::$MIN_SIZE_FOR_PROGRESSBAR) {
			if (!class_exists('progressbar'))
				include_once(WPFB_PLUGIN_ROOT . 'extras/progressbar.class.php');
			$progress_bar = new progressbar(0, $size_for_progress, 300, 30, '#aaa');
			echo "<p><code>" . esc_html($url) . "</code> ...</p>";
			$progress_bar->print_code();
		} else
			$progress_bar = null;

		wpfb_loadclass('Download');
		$result = WPFB_Download::SideloadFile($url, $dest_file, $progress_bar);
		if (is_array($result) && !empty($result['error']))
			return $result;

		return array('error' => false, 'file' => $dest_file);
	}

	/**
	 * @param $file_path string Absolute path
	 * @return array|int
	 */
	static function CreateCatTree($file_path) {
		$rel_path = trim(substr($file_path, strlen(WPFB_Core::UploadDir())), '/');
		$rel_dir = dirname($rel_path);

		if (empty($rel_dir) || $rel_dir == '.')
			return 0;

		$last_cat_id = 0;
		$dirs = explode('/', $rel_dir);
		foreach ($dirs as $dir) {
			if (empty($dir) || $dir == '.')
				continue;
			$cat = WPFB_Item::GetByName($dir, $last_cat_id);
			if ($cat != null && $cat->is_category) {
				$last_cat_id = $cat->cat_id;
			} else {
				$result = self::InsertCategory(array('add_existing' => true, 'cat_parent' => $last_cat_id, 'cat_folder' => $dir));
				if (is_array($result) && !empty($result['error']))
					return $result;
				elseif (empty($result['cat_id']))
					wp_die('Could not create category!');
				else
					$last_cat_id = intval($result['cat_id']);
			}
		}
		return $last_cat_id;
	}

	static function AddExistingFile($file_path, $thumb = null, $presets = null) {
		$cat_id = self::CreateCatTree($file_path);

		if (is_array($cat_id) && !empty($cat_id['error']))
			return $cat_id;

		// check if file still exists (it could be renamed while creating the category if its used for category icon!)
		if (!is_file($file_path))
			return array();

		if (empty($presets) || !is_array($presets))
			$presets = array();
		else
			WPFB_Admin::AdaptPresets($presets);

		return self::InsertFile(array_merge($presets, array(
						'add_existing' => true,
						'file_category' => $cat_id,
						'file_path' => $file_path,
						'file_thumbnail' => $thumb,
								)));
	}

	static function WPCacheRejectUri($add_uri, $remove_uri = '') {
		// changes the settings of wp cache

		global $cache_rejected_uri;

		$added = false;

		if (!isset($cache_rejected_uri))
			return false;

		// remove uri
		if (!empty($remove_uri)) {
			$new_cache_rejected_uri = array();

			foreach ($cache_rejected_uri as $i => $v) {
				if ($v != $remove_uri)
					$new_cache_rejected_uri[$i] = $v;
			}

			$cache_rejected_uri = $new_cache_rejected_uri;
		}

		if (!in_array($add_uri, $cache_rejected_uri)) {
			$cache_rejected_uri[] = $add_uri;
			$added = true;
		}

		return (self::WPCacheSaveRejectedUri() && $added);
	}

	static function WPCacheSaveRejectedUri() {
		global $cache_rejected_uri, $wp_cache_config_file;

		if (!isset($cache_rejected_uri) || empty($wp_cache_config_file) || !function_exists('wp_cache_replace_line'))
			return false;

		$text = var_export($cache_rejected_uri, true);
		$text = preg_replace('/[\s]+/', ' ', $text);
		wp_cache_replace_line('^ *\$cache_rejected_uri', "\$cache_rejected_uri = $text;", $wp_cache_config_file);

		return true;
	}

	static function MakeFormOptsList($opt_name, $selected = null, $add_empty_opt = false) {
		$options = WPFB_Core::GetOpt($opt_name);
		$options = explode("\n", $options);
		$def_sel = (is_null($selected) && !is_string($selected));
		$list = $add_empty_opt ? ('<option value=""' . ( (is_string($selected) && $selected == '') ? ' selected="selected"' : '') . '>-</option>') : '';
		$selected = explode('|', $selected);

		foreach ($options as $opt) {
			$opt = trim($opt);
			if (count($tmp = explode('|', $opt)) >= 2)
				$list .= '<option value="' . esc_attr(trim($tmp[1])) . '"' . ( (($def_sel && $opt{0} == '*') || (!$def_sel && in_array($tmp[1], $selected)) ) ? ' selected="selected"' : '' ) . '>' . esc_html(trim($tmp[0], '*')) . '</option>';
		}

		return $list;
	}

	static function AdminTableSortLink($order) {
		$desc = (!empty($_GET['order']) && $order == $_GET['order'] && empty($_GET['desc']));
		$uri = add_query_arg(array('order' => $order, 'desc' => $desc ? '1' : '0'));
		return $uri;
	}

	static function IsAllowedFileExt($ext) {
		static $srv_script_exts = array('php', 'php3', 'php4', 'php5', 'phtml', 'cgi', 'pl', 'asp', 'py', 'aspx', 'jsp', 'jhtml', 'jhtm');

		if (WPFB_Core::$settings->allow_srv_script_upload)
			return true;

		$ext = strtolower($ext);
		$p = strrpos($ext, '.');
		if ($p !== false)
			$ext = substr($ext, $p + 1);

		return !in_array($ext, $srv_script_exts);
	}

	static function UninstallPlugin() {
		wpfb_loadclass('Setup');
		WPFB_Setup::RemoveOptions();
		WPFB_Setup::DropDBTables();
		// TODO: remove user opt
	}

	static function PrintForm($name, $item = null, $vars = array()) {
		wpfb_call('Output', 'PrintJS');
		?>
		<script type="text/javascript">
		//<![CDATA[

			jQuery(document).ready(function ($) {
				WPFB_formCategoryChanged();
			});

			function WPFB_formCategoryChanged()
			{
				var catId = jQuery('#file_category,#cat_parent').val();
				if (!catId || catId <= 0) {
					jQuery('#<?php echo $name ?>_inherited_permissions_label').html('<?php echo WPFB_Output::RoleNames(WPFB_Core::$settings->default_roles, true); ?>');
				} else {
					jQuery.ajax({
						url: wpfbConf.ajurl,
						data: {wpfb_action: "catinfo", id: catId},
						success: (function (data) {
							jQuery('#<?php echo $name ?>_inherited_permissions_label').html(data.roles_str);
						})
					});
				}
			}
		//]]>
		</script>
		<?php
		extract($vars);
		if (is_writable(WPFB_Core::UploadDir()))
			include(WPFB_PLUGIN_ROOT . 'lib/wpfb_form_' . $name . '.php');
	}

// creates the folder structure
	static function Mkdir($dir) {
		$parent = trim(dirname($dir), '.');
		if (trim($parent, '/\\') != '' && !is_dir($parent)) {
			$result = self::Mkdir($parent);
			if ($result['error'])
				return $result;
		}
		return array('error' => !(@mkdir($dir, octdec(WPFB_PERM_DIR)) && @chmod($dir, octdec(WPFB_PERM_DIR))), 'dir' => $dir, 'parent' => $parent);
	}

	static function ParseTpls() {
		wpfb_loadclass('TplLib');

		// parse default
		WPFB_Core::UpdateOption('template_file_parsed', WPFB_TplLib::Parse(WPFB_Core::$settings->template_file));
		WPFB_Core::UpdateOption('template_cat_parsed', WPFB_TplLib::Parse(WPFB_Core::$settings->template_cat));

		// parse custom
		update_option(WPFB_OPT_NAME . '_ptpls_file', WPFB_TplLib::Parse(WPFB_Core::GetFileTpls()));
		update_option(WPFB_OPT_NAME . '_ptpls_cat', WPFB_TplLib::Parse(WPFB_Core::GetCatTpls()));
	}

	static function PrintPayPalButton() {
		$lang = 'en_US';
		$supported_langs = array('en_US', 'de_DE', 'fr_FR', 'es_ES', 'it_IT', 'ja_JP', 'pl_PL', 'nl_NL');

		/*
		 * fr_FR/FR
		 * https://www.paypalobjects.com/WEBSCR-640-20110401-1/en_US/FR/i/btn/btn_donateCC_LG.gif
		 * https://www.paypalobjects.com/WEBSCR-640-20110401-1/de_DE/DE/i/btn/btn_donateCC_LG.gif
		 * https://www.paypalobjects.com/WEBSCR-640-20110401-1/es_ES/ES/i/btn/btn_donateCC_LG.gif
		 * https://www.paypalobjects.com/WEBSCR-640-20110401-1/it_IT/i/btn/btn_donateCC_LG.gif
		 */

		// find out current language for the donate btn
		if (defined('WPLANG') && WPLANG && WPLANG != '' && strpos(WPLANG, '_') > 0) {
			if (in_array(WPLANG, $supported_langs))
				$lang = WPLANG;
			else {
				$l = strtolower(substr(WPLANG, 0, strpos(WPLANG, '_')));
				if (!empty($l)) {
					foreach ($supported_langs as $sl) {
						$pos = strpos($sl, $l);
						if ($pos !== false && $pos == 0) {
							$lang = $sl;
						}
					}
				}
			}
		}
		?>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick" />
			<input type="hidden" name="hosted_button_id" value="AF6TBLTYLUMD2" />
			<!-- <input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/<?php echo $lang ?>/i/btn/btn_donateCC_LG.gif" style="border:none;" name="submit" alt="PayPal - The safer, easier way to pay online!" /> -->
			<input type="image" src="https://www.paypal.com/<?php echo $lang ?>/i/btn/btn_donateCC_LG.gif" style="border:none;" name="submit" alt="PayPal - The safer, easier way to pay online!" title="PayPal - The safer, easier way to pay online!" />
			<!-- <img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110401-1/<?php echo $lang ?>/i/scr/pixel.gif" width="1" height="1" /> -->
			<img alt="" border="0" src="https://www.paypal.com/<?php echo $lang ?>/i/scr/pixel.gif" width="1" height="1" />

		</form>
		<?php
	}

	static function PrintFlattrHead() {
		?>
		<script type="text/javascript">
			/* <![CDATA[ */
			(function () {
				var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
				s.type = 'text/javascript';
				s.async = true;
				s.src = 'http://api.flattr.com/js/0.6/load.js?mode=auto';
				t.parentNode.insertBefore(s, t);
			})();
			/* ]]> */
		</script>
		<?php
	}

	static function PrintFlattrButton() {
		?>
		<p style="text-align: center;">
			<a class="FlattrButton" style="display:none;" href="http://wordpress.org/extend/plugins/wp-filebase/"></a>
		</p>
		<noscript><p style="text-align: center;"><a href="http://flattr.com/thing/157167/WP-Filebase" target="_blank">
				<img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a></p></noscript>
		<?php
	}

// this is used for post filter

	public static function ProcessWidgetUpload() {
		$content = '';
		$title = '';

			if (!WPFB_Core::$settings->frontend_upload && !current_user_can('upload_files'))
				wp_die(__('Cheatin&#8217; uh?') . " (frontend uploads disabled)");

		{
			$form = null;
			$nonce_action = $_POST['prefix'] . "=&cat=" . ((int) $_POST['cat']) . "&overwrite=" . ((int) $_POST['overwrite']) . "&file_post_id=" . ((int) $_POST['file_post_id']);
			// nonce/referer check (security)
			if (!check_admin_referer($nonce_action, 'wpfb-file-nonce'))
				wp_die(__('Cheatin&#8217; uh?') . ' (security)');
		}

		// if category is set in widget options, force to use this. security done with nonce checking ($_POST['cat'] is reliable)
		if ($_POST['cat'] >= 0)
			$_POST['file_category'] = $_POST['cat'];
		$result = WPFB_Admin::InsertFile(stripslashes_deep(array_merge($_POST, $_FILES, array('frontend_upload' => true, 'form' => empty($form) ? null : $form))));
		if (isset($result['error']) && $result['error']) {
			$content .= '<div id="message" class="updated fade"><p>' . $result['error'] . '</p></div>';
			$title .= __('Error');
		} else {
			// success!!!!
			$file = WPFB_File::GetFile($result['file_id']);
			$title = trim(__('File added.', 'wp-filebase'), '.');

			$content = __('The File has been uploaded successfully.', 'wp-filebase') . $file->GenTpl2();
		}

		wpfb_loadclass('Output');
		WPFB_Output::GeneratePage($title, $content, !empty($_POST['form_tag'])); // prepend to content if embedded form!
	}

	public static function ProcessWidgetAddCat() {
		$content = '';
		$title = '';

			if (!WPFB_Core::$settings->frontend_upload && !current_user_can('upload_files'))
				wp_die(__('Cheatin&#8217; uh?') . " (frontend uploads disabled)");

		// nonce/referer check (security)
		$nonce_action = $_POST['prefix'];
		if (!check_admin_referer($nonce_action, 'wpfb-cat-nonce'))
			wp_die(__('Cheatin&#8217; uh?'));

		$result = WPFB_Admin::InsertCategory(array_merge(stripslashes_deep($_POST), $_FILES));
		if (isset($result['error']) && $result['error']) {
			$content .= '<div id="message" class="updated fade"><p>' . $result['error'] . '</p></div>';
			$title .= __('Error ');
		} else {
			// success!!!!
			$content = __('New Category created.', 'wp-filebase');
			$cat = WPFB_Category::GetCat($result['cat_id']);
			$content .= $cat->GenTpl2();
			$title = trim(__('Category added.', 'wp-filebase'), '.');
		}

		wpfb_loadclass('Output');
		WPFB_Output::GeneratePage($title, $content);
	}

	/**
	 * 
	 * @global wpdb $wpdb
	 * @param array $cond
	 * @return int
	 */
	public static function SetFileRescanPending($re_thumb=false) {
		global $wpdb;
		return $wpdb->query($wpdb->prepare(
										"UPDATE `$wpdb->wpfilebase_files` "
										. "SET `file_rescan_pending` = %d, `file_scan_lock` = 0 "
										. "WHERE `file_rescan_pending` < %d AND `file_scan_lock` < %d", $re_thumb ? 2 : 1, $re_thumb ? 2 : 1, time())
		);
	}

	public static function SyncCustomFields($remove = false) {
		global $wpdb;

		// only once per request!
		static $synced = false;
		if ($synced)
			return array();
		$synced = true;

		$messages = array();

		$cols = $wpdb->get_col("SHOW COLUMNS FROM $wpdb->wpfilebase_files LIKE 'file_custom_%'");

		$custom_fields = WPFB_Core::GetCustomFields();
		foreach ($custom_fields as $ct => $cn) {
			if (!in_array('file_custom_' . $ct, $cols)) {
				$messages[] = sprintf($wpdb->query("ALTER TABLE $wpdb->wpfilebase_files ADD `file_custom_" . esc_sql($ct) . "` TEXT NOT NULL") ?
									 __('Custom field \'%s\' added.', 'wp-filebase') : __('Could not add custom field \'%s\'!', 'wp-filebase'), $cn);
			}
		}

		if (!$remove) {
			foreach ($cols as $cf) {
				$ct = substr($cf, 12); // len(file_custom_)
				if (!isset($custom_fields[$ct]))
					$messages[] = sprintf($wpdb->query("ALTER TABLE $wpdb->wpfilebase_files DROP `$cf`") ?
										 __('Custom field \'%s\' removed!', 'wp-filebase') : __('Could not remove custom field \'%s\'!', 'wp-filebase'), $ct);
			}
		}

		return $messages;
	}

	public static function SettingsUpdated($old, &$new) {
		$messages = array();
		wpfb_call('Setup', 'ProtectUploadPath');

		// custom fields:
		$messages = array_merge($messages, WPFB_Admin::SyncCustomFields());

		if ($old['thumbnail_path'] != $new['thumbnail_path']) {

			update_option(WPFB_OPT_NAME, $old); // temporaly restore old settings
			WPFB_Core::$settings = (object) $old;

			$items = array_merge(WPFB_File::GetFiles2(), WPFB_Category::GetCats());
			$old_thumbs = array();
			foreach ($items as $i => $item)
				$old_thumbs[$i] = $item->GetThumbPath(true);

			update_option(WPFB_OPT_NAME, $new); // restore new settings
			WPFB_Core::$settings = (object) $new;

			$n = 0;
			foreach ($items as $i => $item) {
				if (!empty($old_thumbs[$i]) && is_file($old_thumbs[$i])) {
					$new_path = $item->GetThumbPath(true);
					$dir = dirname($new_path);
					if (!is_dir($dir))
						self::Mkdir($dir);
					if (rename($old_thumbs[$i], $new_path))
						$n++;
					else
						$messages[] = sprintf(__('Could not move thumnail %s to %s.', 'wp-filebase'), $old_thumbs[$i], $new_path);
				}
			}

			if (count($n > 0))
				$messages[] = sprintf(__('%d Thumbnails moved.', 'wp-filebase'), $n);
		}

		flush_rewrite_rules();


		wp_clear_scheduled_hook(WPFB.'_cron');
		wp_schedule_event(time()+10, 'hourly', WPFB.'_cron');

		return $messages;
	}

	static function UserSelector($field_name, $selected_user = null, $noone_label = false) {
		self::RolesCheckList($field_name, empty($selected_user) ? array() : array('_u_' . $selected_user), $noone_label, true);
	}

	static function RolesCheckList($field_name, $selected_roles = array(), $display_everyone = true, $user_select = false) {
		global $wp_roles;
		if (!$user_select) {
			$all_roles = $wp_roles->roles;
			if (empty($selected_roles))
				$selected_roles = array();
			elseif (!is_array($selected_roles))
				$selected_roles = explode('|', $selected_roles);
			?>
			<div id="<?php echo $field_name; ?>-wrap" class=""><input value="" type="hidden" name="<?php echo $field_name; ?>[]" />
				<ul id="<?php echo $field_name; ?>-list" class="wpfilebase-roles-checklist">
					<?php
					if (!empty($display_everyone))
						echo "<li id='{$field_name}_none'><label class='selectit'><input value='' type='checkbox' name='{$field_name}[]' id='in-{$field_name}_none' " . (empty($selected_roles) ? "checked='checked'" : "") . " onchange=\"jQuery('[id^=in-$field_name-]').prop('checked', false);\" /> <i>" . (is_string($display_everyone) ? $display_everyone : __('Everyone', 'wp-filebase')) . "</i></label></li>";
					foreach ($all_roles as $role => $details) {
						$name = translate_user_role($details['name']);
						$sel = in_array($role, $selected_roles);
						echo "<li id='$field_name-$role'><label class='selectit'><input value='$role' type='checkbox' name='{$field_name}[]' id='in-$field_name-$role' " . ($sel ? "checked='checked'" : "") . /* " ".((empty($selected_roles)&&$display_everyone)? "disabled='disabled'":""). */ " /> $name</label></li>";
						if ($sel)
							unset($selected_roles[array_search($role, $selected_roles)]); // rm role from array
					}
				}
					// other roles/users, that were not listed
					foreach ($selected_roles as $role) {
						$name = substr($role, 0, 3) == '_u_' ? (substr($role, 3) . ' (user)') : $role;
						echo "<li id='$field_name-$role'><label class='selectit'><input value='$role' type='$inp_type' name='{$field_name}[]' id='in-$field_name-$role' checked='checked' /> $name</label></li>";
					}
					?>
			</ul>

			

			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready(function ($) {
					jQuery('#<?php echo $field_name; ?>-list input[value!=""]').change(function () {
						jQuery('#<?php echo "in-{$field_name}_none"; ?>').prop('checked', false);
					});
				});
				//]]>
			</script>
		</div>
		<?php
	}

	static function GetTmpFile($name = '') {
		$dir = WPFB_Core::UploadDir() . '/.tmp/';
		self::Mkdir($dir);
		return wp_tempnam($name, $dir);
	}

	static function GetTmpPath($name) {
		$dir = WPFB_Core::UploadDir() . '/.tmp/' . uniqid($name);
		self::Mkdir($dir);
		return $dir;
	}

	static function LockUploadDir($lock = true) {
		$f = WPFB_Core::UploadDir() . '/.lock';
		return $lock ? touch($f) : @unlink($f);
	}

	static function UploadDirIsLocked() {
		$f = WPFB_Core::UploadDir() . '/.lock';
		return file_exists($f) && ( (time() - filemtime($f)) < 120 ); // max lock for 120 seconds without update!
	}

	static function FuncIsDisabled($name) {
		static $dfs = false;
		if($dfs === false) {
			$dfs = ','.@ini_get('disable_functions') . ',' . @ini_get('suhosin.executor.func.blacklist') . ',';
		}
		return strpos($dfs, ','.$name.',') !== false;
	}

	static function GetFileHash($filename) {
		static $use_php_func = -1;
		if (WPFB_Core::$settings->fake_md5)
			return '#' . substr(md5(filesize($filename) . "-" . filemtime($filename)), 1);
		if ($use_php_func === -1) {
			$use_php_func = self::FuncIsDisabled('exec');
			@setlocale(LC_CTYPE, "en_US.UTF-8"); // avoid strip of UTF-8 chars in escapeshellarg()
		}
		if ($use_php_func)
			return md5_file($filename);
		$hash = substr(trim(substr(@exec("md5sum " . escapeshellarg($filename)), 0, 33), "\\ \t"), 0, 32); // on windows, hash starts with \ if not in same dir!
		if (empty($hash) && file_exists($filename)) {
			$use_php_func = true;
			return md5_file($filename);
		}
		return $hash;
	}

	static function DisableOutputBuffering($force_output = false) {
		@ini_set("zlib.output_compression", "Off");

		header('X-Accel-Buffering: no');
		header('Content-Encoding: none;');
		ob_implicit_flush(true);
		@ob_end_clean();
		if (@ob_get_level())
			@ob_end_clean();
		@flush();

		if ($force_output) {
			// generate a random whitespace string with entropy to avoid gzip reduce
			$chars = array(" ", "\r\n","\n", "\t");
			$stuff = '';
			for ( $i = 0; $i < 1024*4; $i++ ) {
				$stuff .= substr($chars, wp_rand(0, strlen($chars) - 1), 1);
			}
			echo "$stuff\n";
		}
	}

	static function TplDropDown($type, $selected = null) {
		$tpls = WPFB_Core::GetTpls($type);
		$content = '<option value="default">' . __('Default') . '</option>';
		foreach ($tpls as $tag => $tpl) {
			if ($tag != 'default')
				$content .= '<option value="' . $tag . '"' . (($selected == $tag) ? ' selected="selected"' : '') . '>' . __(__(esc_attr(WPFB_Output::Filename2Title($tag))), 'wp-filebase') . '</option>';
		}
		return $content;
	}

	static function AdaptPresets(&$presets) {
		if (isset($presets['file_user_roles'])) {
			$presets['file_user_roles'] = array_values(array_filter($presets['file_user_roles']));
			$presets['file_perm_explicit'] = !empty($presets['file_user_roles']); // set explicit if perm != everyone
		}
	}

	static function PrintAdminSchemeCss() {
		static $first = true;
		if (!$first)
			return;
		$first = false;

		global $_wp_admin_css_colors;
		$color_scheme = get_user_option('admin_color');
		if (empty($_wp_admin_css_colors[$color_scheme])) {
			$color_scheme = 'fresh';
		}

		if (!empty($_wp_admin_css_colors[$color_scheme]->colors)) {
			?>
			<style type="text/css" media="screen">
			<?php
			foreach ($_wp_admin_css_colors[$color_scheme]->colors as $i => $cl) {
				echo ".admin-scheme-bgcolor-$i { background-color: $cl; } .admin-scheme-color-$i { color: $cl; } .admin-scheme-bgcolor-$i-hover:hover { background-color: $cl; } .admin-scheme-color-$i-hover:hover { color: $cl; } .admin-scheme-fill-$i-hover:hover svg { fill: $cl !important; }\n";
			}
			?></style><?php
		}
	}

	static function Icon($icon, $size = 24, $color = false) {
		$icon_svg = esc_attr(WPFB_PLUGIN_URI . "images/iron-icons.svg");
		$s = $size / 24;
		$style = $color ? " style=\"fill:$color\"" : "";
		$uses = '';
		$y = 0;
		foreach(explode(',', $icon) as $ic) {
				  $uses .= "<use x='0' y='{$y}' transform='scale($s)' xlink:href='{$icon_svg}#{$ic}' $style />";
				  //$y = $size*(1-$s/8);
				  //$s /= 2;
				  
		}
		return "<svg style='vertical-align: top;' width='$size' height='$size' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'>$uses</svg>";
	}

	static function HumanTimeFilter($since, $diff, $from, $to) {
		$diff = abs($to - $from);
		if ($diff < 1 && $diff > 0) {
			$ms = round($diff * 1000);
			if ($ms <= 1)
				$ms = 1;
			/* translators: sec=second */
			$since = sprintf('%s ms', $ms);
		} elseif ($diff < MINUTE_IN_SECONDS) {
			$secs = round($diff);
			if ($secs <= 1)
				$secs = 1;
			/* translators: sec=second */
			$since = sprintf(_n('%s sec', '%s secs', $secs), $secs);
		}
		return $since;
	}

}
