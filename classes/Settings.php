<?php class WPFB_Settings {

private static function cleanPath($path) {
	return str_replace('//','/',str_replace('\\', '/', $path));
}

static function Schema()
{
	wpfb_loadclass('Models');
	
	$multiple_entries_desc = __('One entry per line. Seperate the title and a short tag (not longer than 8 characters) with \'|\'.<br />All lines beginning with \'*\' are selected by default.','wp-filebase');
	$multiple_line_desc = __('One entry per line.','wp-filebase');
	$bitrate_desc = __('Limits the maximum tranfer rate for downloads. 0 = unlimited','wp-filebase');
	$traffic_desc = __('Limits the maximum data traffic. 0 = unlimited','wp-filebase');
	$dls_per_day = __('downloads per day','wp-filebase');
	$daily_limit_for = __('Daily limit for %s','wp-filebase');
	
	$upload_path_base = str_replace(ABSPATH, '', get_option('upload_path'));
	if($upload_path_base == '' || $upload_path_base == '/')
		$upload_path_base = 'wp-content/uploads';
		
	$sync_stats	= (get_option('wpfilebase_cron_sync_stats'));
	wpfb_loadclass('Output');
	$last_sync_time =  (!empty($sync_stats)) ? ("<br> (".
		sprintf( __('Last cron sync %s ago took %s and used %s of RAM.','wp-filebase'), human_time_diff($sync_stats['t_start']), human_time_diff($sync_stats['t_start'], $sync_stats['t_end']), WPFB_Output::FormatFilesize($sync_stats['mem_peak']) )
		." "
		.(($next=wp_next_scheduled(WPFB.'_cron')) ? sprintf( __('Next cron sync scheduled in %s.','wp-filebase'), human_time_diff(time(), $next) ) : "")
		.")") : '';
	
	$list_tpls = array_keys(wpfb_call('ListTpl','GetAll'));
	$list_tpls = empty($list_tpls) ? array() : array_combine($list_tpls, $list_tpls);
	
	
	
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	
	$folder_icon_files = array_map(array(__CLASS__,'cleanPath'), array_merge(list_files(WPFB_PLUGIN_ROOT.'images/folder-icons'), list_files(WP_CONTENT_DIR.'/images/foldericons')));
	sort($folder_icon_files);
	$folder_icons = array();
	foreach($folder_icon_files as $fif)
		$folder_icons[] = array('path' => str_replace(self::cleanPath(WP_CONTENT_DIR),'',$fif),'url' => str_replace(self::cleanPath(WP_CONTENT_DIR),WP_CONTENT_URL,$fif));


	$isApache = stripos($_SERVER["SERVER_SOFTWARE"], 'Apache') !== false;


	if(!$isApache) {
		$nginx_conf = "<pre>location /" . ltrim(WPFB_Core::$settings->upload_path, '/') . " {\n\tdeny all;\n\treturn 403;\n}\n</pre>";
		$protect_instructions = "<br><b>Please add the following rules to your nginx config file to disable direct file access:</b><br>$nginx_conf";
	}
	
	return
	(
	
	array (
	
	// common
	'upload_path'			=> array('default' => $upload_path_base . '/filebase', 'title' => __('Upload Path','wp-filebase'), 'desc' => __('Path where all files are stored. Relative to WordPress\' root directory.','wp-filebase'), 'type' => 'text', 'class' => 'code', 'size' => 65),
	'thumbnail_size'		=> array('default' => 300, 'title' => __('Thumbnail size'), 'desc' => __('The maximum side of the image is scaled to this value.','wp-filebase'), 'type' => 'number', 'class' => 'num', 'size' => 8),
	'thumbnail_path'		=> array('default' => '', 'title' => __('Thumbnail Path','wp-filebase'), 'desc' => __('Thumbnails can be stored at a different path than the actual files. Leave empty to use the default upload path. The directory specified here CANNOT be inside the upload path!','wp-filebase'), 'type' => 'text', 'class' => 'code', 'size' => 65),
	
	'base_auto_thumb'		=> array('default' => true, 'title' => __('Auto-detect thumbnails','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Images are considered as thumbnails for files with the same name when syncing. (e.g `file.jpg` &lt;=&gt; `file.zip`)','wp-filebase')),
	
	'fext_blacklist'		=> array('default' => 'db,tmp', 'title' => __('Extension Blacklist','wp-filebase'), 'desc' => __('Files with an extension in this list are skipped while synchronisation. (seperate with comma)','wp-filebase'), 'type' => 'text', 'class' => 'code', 'size' => 100),

	'attach_pos'			=> array('default' => 1, 'title' => __('Attachment Position','wp-filebase'), 'desc' => __('','wp-filebase'), 'type' => 'select', 'options' => array(__('Before the Content','wp-filebase'),__('After the Content','wp-filebase'))),
	
	'attach_loop' 			=> array('default' => false,'title' => __('Attachments in post lists','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Attach files to posts in archives, index and search result.','wp-filebase')),
	
	// display
	'auto_attach_files' 	=> array('default' => true,'title' => __('Show attached files','wp-filebase'), 'type' => 'checkbox', 'desc' => __('If enabled, all associated files are listed below an article','wp-filebase')),
	'filelist_sorting'		=> array('default' => 'file_display_name', 'title' => __('Default sorting','wp-filebase'), 'type' => 'select', 'desc' => __('The file property lists are sorted by','wp-filebase'), 'options' => WPFB_Models::FileSortFields()),
	'filelist_sorting_dir'	=> array('default' => 0, 'title' => __('Sort Order:'/*def*/), 'type' => 'select', 'desc' => __('The sorting direction of file lists','wp-filebase'), 'options' => array(0 => __('Ascending'), 1 => __('Descending'))),
	'filelist_num'			=> array('default' => 0, 'title' => __('Number of files per page','wp-filebase'), 'type' => 'number', 'desc' => __('Length of the file list per page. Set to 0 to disable the limit.','wp-filebase')),
	
	'file_date_format'	=> array('default' => get_option('date_format'), 'title' => __('File Date Format','wp-filebase'), 'desc' => __('Date/Time formatting for files.','wp-filebase').' '.__('<a href="http://codex.wordpress.org/Formatting_Date_and_Time">Documentation on date and time formatting</a>.'), 'type' => 'text', 'class' => 'small-text'),
	
	'disable_css'			=> array('default' => false, 'title' => __('Disable wp-filebase.css','wp-filebase'), 'type' => 'checkbox', 'desc' => __('If you don\'t need default WP-Filebase styling. Improves site performance.','wp-filebase')),
	
	
	// limits
	'bitrate_unregistered'	=> array('default' => 0, 'title' => __('Bit rate limit for guests','wp-filebase'), 'type' => 'number', 'unit' => 'KiB/Sec', 'desc' => &$bitrate_desc),
	'bitrate_registered'	=> array('default' => 0, 'title' => __('Bit rate limit for registered users','wp-filebase'), 'type' => 'number', 'unit' => 'KiB/Sec', 'desc' => &$bitrate_desc),	
	'traffic_day'			=> array('default' => 0, 'title' => __('Daily traffic limit','wp-filebase'), 'type' => 'number', 'unit' => 'MiB', 'desc' => &$traffic_desc),
	'traffic_month'			=> array('default' => 0, 'title' => __('Monthly traffic limit','wp-filebase'), 'type' => 'number', 'unit' => 'GiB', 'desc' => &$traffic_desc),
	'traffic_exceeded_msg'	=> array('default' => __('Traffic limit exceeded! Please try again later.','wp-filebase'), 'title' => __('Traffic exceeded message','wp-filebase'), 'type' => 'text', 'size' => 65),
	'file_offline_msg'		=> array('default' => __('This file is currently offline.','wp-filebase'), 'title' => __('File offline message','wp-filebase'), 'type' => 'text', 'size' => 65),
		
	'daily_user_limits'		=> array('default' => false, 'title' => __('Daily user download limits','wp-filebase'), 'type' => 'checkbox', 'desc' => __('If enabled, unregistered users cannot download any files. You can set different limits for each user role below.','wp-filebase')),
	
	'daily_limit_subscriber'	=> array('default' => 5, 'title' => sprintf($daily_limit_for, _x('Subscriber', 'User role')), 'type' => 'number', 'unit' => &$dls_per_day),
	'daily_limit_contributor'	=> array('default' => 10, 'title' => sprintf($daily_limit_for, _x('Contributor', 'User role')), 'type' => 'number', 'unit' => &$dls_per_day),
	'daily_limit_author'		=> array('default' => 15, 'title' => sprintf($daily_limit_for, _x('Author', 'User role')), 'type' => 'number', 'unit' => &$dls_per_day),
	'daily_limit_editor'		=> array('default' => 20, 'title' => sprintf($daily_limit_for, _x('Editor', 'User role')), 'type' => 'number', 'unit' => &$dls_per_day),
		 
	'daily_limit_exceeded_msg'	=> array('default' => __('You can only download %d files per day.','wp-filebase'), 'title' => __('Daily limit exceeded message','wp-filebase'), 'type' => 'text', 'size' => 65),
	
	// download
	'disable_permalinks'	=> array('default' => false, 'title' => __('Disable download permalinks','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Enable this if you have problems with permalinks.','wp-filebase')),
	'download_base'			=> array('default' => 'download', 'title' => __('Download URL base','wp-filebase'), 'type' => 'text', 'desc' => sprintf(__('The url prefix for file download links. Example: <code>%s</code> (Only used when Permalinks are enabled.)','wp-filebase'), get_option('home').'/%value%/category/file.zip')),
	
	'file_browser_post_id'		=> array('default' => '', 'title' => __('Post ID of the file browser','wp-filebase'), 'type' => 'number', 'unit' => '<span id="file_browser_post_title">'.(($fbid=@WPFB_Core::$settings->file_browser_post_id)?('<a href="'.get_permalink($fbid).'">'.get_the_title($fbid).'</a>'):'').'</span> <a href="javascript:;" class="button" onclick="WPFB_PostBrowser(\'file_browser_post_id\',\'file_browser_post_title\')">' . __('Select') . '</a>', 'desc' => __('Specify the ID of the post or page where the file browser should be placed. If you want to disable this feature leave the field blank.','wp-filebase').' '.__('Note that the selected page should <b>not have any sub-pages</b>!')),
	
	'file_browser_cat_sort_by'		=> array('default' => 'cat_name', 'title' => __('File browser category sorting','wp-filebase'), 'type' => 'select', 'desc' => __('The category property categories in the file browser are sorted by','wp-filebase'), 'options' => WPFB_Models::CatSortFields()),
	'file_browser_cat_sort_dir'	=> array('default' => 0, 'title' => __('Sort Order:'/*def*/), 'type' => 'select', 'desc' => '', 'options' => array(0 => __('Ascending'), 1 => __('Descending'))),
	
	'file_browser_file_sort_by'		=> array('default' => 'file_display_name', 'title' => __('File browser file sorting','wp-filebase'), 'type' => 'select', 'desc' => __('The file property files in the file browser are sorted by','wp-filebase'), 'options' => WPFB_Models::FileSortFields()),
	'file_browser_file_sort_dir'	=> array('default' => 0, 'title' => __('Sort Order:'/*def*/), 'type' => 'select', 'desc' => '', 'options' => array(0 => __('Ascending'), 1 => __('Descending'))),
	
	'file_browser_fbc'		=> array('default' => false, 'title' => __('Files before Categories','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Files will appear above categories in the file browser.','wp-filebase')),

		 
            
        'file_browser_inline_add' => array('default' => true, 'title' => __('Inline Add','wp-filebase'), 'type' => 'checkbox', 'desc' => __('In each category display actions to add a file or category.','wp-filebase')),
	
			'folder_icon' => array('default' => '/plugins/wp-filebase/images/folder-icons/folder_orange48.png', 'title' => __('Folder Icon','wp-filebase'), 'type' => 'icon', 'icons' => $folder_icons, 'desc' => sprintf(__('Choose the default category icon and file browser icon. You can put custom icons in <code>%s</code>.','wp-filebase'),'wp-content/images/foldericons')),
			 
	'small_icon_size'		=> array('default' => 32, 'title' => __('Small Icon Size'), 'desc' => __('Icon size (height) for categories and files. Set to 0 to show icons in full size.','wp-filebase'), 'type' => 'number', 'class' => 'num', 'size' => 8),
			
	
	'cat_drop_down'			=> array('default' => false, 'title' => __('Category drop down list','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Use category drop down list in the file browser instead of listing like files.','wp-filebase')),

	'force_download'		=> array('default' => false, 'title' => __('Always force download','wp-filebase'), 'type' => 'checkbox', 'desc' => __('If enabled files that can be viewed in the browser (like images, PDF documents or videos) can only be downloaded (no streaming).','wp-filebase')),
	'range_download'		=> array('default' => true, 'title' => __('Send HTTP-Range header','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Allows users to pause downloads and continue later. In addition download managers can use multiple connections at the same time.','wp-filebase')),
	'hide_links'			=> array('default' => false, 'title' => __('Hide download links','wp-filebase'), 'type' => 'checkbox', 'desc' => sprintf(__('File download links wont be displayed in the browser\'s status bar. You should enable \'%s\' to make it even harder to find out the URL.','wp-filebase'), __('Always force download','wp-filebase'))),
	'ignore_admin_dls'		=> array('default' => true, 'title' => __('Ignore downloads by admins','wp-filebase'), 'type' => 'checkbox', 'desc' => sprintf(__('Download by an admin user does not increase hit counter. <a href="%s" class="button" onclick="alert(\'Sure?\');" style="vertical-align: baseline;">Reset All Hit Counters to 0</a>'),esc_attr(admin_url('admin.php?page=wpfilebase_manage&action=reset-hits')))),
	'hide_inaccessible'		=> array('default' => false, 'title' => __('Hide inaccessible files and categories','wp-filebase'), 'type' => 'checkbox', 'desc' => __('If enabled files tagged <i>For members only</i> will not be listed for guests or users whith insufficient rights.','wp-filebase')),
	'inaccessible_msg'		=> array('default' => __('You are not allowed to access this file!','wp-filebase'), 'title' => __('Inaccessible file message','wp-filebase'), 'type' => 'text', 'size' => 65, 'desc' => (__('This message will be displayed if users try to download a file they cannot access','wp-filebase').'. '.__('You can enter a URL to redirect users.','wp-filebase'))),
	'inaccessible_redirect'	=> array('default' => false, 'title' => __('Redirect to login','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Guests trying to download inaccessible files are redirected to the login page if this option is enabled.','wp-filebase')),
	'cat_inaccessible_msg'	=> array('default' => __('Access to category denied!','wp-filebase'), 'title' => __('Inaccessible category message','wp-filebase'), 'type' => 'text', 'size' => 65, 'desc' => (__('This message will be displayed if users try to access a category without permission.','wp-filebase'))),
	'login_redirect_src'	=> array('default' => false, 'title' => __('Redirect to referring page after login','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Users are redirected to the page where they clicked on the download link after logging in.','wp-filebase')),
	
	'http_nocache'			=> array('default' => false, 'title' => __('Disable HTTP Caching','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Enable this if you have problems with downloads while using Wordpress with a cache plugin.','wp-filebase')),
	
	'parse_tags_rss'		=> array('default' => true, 'title' => __('Parse template tags in RSS feeds','wp-filebase'), 'type' => 'checkbox', 'desc' => __('If enabled WP-Filebase content tags are parsed in RSS feeds.','wp-filebase')),
	
	'allow_srv_script_upload'	=> array('default' => false, 'title' => __('Allow script upload','wp-filebase'), 'type' => 'checkbox', 'desc' => __('If you enable this, scripts like PHP or CGI can be uploaded. <b>WARNING:</b> Enabling script uploads is a <b>security risk</b>!','wp-filebase')),
	'protect_upload_path'       => array('default' => true && $isApache, 'title' => __('Protect upload path','wp-filebase'), 'type' => 'checkbox', 'desc' => __('This prevents direct access to files in the upload directory.','wp-filebase'). ' '.__('Only applies on Apache webservers! For non-Apache server you have to edit its config file manually.','wp-filebase').$protect_instructions, 'disabled' => !$isApache),

		 
	'private_files'			=> array('default' => false, 'title' => __('Private Files','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Access to files is only permitted to owner and administrators.','wp-filebase').' '.__('This completely overrides access permissions.','wp-filebase')),
	
	'frontend_upload'  		=> array('default' => false, 'title' => __('Enable front end uploads','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Global option to allow file uploads from widgets and embedded file forms','wp-filebase')), //  (Pro only)

		 
	
	'accept_empty_referers'	=> array('default' => false, 'title' => __('Accept empty referers','wp-filebase'), 'type' => 'checkbox', 'desc' => __('If enabled, direct-link-protected files can be downloaded when the referer is empty (i.e. user entered file url in address bar or browser does not send referers)','wp-filebase')),	
	'allowed_referers' 		=> array('default' => '', 'title' => __('Allowed referers','wp-filebase'), 'type' => 'textarea', 'desc' => __('Sites with matching URLs can link to files directly.','wp-filebase').'<br />'.$multiple_line_desc),
	
	//'dl_destroy_session' 	=> array('default' => false, 'title' => __('Destroy session when downloading','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Should be enabled to allow users to download multiple files at the same time. This does not interfere WordPress user sessions, but can cause trouble with other plugins using the global $_SESSION.','wp-filebase')),	
	'use_fpassthru'			=> array('default' => false, 'title' => __('Use fpassthru','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Downloads will be serverd using the native PHP function fpassthru. Enable this when you are experiencing trouble with large files. Note that bandwidth throttle is not available for this method.','wp-filebase')),
	
	'decimal_size_format'	=> array('default' => false, 'title' => __('Decimal file size prefixes','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Enable this if you want decimal prefixes (1 MB = 1000 KB = 1 000 000 B) instead of binary (1 MiB = 1024 KiB = 1 048 576 B)','wp-filebase')),
	
	'admin_bar'	=> array('default' => true, 'title' => __('Add WP-Filebase to admin menu bar','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Display some quick actions for file management in the admin menu bar.','wp-filebase')),
	//'file_context_menu'	=> array('default' => true, 'title' => '', 'type' => 'checkbox', 'desc' => ''),
	
	'cron_sync'	=> array('default' => true, 'title' => __('Automatic Sync','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Schedules a cronjob to hourly synchronize the filesystem and the database.','wp-filebase').$last_sync_time),
	
	'remove_missing_files'	=> array('default' => false, 'title' => __('Remove Missing Files','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Missing files are removed from the database during sync','wp-filebase')),
	
			
	
	'search_integration' =>  array('default' => true, 'title' => __('Search Integration','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Searches in attached files and lists the associated posts and pages when searching the site.','wp-filebase').' '.sprintf(__('If you experience performance issues with many posts and files (>1000), disable this option but enable %s.','wp-filebase'), 'File Pages / Content Keywords')),
	
	'search_result_tpl' =>  array('default' => 'default', 'title' => __('Search Result File List Template','wp-filebase'), 'type' => 'select', 'options' => $list_tpls, 'desc' => __('Set the List Template used for Search Results when using the Search Widget','wp-filebase')),
	
		 
	'disable_id3' =>  array('default' => false, 'title' => __('Disable ID3 tag detection','wp-filebase'), 'type' => 'checkbox', 'desc' => __('This disables all meta file info reading. Use this option if you have issues adding large files.','wp-filebase')),
	'search_id3' =>  array('default' => true, 'title' => __('Search ID3 Tags','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Search in file meta data, like ID3 for MP3 files, EXIF for JPEG... (this option does not increase significantly server load since all data is cached in a MySQL table)','wp-filebase')),
	'use_path_tags' => array('default' => false, 'title' => __('Use path instead of ID in Shortcode','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Files and Categories are identified by paths and not by their IDs in the generated Shortcodes','wp-filebase')),
	'no_name_formatting'  => array('default' => false, 'title' => __('Disable Name Formatting','wp-filebase'), 'type' => 'checkbox', 'desc' => __('This will disable automatic formatting/uppercasing file names when they are used as title (e.g. when syncing)','wp-filebase')),
		 
		 
	'fake_md5' => array('default' => false, 'title' => __('Fake MD5 Hashes','wp-filebase'), 'type' => 'checkbox', 'desc' => __('This dramatically speeds up sync, since no real MD5 checksum of the files is calculated but only a hash of modification time and file size.','wp-filebase')),

	
	// file browser
	'disable_footer_credits'  => array('default' => true, 'title' => __('Remove WP-Filebase Footer credits','wp-filebase'), 'type' => 'checkbox', 'desc' => sprintf(__('This disables the footer credits only displayed on <a href="%s">File Browser Page</a>. Why should you keep the credits? Every backlink helps WP-Filebase to get more popular, popularity motivates the developer to continue work on the plugin.','wp-filebase'), get_permalink(@WPFB_Core::$settings->file_browser_post_id).'#wpfb-credits')),
	'footer_credits_style'  => array('default' => 'margin:0 auto 2px auto; text-align:center; font-size:11px;', 'title' => __('Footer credits Style','wp-filebase'), 'type' => 'text', 'class' => 'code', 'desc' => __('Set custom CSS style for WP-Filebase footer credits','wp-filebase'),'size'=>80),
	'late_script_loading'	=> array('default' => false, 'title' => __('Late script loading','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Scripts will be included in content, not in header. Enable if your AJAX tree view does not work properly.','wp-filebase')),
	
	'default_author' => array('default' => '', 'title' => __('Default Author','wp-filebase'), 'desc' => __('This author will be used as form default and when adding files with FTP','wp-filebase'), 'type' => 'text', 'size' => 65),
	'default_roles' => array('default' => array(), 'title' => __('Default User Roles','wp-filebase'), 'desc' => __('These roles are selected by default and will be used for files added with FTP','wp-filebase'), 'type' => 'roles'),
	
	'default_cat' => array('default' => 0, 'title' => __('Default Category','wp-filebase'), 'desc' => __('Preset Category in the file form','wp-filebase'), 'type' => 'cat'),
		
	'languages'				=> array('default' => "English|en\nDeutsch|de", 'title' => __('Languages'), 'type' => 'textarea', 'desc' => &$multiple_entries_desc),
	'platforms'				=> array('default' => "Windows 7|win7\n*Windows 8|win8\nLinux|linux\nMac OS X|mac", 'title' => __('Platforms','wp-filebase'), 'type' => 'textarea', 'desc' => &$multiple_entries_desc, 'nowrap' => true),	
	'licenses'				=> array('default' =>
"*Freeware|free\nShareware|share\nGNU General Public License|gpl|http://www.gnu.org/copyleft/gpl.html\nCC Attribution-NonCommercial-ShareAlike|ccbyncsa|http://creativecommons.org/licenses/by-nc-sa/3.0/", 'title' => __('Licenses','wp-filebase'), 'type' => 'textarea', 'desc' => &$multiple_entries_desc, 'nowrap' => true),
	'requirements'			=> array('default' =>
"PDF Reader|pdfread|https://www.foxitsoftware.com/products/pdf-reader/
Java|java|http://www.java.com/download/
Open Office|ooffice|http://www.openoffice.org/download/index.html
",
	'title' => __('Requirements','wp-filebase'), 'type' => 'textarea', 'desc' => $multiple_entries_desc . ' ' . __('You can optionally add |<i>URL</i> to each line to link to the required software/file.','wp-filebase'), 'nowrap' => true),
	
	'default_direct_linking'	=> array('default' => 1, 'title' => __('Default File Direct Linking'), 'type' => 'select', 'desc' => __('','wp-filebase'), 'options' => array(1 => __('Allow direct linking','wp-filebase'), 0 => __('Redirect to post','wp-filebase') )),	 
		 
	'custom_fields'			=> array('default' => "Custom 1|cf1\nCustom 2|cf2", 'title' => __('Custom Fields'), 'type' => 'textarea', 'desc' => 
	__('With custom fields you can add even more file properties.','wp-filebase').' '.  sprintf(__('Append another %s to set the default value.','wp-filebase'),'|<i>Default Value</i>'.' '.$multiple_entries_desc)),
	
	
	
	
	
	'template_file'			=> array('default' =>
<<<TPLFILE
<div class="wpfilebase-file-default wpfb-file-%file_id%" onclick="if('undefined' == typeof event.target.href) document.getElementById('wpfb-file-link-%uid%').click();">
  <div class="icon"><a href="%file_url%" target="_blank" title="Download %file_display_name%"><img align="middle" src="%file_icon_url%" alt="%file_display_name%" /></a></div>
  <div class="filetitle">
    <a href="%file_url%" title="Download %file_display_name%" target="_blank" id="wpfb-file-link-%uid%">%file_display_name%</a>
    %button_edit% %button_delete%
    <!-- IF %file_post_id% AND %post_id% != %file_post_id% --><a href="%file_post_url%" class="postlink">&raquo; %'Post'%</a><!-- ENDIF -->
    <br />
    %file_name%<br />
    <!-- IF %file_version% -->%'Version:'% %file_version%<br /><!-- ENDIF -->
  </div>
  <div class="info">
    %file_size%<br />
    %file_hits% %'Downloads'%<br />
    <a href="#" onclick="return wpfilebase_filedetails(%uid%);">%'Details'%</a>
  </div>
  <div class="details" id="wpfilebase-filedetails%uid%" style="display: none;">
  <!-- IF %file_description% --><p>%file_description%</p><!-- ENDIF -->
  <table border="0">
   <!-- IF %file_languages% --><tr><td><strong>%'Languages'%:</strong></td><td>%file_languages%</td></tr><!-- ENDIF -->
   <!-- IF %file_author% --><tr><td><strong>%'Author'%:</strong></td><td>%file_author%</td></tr><!-- ENDIF -->
   <!-- IF %file_platforms% --><tr><td><strong>%'Platforms'%:</strong></td><td>%file_platforms%</td></tr><!-- ENDIF -->
   <!-- IF %file_requirements% --><tr><td><strong>%'Requirements'%:</strong></td><td>%file_requirements%</td></tr><!-- ENDIF -->
   <!-- IF %file_category% --><tr><td><strong>%'Category:'%</strong></td><td>%file_category%</td></tr><!-- ENDIF -->
   <!-- IF %file_license% --><tr><td><strong>%'License'%:</strong></td><td>%file_license%</td></tr><!-- ENDIF -->
   <tr><td><strong>%'Date'%:</strong></td><td>%file_date%</td></tr>
  </table>
  </div>
 <div style="clear: both;"></div>
</div>
TPLFILE
	, 'title' => __('Default File Template','wp-filebase'), 'type' => 'textarea', 'desc' => (WPFB_Models::TplFieldsSelect('template_file') . '<br />' . __('The template for attachments','wp-filebase')), 'class' => 'code'),

	'template_cat'			=> array('default' =>
<<<TPLCAT
<div class="wpfilebase-cat-default">
  <h3>
    <!-- IF %cat_has_icon% || true -->%cat_small_icon%<!-- ENDIF -->
    <a href="%cat_url%" title="Go to category %cat_name%">%cat_name%</a>
    <span>%cat_num_files% <!-- IF %cat_num_files% == 1 -->file<!-- ELSE -->files<!-- ENDIF --></span>
  </h3>
</div>
TPLCAT
	, 'title' => __('Category Template','wp-filebase'), 'type' => 'textarea', 'desc' => (WPFB_Models::TplFieldsSelect('template_cat', false, true) . '<br />' . __('The template for category lists (used in the file browser)','wp-filebase')), 'class' => 'code'),

	'dlclick_js'			=> array('default' =>
<<<JS
if(typeof pageTracker == 'object') {
	pageTracker._trackPageview(file_url); // new google analytics tracker
} else if(typeof urchinTracker == 'function') {	
	urchinTracker(file_url); // old google analytics tracker
} else if(typeof ga == 'function') {
	ga('send', 'pageview', file_url); // universal analytics
}
JS
	, 'title' => __('Download JavaScript','wp-filebase'), 'type' => 'textarea', 'desc' => __('Here you can enter JavaScript Code which is executed when a user clicks on file download link. The following variables can be used: <i>file_id</i>: the ID of the file, <i>file_url</i>: the clicked download url','wp-filebase'), 'class' => 'code'),

	//'max_dls_per_ip'			=> array('default' => 10, 'title' => __('Maximum downloads','wp-filebase'), 'type' => 'number', 'unit' => 'per file, per IP Address', 'desc' => 'Maximum number of downloads of a file allowed for an IP Address. 0 = unlimited'),
	//'archive_lister'			=> array('default' => false, 'title' => __('Archive lister','wp-filebase'), 'type' => 'checkbox', 'desc' => __('Uploaded files are scanned for archives','wp-filebase')),
	//'enable_ratings'			=> array('default' => false, 'title' => __('Ratings'), 'type' => 'checkbox', 'desc' => ''),
	)
			  
	);
}

}


 