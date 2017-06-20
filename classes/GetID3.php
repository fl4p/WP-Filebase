<?php

class WPFB_GetID3 {

	static $engine;

	static function GetEngine() {
		if (!self::$engine) {
			if (!class_exists('getID3')) {
				$tmp_dir = WPFB_Core::UploadDir() . '/.tmp';
				if (!is_dir($tmp_dir))
					@mkdir($tmp_dir);
				define('GETID3_TEMP_DIR', $tmp_dir . '/');
				unset($tmp_dir);
				require_once(WPFB_PLUGIN_ROOT . 'extras/getid3/getid3.php');
			}

			if (!class_exists('getid3_lib')) {
				require_once(WPFB_PLUGIN_ROOT . 'extras/getid3/getid3.lib.php');
			}

			self::$engine = new getID3;
		}
		return self::$engine;
	}

	private static function xml2Text($content) {
		return trim(esc_html(preg_replace('! +!', ' ', strip_tags(str_replace('<', ' <', $content)))));
	}

	/**
	 * @param WPFB_File $file
	 * @param array $info
	 */
private static function indexDocument($file, &$info, &$times)
{

}

	/**
	 * Intesive analysis of file contents. Does _not_ make changes to the file or store anything in the DB!
	 * 
	 * @param WPFB_File $file
	 * @return type
	 */
	private static function analyzeFile($file) {
		@ini_set('max_execution_time', '0');
		@set_time_limit(0);
		$filename = $file->GetLocalPath();

		$times = array();
		$times['analyze'] = microtime(true);
		$info = WPFB_Core::$settings->disable_id3 ? array() : self::GetEngine()->analyze($filename);

		if (!WPFB_Core::$settings->disable_id3 && class_exists('getid3_lib')) {
			getid3_lib::CopyTagsToComments($info);
		}

		$info = apply_filters('wpfilebase_analyze_file', $info, $file);


		// only index if keywords not externally set
		if(!isset($info['keywords']))
			self::indexDocument($file, $info, $times);

		
		$times['end'] = microtime(true);			
		$t_keys = array_keys($times);

		$into['debug'] = array('timestamp' => $times[$t_keys[0]], 'timings' => array());			
		for($i = 1; $i < count($t_keys); $i++) {
			$info['debug']['timings'][$t_keys[$i-1]] = round(($times[$t_keys[$i]] - $times[$t_keys[$i-1]]) * 1000);
		}
			
		return $info;
	}

	/**
	 * 
	 * @global type $wpdb
	 * @param WPFB_File $file
	 * @param type $info
	 * @return type
	 */
	static function StoreFileInfo($file, $info) {
		global $wpdb;

		if (empty($file->file_thumbnail)) {
			if (!empty($info['comments']['picture'][0]['data']))
				$cover_img = & $info['comments']['picture'][0]['data'];
			elseif (!empty($info['id3v2']['APIC'][0]['data']))
				$cover_img = & $info['id3v2']['APIC'][0]['data'];
			elseif (!empty($info['document']['thumbnail_url'])) {
				// read thumbnail from external webservice
				$cover_img = @file_get_contents($info['document']['thumbnail_url']);
			} else {
				$cover_img = null;
			}

			// TODO unset pic in info?

			if (!empty($cover_img)) {
				$cover = $file->GetLocalPath();
				$cover = substr($cover, 0, strrpos($cover, '.')) . '.jpg';
				file_put_contents($cover, $cover_img);
				$file->CreateThumbnail($cover, true);
				@unlink($cover);
				$cf_changed = true;
			}
		}

		self::cleanInfoByRef($info);

		// set encoding to utf8 (required for GetKeywords)
		if (function_exists('mb_internal_encoding')) {
			$cur_enc = mb_internal_encoding();
			mb_internal_encoding('UTF-8');
		}


		wpfb_loadclass('Misc');

		$keywords = array();
		WPFB_Misc::GetKeywords($info, $keywords);
		$keywords = strip_tags(join(' ', $keywords));
		$keywords = str_replace(array('\n', '&#10;'), '', $keywords);
		$keywords = preg_replace('/\s\s+/', ' ', $keywords);
		if (!function_exists('mb_detect_encoding') || mb_detect_encoding($keywords, "UTF-8") != "UTF-8")
			$keywords = utf8_encode($keywords);
		// restore prev encoding
		if (function_exists('mb_internal_encoding'))
			mb_internal_encoding($cur_enc);

		// don't store keywords 2 times:
		unset($info['keywords']);
		self::removeLongData($info, 8000);

		$data = empty($info) ? '0' : base64_encode(serialize($info));

		$res = $wpdb->replace($wpdb->wpfilebase_files_id3, array(
			 'file_id' => (int) $file->GetId(),
			 'analyzetime' => time(),
			 'value' => &$data,
			 'keywords' => &$keywords
		));
		unset($data, $keywords);

		$cf_changed = false;

		// TODO: move this cleanup into a callback / should NOT be HERE!
		if ($file->file_rescan_pending) {
			$file->file_rescan_pending = 0;
			$cf_changed = true;
		}

		// delete local temp file
		if ($file->IsRemote() && file_exists($file->GetLocalPath())) {
			@unlink($file->GetLocalPath());
		}
		// TODO END;

		if ($cf_changed && !$file->IsLocked())
			$file->DbSave(true);

		return $res;
	}

	static function UpdateCachedFileInfo($file) {
		$info = self::analyzeFile($file);
		if(self::StoreFileInfo($file, $info) === false)
			return false;
		return $info;
	}

	/**
	 *  gets file info out of the cache or analyzes the file if not cached
	 *  used in file edit form to display the details
	 * @global type $wpdb
	 * @param type $file
	 * @param type $get_keywords
	 * @return type
	 */
	static function GetFileInfo($file, $get_keywords = false) {
		global $wpdb;
		$sql = "SELECT value" . ($get_keywords ? ", keywords" : "") . " FROM $wpdb->wpfilebase_files_id3 WHERE file_id = " . $file->GetId();
		if ($get_keywords) {	// TODO: cache not updated if get_keywords
			$info = $wpdb->get_row($sql);
			if (!empty($info))
				$info->value = unserialize(base64_decode($info->value));
			return $info;
		}
		if (is_null($info = $wpdb->get_var($sql)))
			return self::UpdateCachedFileInfo($file);
		return ($info == '0') ? null : unserialize(base64_decode($info));
	}

	static function GetFileAnalyzeTime($file) {
		global $wpdb;
		$t = $wpdb->get_var("SELECT analyzetime FROM $wpdb->wpfilebase_files_id3 WHERE file_id = " . $file->GetId());
		if (is_null($t))
			$t = 0;
		return $t;
	}

	private static function cleanInfoByRef(&$info) {
		static $skip_keys = array('getid3_version', 'streams', 'seektable', 'streaminfo',
			 'comments_raw', 'encoding', 'flags', 'image_data', 'toc', 'lame', 'filename', 'filesize', 'md5_file',
			 'data', 'warning', 'error', 'filenamepath', 'filepath', 'popm', 'email', 'priv', 'ownerid', 'central_directory', 'raw', 'apic', 'iTXt', 'IDAT');

		foreach ($info as $key => &$val) {
			if (empty($val) || in_array(strtolower($key), $skip_keys) || strpos($key, "UndefinedTag") !== false || strpos($key, "XML") !== false) {
				unset($info[$key]);
				continue;
			}

			if (is_array($val) || is_object($val))
				self::cleanInfoByRef($info[$key]);
			else if (is_string($val)) {
				$a = ord($val{0});
				if ($a < 32 || $a > 126 || $val{0} == '?' || strpos($val, chr(01)) !== false || strpos($val, chr(0x09)) !== false) {  // check for binary data
					unset($info[$key]);
					continue;
				}
			}
		}
	}

	private static function removeLongData(&$info, $max_length) {
		foreach (array_keys($info) as $key) {
			if (is_array($info[$key]) || is_object($info[$key]))
				self::removeLongData($info[$key], $max_length);
			else if (is_string($info[$key]) && strlen($info[$key]) > $max_length)
				unset($info[$key]);
		}
	}



}
