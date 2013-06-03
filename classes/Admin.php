<?php
class WPFB_Admin {

static $MIN_SIZE_FOR_PROGRESSBAR = 2097152;//2MiB
const MAX_USERS_PER_ROLE_DISPLAY = 50;

static function InitClass()
{	
	wpfb_loadclass('AdminLite', 'Item', 'File', 'Category','FileUtils');
	
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_script(WPFB.'-admin', WPFB_PLUGIN_URI.'js/admin.js', array(), WPFB_VERSION);	

	wp_enqueue_style('widgets');
	
	require_once(ABSPATH . 'wp-admin/includes/file.php');
}

static function SettingsSchema()
{
	$multiple_entries_desc = __("One entry per line. Seperate the title and a short tag (not longer than 8 characters) with '|'.<br />All lines beginning with '*' are selected by default.", WPFB);
	$multiple_line_desc = __('One entry per line.', WPFB);
	$bitrate_desc = __('Limits the maximum tranfer rate for downloads. 0 = unlimited', WPFB);
	$traffic_desc = __('Limits the maximum data traffic. 0 = unlimited', WPFB);
	$dls_per_day = __('downloads per day', WPFB);
	$daily_limit_for = __('Daily limit for %s', WPFB);
	
	$upload_path_base = str_replace(ABSPATH, '', get_option('upload_path'));
	if($upload_path_base == '' || $upload_path_base == '/')
		$upload_path_base = 'wp-content/uploads';
		
	$last_sync_time	= intval(get_option(WPFB_OPT_NAME.'_cron_sync_time'));
	$last_sync_time = ($last_sync_time > 0) ? (" (".sprintf( __('Last cron sync on %1$s at %2$s.',WPFB), date_i18n( get_option( 'date_format'), $last_sync_time ), date_i18n( get_option( 'time_format'), $last_sync_time ) ).")") : '';
		
	
	$list_tpls = array_keys(wpfb_call('ListTpl','GetAll'));
	$list_tpls = empty($list_tpls) ? array() : array_combine($list_tpls, $list_tpls);
	
	
	return
	(
	
	array (
	
	// common
	'upload_path'			=> array('default' => $upload_path_base . '/filebase', 'title' => __('Upload Path', WPFB), 'desc' => __('Path where all files are stored. Relative to WordPress\' root directory.', WPFB), 'type' => 'text', 'class' => 'code', 'size' => 65),
	'thumbnail_size'		=> array('default' => 120, 'title' => __('Thumbnail size'), 'desc' => __('The maximum side of the image is scaled to this value.', WPFB), 'type' => 'number', 'class' => 'num', 'size' => 8),
	'thumbnail_path'		=> array('default' => '', 'title' => __('Thumbnail Path',WPFB), 'desc' => __('Thumbnails can be stored at a different path than the actual files. Leave empty to use the default upload path.', WPFB), 'type' => 'text', 'class' => 'code', 'size' => 65),
	
	'base_auto_thumb'		=> array('default' => true, 'title' => __('Auto-detect thumbnails',WPFB), 'type' => 'checkbox', 'desc' => __('Images are considered as thumbnails for files with the same name when syncing. (e.g `file.jpg` &lt;=&gt; `file.zip`)', WPFB)),
	
	'fext_blacklist'		=> array('default' => 'db,tmp', 'title' => __('Extension Blacklist', WPFB), 'desc' => __('Files with an extension in this list are skipped while synchronisation. (seperate with comma)', WPFB), 'type' => 'text', 'class' => 'code', 'size' => 100),

	'attach_pos'			=> array('default' => 1, 'title' => __('Attachment Position', WPFB), 'desc' => __('', WPFB), 'type' => 'select', 'options' => array(__('Before the Content',WPFB),__('After the Content',WPFB))),
	
	'attach_loop' 			=> array('default' => false,'title' => __('Attachments in post lists', WPFB), 'type' => 'checkbox', 'desc' => __('Attach files to posts in archives, index and search result.', WPFB)),
	
	// display
	'auto_attach_files' 	=> array('default' => true,'title' => __('Show attached files', WPFB), 'type' => 'checkbox', 'desc' => __('If enabled, all associated files are listed below an article', WPFB)),
	'filelist_sorting'		=> array('default' => 'file_display_name', 'title' => __('Default sorting', WPFB), 'type' => 'select', 'desc' => __('The file property lists are sorted by', WPFB), 'options' => self::FileSortFields()),
	'filelist_sorting_dir'	=> array('default' => 0, 'title' => __('Sort Order:'/*def*/), 'type' => 'select', 'desc' => __('The sorting direction of file lists', WPFB), 'options' => array(0 => __('Ascending'), 1 => __('Descending'))),
	'filelist_num'			=> array('default' => 0, 'title' => __('Number of files per page', WPFB), 'type' => 'number', 'desc' => __('Length of the file list per page. Set to 0 to disable the limit.', WPFB)),
	
	'file_date_format'		=> array('default' => get_option('date_format'), 'title' => __('File Date Format', WPFB), 'desc' => __('Date/Time formatting for files.',WPFB).' '.__('<a href="http://codex.wordpress.org/Formatting_Date_and_Time">Documentation on date and time formatting</a>.'), 'type' => 'text', 'class' => 'small-text'),
	
	
	
	// limits
	'bitrate_unregistered'	=> array('default' => 0, 'title' => __('Bit rate limit for guests', WPFB), 'type' => 'number', 'unit' => 'KiB/Sec', 'desc' => &$bitrate_desc),
	'bitrate_registered'	=> array('default' => 0, 'title' => __('Bit rate limit for registered users', WPFB), 'type' => 'number', 'unit' => 'KiB/Sec', 'desc' => &$bitrate_desc),	
	'traffic_day'			=> array('default' => 0, 'title' => __('Daily traffic limit', WPFB), 'type' => 'number', 'unit' => 'MiB', 'desc' => &$traffic_desc),
	'traffic_month'			=> array('default' => 0, 'title' => __('Monthly traffic limit', WPFB), 'type' => 'number', 'unit' => 'GiB', 'desc' => &$traffic_desc),
	'traffic_exceeded_msg'	=> array('default' => __('Traffic limit exceeded! Please try again later.', WPFB), 'title' => __('Traffic exceeded message', WPFB), 'type' => 'text', 'size' => 65),
	'file_offline_msg'		=> array('default' => __('This file is currently offline.', WPFB), 'title' => __('File offline message', WPFB), 'type' => 'text', 'size' => 65),
		
	'daily_user_limits'		=> array('default' => false, 'title' => __('Daily user download limits', WPFB), 'type' => 'checkbox', 'desc' => __('If enabled, unregistered users cannot download any files. You can set different limits for each user role below.', WPFB)),
	
	'daily_limit_subscriber'	=> array('default' => 5, 'title' => sprintf($daily_limit_for, _x('Subscriber', 'User role')), 'type' => 'number', 'unit' => &$dls_per_day),
	'daily_limit_contributor'	=> array('default' => 10, 'title' => sprintf($daily_limit_for, _x('Contributor', 'User role')), 'type' => 'number', 'unit' => &$dls_per_day),
	'daily_limit_author'		=> array('default' => 15, 'title' => sprintf($daily_limit_for, _x('Author', 'User role')), 'type' => 'number', 'unit' => &$dls_per_day),
	'daily_limit_editor'		=> array('default' => 20, 'title' => sprintf($daily_limit_for, _x('Editor', 'User role')), 'type' => 'number', 'unit' => &$dls_per_day),
		 
	'daily_limit_exceeded_msg'	=> array('default' => __('You can only download %d files per day.', WPFB), 'title' => __('Daily limit exceeded message', WPFB), 'type' => 'text', 'size' => 65),
	
	// download
	'disable_permalinks'	=> array('default' => false, 'title' => __('Disable download permalinks', WPFB), 'type' => 'checkbox', 'desc' => __('Enable this if you have problems with permalinks.', WPFB)),
	'download_base'			=> array('default' => 'download', 'title' => __('Download URL base', WPFB), 'type' => 'text', 'desc' => sprintf(__('The url prefix for file download links. Example: <code>%s</code> (Only used when Permalinks are enabled.)', WPFB), get_option('home').'/%value%/category/file.zip')),
	
	'file_browser_post_id'		=> array('default' => '', 'title' => __('Post ID of the file browser', WPFB), 'type' => 'number', 'unit' => '<span id="file_browser_post_title">'.(($fbid=WPFB_Core::GetOpt('file_browser_post_id'))?('<a href="'.get_permalink($fbid).'">'.get_the_title($fbid).'</a>'):'').'</span> <a href="javascript:;" class="button" onclick="WPFB_PostBrowser(\'file_browser_post_id\',\'file_browser_post_title\')">' . __('Select') . '</a>', 'desc' => __('Specify the ID of the post or page where the file browser should be placed. If you want to disable this feature leave the field blank.', WPFB).' '.__('Note that the selected page should <b>not have any sub-pages</b>!')),
	
	'file_browser_cat_sort_by'		=> array('default' => 'cat_name', 'title' => __('File browser category sorting', WPFB), 'type' => 'select', 'desc' => __('The category property categories in the file browser are sorted by', WPFB), 'options' => self::CatSortFields()),
	'file_browser_cat_sort_dir'	=> array('default' => 0, 'title' => __('Sort Order:'/*def*/), 'type' => 'select', 'desc' => '', 'options' => array(0 => __('Ascending'), 1 => __('Descending'))),
	
	'file_browser_file_sort_by'		=> array('default' => 'file_display_name', 'title' => __('File browser file sorting', WPFB), 'type' => 'select', 'desc' => __('The file property files in the file browser are sorted by', WPFB), 'options' => self::FileSortFields()),
	'file_browser_file_sort_dir'	=> array('default' => 0, 'title' => __('Sort Order:'/*def*/), 'type' => 'select', 'desc' => '', 'options' => array(0 => __('Ascending'), 1 => __('Descending'))),
	
	'file_browser_fbc'		=> array('default' => false, 'title' => __('Files before Categories', WPFB), 'type' => 'checkbox', 'desc' => __('Files will appear above categories in the file browser.', WPFB)),
	
	'small_icon_size'		=> array('default' => 32, 'title' => __('Small Icon Size'), 'desc' => __('Icon size (height) for categories and files. Set to 0 to show icons in full size.', WPFB), 'type' => 'number', 'class' => 'num', 'size' => 8),
			
	
	'cat_drop_down'			=> array('default' => false, 'title' => __('Category drop down list', WPFB), 'type' => 'checkbox', 'desc' => __('Use category drop down list in the file browser instead of listing like files.', WPFB)),

	'force_download'		=> array('default' => false, 'title' => __('Always force download', WPFB), 'type' => 'checkbox', 'desc' => __('If enabled files that can be viewed in the browser (like images, PDF documents or videos) can only be downloaded (no streaming).', WPFB)),
	'range_download'		=> array('default' => true, 'title' => __('Send HTTP-Range header', WPFB), 'type' => 'checkbox', 'desc' => __('Allows users to pause downloads and continue later. In addition download managers can use multiple connections at the same time.', WPFB)),
	'hide_links'			=> array('default' => false, 'title' => __('Hide download links', WPFB), 'type' => 'checkbox', 'desc' => sprintf(__('File download links wont be displayed in the browser\'s status bar. You should enable \'%s\' to make it even harder to find out the URL.', WPFB), __('Always force download', WPFB))),
	'ignore_admin_dls'		=> array('default' => true, 'title' => __('Ignore downloads by admins', WPFB), 'type' => 'checkbox'),
	'hide_inaccessible'		=> array('default' => true, 'title' => __('Hide inaccessible files and categories', WPFB), 'type' => 'checkbox', 'desc' => __('If enabled files tagged <i>For members only</i> will not be listed for guests or users whith insufficient rights.', WPFB)),
	'inaccessible_msg'		=> array('default' => __('You are not allowed to access this file!', WPFB), 'title' => __('Inaccessible file message', WPFB), 'type' => 'text', 'size' => 65, 'desc' => (__('This message will be displayed if users try to download a file they cannot access', WPFB).'. '.__('You can enter a URL to redirect users.', WPFB))),
	'inaccessible_redirect'	=> array('default' => false, 'title' => __('Redirect to login', WPFB), 'type' => 'checkbox', 'desc' => __('Guests trying to download inaccessible files are redirected to the login page if this option is enabled.', WPFB)),
	'cat_inaccessible_msg'	=> array('default' => __('Access to category denied!', WPFB), 'title' => __('Inaccessible category message', WPFB), 'type' => 'text', 'size' => 65, 'desc' => (__('This message will be displayed if users try to access a category without permission.', WPFB))),
	'login_redirect_src'	=> array('default' => false, 'title' => __('Redirect to referring page after login', WPFB), 'type' => 'checkbox', 'desc' => __('Users are redirected to the page where they clicked on the download link after logging in.', WPFB)),
	
	'http_nocache'			=> array('default' => false, 'title' => __('Disable HTTP Caching', WPFB), 'type' => 'checkbox', 'desc' => __('Enable this if you have problems with downloads while using Wordpress with a cache plugin.', WPFB)),
	
	'parse_tags_rss'		=> array('default' => true, 'title' => __('Parse template tags in RSS feeds', WPFB), 'type' => 'checkbox', 'desc' => __('If enabled WP-Filebase content tags are parsed in RSS feeds.', WPFB)),
	
	'allow_srv_script_upload'	=> array('default' => false, 'title' => __('Allow script upload', WPFB), 'type' => 'checkbox', 'desc' => __('If you enable this, scripts like PHP or CGI can be uploaded. <b>WARNING:</b> Enabling script uploads is a <b>security risk</b>!', WPFB)),
	'protect_upload_path'	=> array('default' => true, 'title' => __('Protect upload path', WPFB), 'type' => 'checkbox', 'desc' => __('This prevents direct access to files in the upload directory.', WPFB)),

		 
	'private_files'			=> array('default' => false, 'title' => __('Private Files', WPFB), 'type' => 'checkbox', 'desc' => __('Access to files is only permitted to owner and administrators.', WPFB)),
	
	'frontend_upload'  		=> array('default' => false, 'title' => __('Enable front end uploads', WPFB), 'type' => 'checkbox', 'desc' => __('Global option to allow file uploads from widgets and embedded file forms', WPFB)), //  (Pro only)
	
	
	'accept_empty_referers'	=> array('default' => true, 'title' => __('Accept empty referers', WPFB), 'type' => 'checkbox', 'desc' => __('If enabled, direct-link-protected files can be downloaded when the referer is empty (i.e. user entered file url in address bar or browser does not send referers)', WPFB)),	
	'allowed_referers' 		=> array('default' => '', 'title' => __('Allowed referers', WPFB), 'type' => 'textarea', 'desc' => __('Sites with matching URLs can link to files directly.', WPFB).'<br />'.$multiple_line_desc),
	
	//'dl_destroy_session' 	=> array('default' => false, 'title' => __('Destroy session when downloading', WPFB), 'type' => 'checkbox', 'desc' => __('Should be enabled to allow users to download multiple files at the same time. This does not interfere WordPress user sessions, but can cause trouble with other plugins using the global $_SESSION.', WPFB)),	
	'use_fpassthru'			=> array('default' => false, 'title' => __('Use fpassthru', WPFB), 'type' => 'checkbox', 'desc' => __('Downloads will be serverd using the native PHP function fpassthru. Enable this when you are experiencing trouble with large files. Note that bandwidth throttle is not available for this method.', WPFB)),
	
	'decimal_size_format'	=> array('default' => false, 'title' => __('Decimal file size prefixes', WPFB), 'type' => 'checkbox', 'desc' => __('Enable this if you want decimal prefixes (1 MB = 1000 KB = 1 000 000 B) instead of binary (1 MiB = 1024 KiB = 1 048 576 B)', WPFB)),
	
	'admin_bar'	=> array('default' => true, 'title' => __('Add WP-Filebase to admin menu bar', WPFB), 'type' => 'checkbox', 'desc' => __('Display some quick actions for file management in the admin menu bar.', WPFB)),
	//'file_context_menu'	=> array('default' => true, 'title' => '', 'type' => 'checkbox', 'desc' => ''),
	
	'cron_sync'	=> array('default' => false, 'title' => __('Automatic Sync', WPFB), 'type' => 'checkbox', 'desc' => __('Schedules a cronjob to hourly synchronize the filesystem and the database.', WPFB).$last_sync_time),
	
	'remove_missing_files'	=> array('default' => false, 'title' => __('Remove Missing Files', WPFB), 'type' => 'checkbox', 'desc' => __('Missing files are removed from the database during sync', WPFB)),
	
			
	
	'search_integration' =>  array('default' => true, 'title' => __('Search Integration', WPFB), 'type' => 'checkbox', 'desc' => __('Searches in attached files and lists the associated posts and pages when searching the site.', WPFB)),
	
	'search_result_tpl' =>  array('default' => 'default', 'title' => __('Search Result Template', WPFB), 'type' => 'select', 'options' => $list_tpls, 'desc' => __('Set the List Template used for Search Results when using the Search Widget', WPFB)),

		 
	'disable_id3' =>  array('default' => false, 'title' => __('Disable ID3 tag detection', WPFB), 'type' => 'checkbox', 'desc' => __('This disables all meta file info reading. Use this option if you have issues adding large files.', WPFB)),
	'search_id3' =>  array('default' => true, 'title' => __('Search ID3 Tags', WPFB), 'type' => 'checkbox', 'desc' => __('Search in file meta data, like ID3 for MP3 files, EXIF for JPEG... (this option does not increase significantly server load since all data is cached in a MySQL table)', WPFB)),
	'use_path_tags' => array('default' => false, 'title' => __('Use path instead of ID in Shortcode', WPFB), 'type' => 'checkbox', 'desc' => __('Files and Categories are identified by paths and not by their IDs in the generated Shortcodes', WPFB)),
	'no_name_formatting'  => array('default' => false, 'title' => __('Disable Name Formatting', WPFB), 'type' => 'checkbox', 'desc' => __('This will disable automatic formatting/uppercasing file names when they are used as title (e.g. when syncing)', WPFB)),
	
	// file browser
	'disable_footer_credits'  => array('default' => true, 'title' => __('Remove WP-Filebase Footer credits', WPFB), 'type' => 'checkbox', 'desc' => sprintf(__('This disables the footer credits only displayed on <a href="%s">File Browser Page</a>. Why should you keep the credits? Every backlink helps WP-Filebase to get more popular, popularity motivates the developer to continue work on the plugin.', WPFB), get_permalink(WPFB_Core::GetOpt('file_browser_post_id')).'#wpfb-credits')),
	'footer_credits_style'  => array('default' => 'margin:0 auto 2px auto; text-align:center; font-size:11px;', 'title' => __('Footer credits Style', WPFB), 'type' => 'text', 'class' => 'code', 'desc' => __('Set custom CSS style for WP-Filebase footer credits',WPFB),'size'=>80),
	'late_script_loading'	=> array('default' => false, 'title' => __('Late script loading', WPFB), 'type' => 'checkbox', 'desc' => __('Scripts will be included in content, not in header. Enable if your AJAX tree view does not work properly.', WPFB)),
	
	'default_author' => array('default' => '', 'title' => __('Default Author', WPFB), 'desc' => __('This author will be used as form default and when adding files with FTP', WPFB), 'type' => 'text', 'size' => 65),
	'default_roles' => array('default' => array(), 'title' => __('Default User Roles', WPFB), 'desc' => __('These roles are selected by default and will be used for files added with FTP', WPFB), 'type' => 'roles'),
	
	'default_cat' => array('default' => 0, 'title' => __('Default Category', WPFB), 'desc' => __('Preset Category in the file form', WPFB), 'type' => 'cat'),
		
	'languages'				=> array('default' => "English|en\nDeutsch|de", 'title' => __('Languages'), 'type' => 'textarea', 'desc' => &$multiple_entries_desc),
	'platforms'				=> array('default' => "Windows 95|win95\n*Windows 98|win98\n*Windows 2000|win2k\n*Windows XP|winxp\n*Windows Vista|vista\n*Windows 7|win7\nLinux|linux\nMac OS X|mac", 'title' => __('Platforms', WPFB), 'type' => 'textarea', 'desc' => &$multiple_entries_desc, 'nowrap' => true),	
	'licenses'				=> array('default' =>
"*Freeware|free\nShareware|share\nGNU General Public License|gpl|http://www.gnu.org/copyleft/gpl.html\nGNU Lesser General Public License|lgpl\nGNU Affero General Public License|agpl\nCC Attribution-NonCommercial-ShareAlike|ccbyncsa|http://creativecommons.org/licenses/by-nc-sa/3.0/", 'title' => __('Licenses', WPFB), 'type' => 'textarea', 'desc' => &$multiple_entries_desc, 'nowrap' => true),
	'requirements'			=> array('default' =>
"PDF Reader|pdfread|http://www.foxitsoftware.com/pdf/reader/addons.php
Java|java|http://www.java.com/download/
Flash|flash|http://get.adobe.com/flashplayer/
Open Office|ooffice|http://www.openoffice.org/download/index.html
.NET Framework 3.5|.net35|http://www.microsoft.com/downloads/details.aspx?FamilyID=333325fd-ae52-4e35-b531-508d977d32a6",
	'title' => __('Requirements', WPFB), 'type' => 'textarea', 'desc' => $multiple_entries_desc . ' ' . __('You can optionally add |<i>URL</i> to each line to link to the required software/file.', WPFB), 'nowrap' => true),
	
	'default_direct_linking'	=> array('default' => 1, 'title' => __('Default File Direct Linking'), 'type' => 'select', 'desc' => __('', WPFB), 'options' => array(1 => __('Allow direct linking', WPFB), 0 => __('Redirect to post', WPFB) )),	 
		 
	'custom_fields'			=> array('default' => "Custom Field 1|cf1\nCustom Field 2|cf2", 'title' => __('Custom Fields'), 'type' => 'textarea', 'desc' => 
	__('With custom fields you can add even more file properties.',WPFB).' '.$multiple_entries_desc),
	
	
	
	
	
	'template_file'			=> array('default' =>
<<<TPLFILE
<div class="wpfilebase-file-default" onclick="if('undefined' == typeof event.target.href) document.getElementById('wpfb-file-link-%uid%').click();">
  <div class="icon"><a href="%file_url%" target="_blank" title="Download %file_display_name%"><img align="middle" src="%file_icon_url%" alt="%file_display_name%" /></a></div>
  <div class="filetitle">
    <a href="%file_url%" title="Download %file_display_name%" target="_blank" id="wpfb-file-link-%uid%">%file_display_name%</a>
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
	, 'title' => __('Default File Template', WPFB), 'type' => 'textarea', 'desc' => (self::TplFieldsSelect('template_file') . '<br />' . __('The template for attachments', WPFB)), 'class' => 'code'),

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
	, 'title' => __('Category Template', WPFB), 'type' => 'textarea', 'desc' => (self::TplFieldsSelect('template_cat', false, true) . '<br />' . __('The template for category lists (used in the file browser)', WPFB)), 'class' => 'code'),

	'dlclick_js'			=> array('default' =>
<<<JS
if(typeof pageTracker == 'object') {
	pageTracker._trackPageview(file_url); // new google analytics tracker
} else if(typeof urchinTracker == 'function') {	
	urchinTracker(file_url); // old google analytics tracker
}
JS
	, 'title' => __('Download JavaScript', WPFB), 'type' => 'textarea', 'desc' => __('Here you can enter JavaScript Code which is executed when a user clicks on file download link. The following variables can be used: <i>file_id</i>: the ID of the file, <i>file_url</i>: the clicked download url', WPFB), 'class' => 'code'),

	//'max_dls_per_ip'			=> array('default' => 10, 'title' => __('Maximum downloads', WPFB), 'type' => 'number', 'unit' => 'per file, per IP Address', 'desc' => 'Maximum number of downloads of a file allowed for an IP Address. 0 = unlimited'),
	//'archive_lister'			=> array('default' => false, 'title' => __('Archive lister', WPFB), 'type' => 'checkbox', 'desc' => __('Uploaded files are scanned for archives', WPFB)),
	//'enable_ratings'			=> array('default' => false, 'title' => __('Ratings'), 'type' => 'checkbox', 'desc' => ''),
	)
			  
	);
}

static function TplVarsDesc($for_cat=false)
{
	if($for_cat) return array(	
	'cat_name'				=> __('The category name', WPFB),
	'cat_description'		=> __('Short description', WPFB),
	
	'cat_url'				=> __('The category URL', WPFB),
	'cat_path'				=> __('Category path (e.g cat1/cat2/)', WPFB),
	'cat_folder'			=> __('Just the category folder name, not the path', WPFB),
	
	'cat_icon_url'			=> __('URL of the thumbnail or icon', WPFB),
	'cat_small_icon'		=> sprintf(__('HTML image tag for a small icon (height %d)'), 32),
	'cat_has_icon'			=> __('Wether the category has a custom icon (boolean 0/1)'),

	
	'cat_parent_name'		=> __('Name of the parent categories (empty if none)', WPFB),
	'cat_num_files'			=> __('Number of files in the category', WPFB),
	'cat_num_files_total'			=> __('Number of files in the category and all child categories', WPFB),
	
	//'cat_required_level'	=> __('The minimum user level to view this category (-1 = guest, 0 = Subscriber ...)', WPFB),
	'cat_user_can_access'	=> sprintf(__('Variable to check if the %s is accessible (boolean 0/1)', WPFB),__('Category')),
	
	'cat_id'				=> __('The category ID', WPFB),
	'uid'					=> __('A unique ID number to identify elements within a template', WPFB),
	);
	else return array_merge(array(	
	'file_display_name'		=> __('Title', WPFB),
	'file_name'				=> __('Name of the file', WPFB),
	
	'file_url'				=> __('Download URL', WPFB),
	'file_url_encoded'		=> __('Download URL encoded for use in query strings', WPFB),
	
	'file_icon_url'			=> __('URL of the thumbnail or icon', WPFB),
	
	
	'file_size'				=> __('Formatted file size', WPFB),
	'file_date'				=> __('Formatted file date', WPFB),
	'file_version'			=> __('File version', WPFB),	
	'file_author'			=> __('Author'),
	'file_tags'				=> __('Tags'),
	'file_description'		=> __('Short description', WPFB),	
	'file_languages'		=> __('Supported languages', WPFB),
	'file_platforms'		=> __('Supported platforms (operating systems)', WPFB),
	'file_requirements'		=> __('Requirements to use this file', WPFB),
	'file_license'			=> __('License', WPFB),
	
	'file_category'			=> __('The category name', WPFB),
	
	
	'file_thumbnail'		=> __('Name of the thumbnail file', WPFB),	
	'cat_icon_url'			=> __('URL of the category icon (if any)', WPFB),
	'cat_small_icon'		=> __('Category').': '.sprintf(__('HTML image tag for a small icon (height %d)'), 32),
	

	
	//'file_required_level'	=> __('The minimum user level to download this file (-1 = guest, 0 = Subscriber ...)', WPFB),
	'file_user_can_access'	=> sprintf(__('Variable to check if the %s is accessible (boolean 0/1)', WPFB),__('File',WPFB)),
	
	'file_offline'			=> __('1 if file is offline, otherwise 0', WPFB),
	'file_direct_linking'	=> __('1 if direct linking is allowed, otherwise 0', WPFB),
	
	//'file_update_of'		=>
	'file_post_id'			=> __('ID of the post/page this file belongs to', WPFB),
	'file_added_by'			=> __('User Name of the owner', WPFB),
	'file_hits'				=> __('How many times this file has been downloaded.', WPFB),
	//'file_ratings'			=>
	//'file_rating_sum'		=>
	'file_last_dl_ip'		=> __('IP Address of the last downloader', WPFB),
	'file_last_dl_time'		=> __('Time of the last download', WPFB),
	
	'file_extension'		=> sprintf(__('Lowercase file extension (e.g. \'%s\')', WPFB), 'pdf'),
	'file_type'				=> sprintf(__('File content type (e.g. \'%s\')', WPFB), 'image/png'),
	

	'file_post_url'			=> __('URL of the post/page this file belongs to', WPFB),
	
	'file_path'				=> __('Category path and file name (e.g cat1/cat2/file.ext)', WPFB),
	
	'file_id'				=> __('The file ID', WPFB),
	
	'uid'					=> __('A unique ID number to identify elements within a template', WPFB),
	'post_id'				=> __('ID of the current post or page', WPFB),
	'wpfb_url'				=> sprintf(__('Plugin root URL (%s)',WPFB), WPFB_PLUGIN_URI)
	), WPFB_Core::GetCustomFields(true));
}

static function FileSortFields()
{
	return array_merge(array(
	'file_display_name'		=> __('Title', WPFB),
	'file_name'				=> __('Name of the file', WPFB),
	'file_version'			=> __('File version', WPFB),
	
	'file_hits'				=> __('How many times this file has been downloaded.', WPFB),
	'file_size'				=> __('Formatted file size', WPFB),
	'file_date'				=> __('Formatted file date', WPFB),
	'file_last_dl_time'		=> __('Time of the last download', WPFB),
	
	'file_path'				=> __('Relative path of the file'),
	'file_id'				=> __('File ID'),
	
	'file_category_name'	=> __('Category Name', WPFB),
	'file_category'			=> __('Category ID', WPFB),
	
	'file_description'		=> __('Short description', WPFB),	
	'file_author'			=> __('Author', WPFB),
	'file_license'			=> __('License', WPFB),
	
	'file_post_id'			=> __('ID of the post/page this file belongs to', WPFB),
	'file_added_by'			=> __('User Name of the owner', WPFB),
	
	//'file_offline'			=> __('Offline &gt; Online', WPFB),
	//'file_direct_linking'	=> __('Direct linking &gt; redirect to post', WPFB),
	
	), WPFB_Core::GetCustomFields(true));
}

static function CatSortFields()
{
	return array(
	'cat_name'			=> __('Category Name', WPFB),
	'cat_folder'		=> __('Name of the Category folder', WPFB),
	'cat_description'	=> __('Short description', WPFB),	
	
	'cat_path'			=> __('Relative path of the category folder', WPFB),
	'cat_id'			=> __('Category ID', WPFB),
	'cat_parent'		=> __('Parent Category ID', WPFB),
	
	'cat_num_files'		=> __('Number of files directly in the category', WPFB),
	'cat_num_files_total' => __('Number of all files in the category and all sub-categories', WPFB),
	
	'cat_order'			=> __('Custom Category Order', WPFB)
	
	//'cat_required_level' => __('The minimum user level to access (-1 = guest, 0 = Subscriber ...)', WPFB)
	);
}

static function TplFieldsSelect($input, $short=false, $for_cat=false)
{
	$out = __('Add template variable:', WPFB) . ' <select name="_wpfb_tpl_fields" onchange="WPFB_AddTplVar(this, \'' . $input . '\')"><option value="">'.__('Select').'</option>';	
	foreach(self::TplVarsDesc($for_cat) as $tag => $desc)
		$out .= '<option value="'.$tag.'" title="'.$desc.'">'.$tag.($short ? '' : ' ('.$desc.')').'</option>';
	$out .= '</select>';
	$out .= '<small>('.__('For some files there are more tags available. You find a list of all tags below the form when editing a file.',WPFB).'</small>';
	return $out;
}

// copy of wp's copy_dir, but moves everything
static function MoveDir($from, $to)
{
	require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
	require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
	
	$wp_filesystem = new WP_Filesystem_Direct(null);
	
	$dirlist = $wp_filesystem->dirlist($from);

	$from = trailingslashit($from);
	$to = trailingslashit($to);

	foreach ( (array) $dirlist as $filename => $fileinfo ) {
		if ( 'f' == $fileinfo['type'] ) {
			if ( ! $wp_filesystem->move($from . $filename, $to . $filename, true) )
				return false;
			$wp_filesystem->chmod($to . $filename, octdec(WPFB_PERM_FILE));
		} elseif ( 'd' == $fileinfo['type'] ) {
			if ( !$wp_filesystem->mkdir($to . $filename, octdec(WPFB_PERM_DIR)) )
				return false;
			if(!self::MoveDir($from . $filename, $to . $filename))
				return false;
		}
	}
	
	// finally delete the from dir
	@rmdir($from);
	
	return true;
}

static function InsertCategory($catarr)
{	
	$catarr = wp_parse_args($catarr, array('cat_id' => 0, 'cat_name' => '', 'cat_description' => '', 'cat_parent' => 0, 'cat_folder' => '', 'cat_order' => 0));
	extract($catarr, EXTR_SKIP);
	$data = (object)$catarr;

	$cat_id = intval($cat_id);
	$cat_parent = intval($cat_parent);
	$update = ($cat_id > 0); // update or creating??
	$add_existing = !empty($add_existing);
	$cat = $update ? WPFB_Category::GetCat($cat_id) : new WPFB_Category(array('cat_id' => 0));
	$cat->Lock(true);
	
	// some validation
	if (empty($cat_name) && empty($cat_folder)) return array( 'error' => __('You must enter a category name or a folder name.', WPFB) );
	if(!$add_existing && !empty($cat_folder)) {
		$cat_folder = preg_replace('/\s/', ' ', $cat_folder);
		if(!preg_match('/^[0-9a-z-_.+,\'\s()]+$/i', $cat_folder)) return array( 'error' => __('The category folder name contains invalid characters.', WPFB) );	
	}
	wpfb_loadclass('Output');
	if (empty($cat_name)) $cat_name = WPFB_Core::GetOpt('no_name_formatting') ? $cat_folder : WPFB_Output::Filename2Title($cat_folder, false);
	elseif(empty($cat_folder)) $cat_folder = strtolower(str_replace(' ', '_', $cat_name));
	

	$cat->cat_name = trim($cat_name);
	$cat->cat_description = trim($cat_description);
	$cat->cat_exclude_browser = (int)!empty($cat_exclude_browser);
	$cat->cat_order = intval($cat_order);
		
	// handle parent cat
	if($cat_parent <= 0 || $cat_parent == $cat_id) {
		$cat_parent = 0;
		$pcat = null;
	} else {
		$pcat = WPFB_Category::GetCat($cat_parent);
		if($pcat == null || ($update && $cat->IsAncestorOf($pcat))) $cat_parent = $cat->cat_parent;
	}
	
	// this will (eventually) inherit permissions:
	$result = $cat->ChangeCategoryOrName($cat_parent, $cat_folder, $add_existing);
	if(!empty($result['error'])) return $result;

	// explicitly set permissions:
	if(!empty($data->cat_perm_explicit) && isset($data->cat_user_roles))
		$cat->SetReadPermissions((empty($data->cat_user_roles) || count(array_filter($data->cat_user_roles)) == 0) ? array() : $data->cat_user_roles);		
	
	$current_user = wp_get_current_user();
	if(!$update && !empty($current_user)) $cat->cat_owner = $current_user->ID;
	if(empty($cat->cat_owner)) $cat->cat_owner = 0;	
	
	// apply permissions to children
	if($update && !empty($cat_child_apply_perm))
	{
		$cur = $cat->GetReadPermissions();
		$childs = $cat->GetChildFiles(true);
		foreach($childs as $child) $child->SetReadPermissions($cur);
		
		$childs = $cat->GetChildCats(true);
		foreach($childs as $child) {
			$child->Lock(true);
			$child->SetReadPermissions($cur);
			$child->Lock(false);
			$child->DBSave();
		}
	}
		
	// icon
	if(!empty($cat_icon_delete)) {
		@unlink($cat->GetThumbPath());
		$cat->cat_icon = null;
	}
	if(!empty($cat_icon) && @is_uploaded_file($cat_icon['tmp_name']) && !empty($cat_icon['name'])) {
		$ext = strtolower(substr($cat_icon['name'], strrpos($cat_icon['name'], '.')+1));
		if($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
			if(!empty($cat->cat_icon))
				@unlink($cat->GetThumbPath());
			$cat->cat_icon = '_caticon.'.$ext;
			if(!@move_uploaded_file($cat_icon['tmp_name'], $cat->GetThumbPath()))
				return array( 'error' => __( 'Unable to move category icon!', WPFB));	
			@chmod($cat->GetThumbPath(), octdec(WPFB_PERM_FILE));
		}
	}
	elseif($add_existing)
	{
		static $folder_icons = array('_caticon.jpg', '_caticon.png', '_caticon.gif', 'folder.jpg', 'folder.png', 'folder.gif', 'cover.jpg');
		$cat_path = $cat->GetLocalPath(); 
		foreach($folder_icons as $fi) {
			$fi = "$cat_path/$fi";
			if(is_file($fi)) {
				$ext = strtolower(substr($fi, strrpos($fi,'.')+1));
				$cat->cat_icon = "_caticon.$ext";
				if(!@rename($fi, $cat->GetThumbPath()))
					return array( 'error' => __( 'Unable to move category icon!', WPFB));
				break;
			}
		}
	}
	
	// save into db
	$cat->Lock(false);
	$result = $cat->DBSave();	
	if(!empty($result['error']))
		return $result;		
	$cat_id = (int)$result['cat_id'];	
	
	return array( 'error' => false, 'cat_id' => $cat_id);
}

static function InsertFile($data, $in_gui =false)
{
	if(!is_object($data)) $data = (object)$data;
	
	$file_id = isset($data->file_id) ? (int)$data->file_id : 0;
	$file = null;
	if($file_id > 0) {
		$file = WPFB_File::GetFile($file_id);
		if($file == null) $file_id = 0;
	}	
	$update = ($file_id > 0 && $file != null && $file->is_file);	
	if(!$update) $file = new WPFB_File(array('file_id' => 0));
	$file->Lock(true);
	$add_existing = !empty($data->add_existing); // if the file is added by a sync (not uploaded)
	
	if(!$add_existing) self::SyncCustomFields();  // dont sync custom fields when file syncing!
	
	if(!empty($data->file_flash_upload)) { // check for flash upload and validate!
		$file_flash_upload = json_decode($data->file_flash_upload, true);
		$file_flash_upload['tmp_name'] = WPFB_Core::UploadDir().'/'.str_replace('../','',$file_flash_upload['tmp_name']);
		if(is_file($file_flash_upload['tmp_name']))
			$data->file_upload = $file_flash_upload;
	}
	// are we uploading a file?
	$upload = (!$add_existing && ((@is_uploaded_file($data->file_upload['tmp_name']) || !empty($data->file_flash_upload)) && !empty($data->file_upload['name'])));
	$remote_upload = (!$add_existing && !$upload && !empty($data->file_is_remote) && !empty($data->file_remote_uri) && (!$update || $file->file_remote_uri != $data->file_remote_uri));
	$remote_redirect = !empty($data->file_remote_redirect) && !empty($data->file_remote_uri);
	if($remote_redirect) $remote_scan = !empty($data->file_remote_scan);
	
	// are we uploading a thumbnail?
	$upload_thumb = (!$add_existing && @is_uploaded_file($data->file_upload_thumb['tmp_name']));

	if($upload_thumb && !(WPFB_FileUtils::FileHasImageExt($data->file_upload_thumb['name']) && WPFB_FileUtils::IsValidImage($data->file_upload_thumb['tmp_name'])))
		return array( 'error' => __('Thumbnail is not a valid image!.', WPFB) );
	
	if($remote_upload) {
		unset($file_src_path);
		$remote_file_info = self::GetRemoteFileInfo($data->file_remote_uri);
		if(empty($remote_file_info))
			return array('error' => sprintf( __( 'Could not get file information from %s!', WPFB), $data->file_remote_uri));
		$file_name = $remote_file_info['name'];
		if($remote_file_info['size'] > 0) $file->file_size = $remote_file_info['size'];
		if($remote_file_info['time'] > 0) $file->SetModifiedTime($remote_file_info['time']);
	} else {
		$file_src_path = $upload ? $data->file_upload['tmp_name'] : ($add_existing ? $data->file_path : null);
		$file_name = $upload ? str_replace('\\','',$data->file_upload['name']) : ((empty($file_src_path) && $update) ? $file->file_name : basename($file_src_path));		
	}
	
	if($upload) $data->file_rename = null;
		
	
	// VALIDATION
	$current_user = wp_get_current_user();
	if(empty($data->frontend_upload) && !$add_existing && empty($current_user->ID)) return array( 'error' => __('Could not get user id!', WPFB) );	
	
	if(!$update && !$add_existing && !$upload && !$remote_upload) return array( 'error' => __('No file was uploaded.', WPFB) );

	// check extension
	if($upload || $add_existing) {
		if(!self::IsAllowedFileExt($file_name)) {
			if(isset($file_src_path)) @unlink($file_src_path);
			return array( 'error' => sprintf( __( 'The file extension of the file <b>%s</b> is forbidden!', WPFB), $file_name ) );
		}
	}
	// check url
	if($remote_upload && !preg_match('/^https?:\/\//', $data->file_remote_uri))	return array( 'error' => __('Only HTTP links are supported.', WPFB) );
	
	
	// do some simple file stuff
	if($update && (!empty($data->file_delete_thumb) || $upload_thumb)) $file->DeleteThumbnail(); // delete thumbnail if user wants to	
	if($update && ($upload||$remote_upload)) $file->Delete(); // if we update, delete the old file
	

	// handle display name and version
	if(isset($data->file_version)) $file->file_version = $data->file_version;	
	if(isset($data->file_display_name)) $file->file_display_name = $data->file_display_name;	
	$result = self::ParseFileNameVersion($file_name, $file->file_version);	
	if(empty($file->file_version)) $file->file_version = $result['version'];
	if(empty($file->file_display_name)) $file->file_display_name = $result['title'];	
	
	// handle category & name
	$file_category = intval($data->file_category);
	$new_cat = null;
	if ($file_category > 0 && ($new_cat=WPFB_Category::GetCat($file_category)) == null) $file_category = 0;
	
	
	// this inherits permissions as well:
	$result = $file->ChangeCategoryOrName($file_category, empty($data->file_rename) ? $file_name : $data->file_rename, $add_existing, !empty($data->overwrite));
	if(!empty($result['error'])) return $result;
	
	// explicitly set permissions:
	if(!empty($data->file_perm_explicit) && isset($data->file_user_roles))
		$file->SetReadPermissions((empty($data->file_user_roles) || count(array_filter($data->file_user_roles)) == 0) ? array() : $data->file_user_roles);	

	// if there is an uploaded file 
	if($upload) {
		$file_dest_path = $file->GetLocalPath();
		$file_dest_dir = dirname($file_dest_path);
		if(@file_exists($file_dest_path)) return array( 'error' => sprintf( __( 'File %s already exists. You have to delete it first!', WPFB), $file->GetLocalPath() ) );
		if(!is_dir($file_dest_dir)) self::Mkdir($file_dest_dir);
		// try both move_uploaded_file for http, rename for flash uploads!
		if(!(move_uploaded_file($file_src_path, $file_dest_path) || rename($file_src_path, $file->GetLocalPath())) || !@file_exists($file->GetLocalPath())) return array( 'error' => sprintf( __( 'Unable to move file %s! Is the upload directory writeable?', WPFB), $file->file_name ).' '.$file->GetLocalPathRel());	
	} elseif($remote_upload) {
		if(!$remote_redirect || $remote_scan) {	
			$tmp_file = self::GetTmpFile($file->file_name);
			$result = self::SideloadFile($data->file_remote_uri, $tmp_file, $in_gui ? $remote_file_info['size'] : -1);
			if(!empty($result['error'])) return $result;
			if(!rename($tmp_file, $file->GetLocalPath())) return array('error' => 'Could not rename temp file!');
		}
	} elseif(!$add_existing && !$update) {
		return array( 'error' => __('No file was uploaded.', WPFB) );
	}
	
	// handle date/time stuff
	if(!empty($data->file_date)) {
		$file->file_date = $data->file_date;
	} elseif($add_existing || empty($file->file_date)) {		
		$file->file_date = gmdate('Y-m-d H:i:s', file_exists($file->GetLocalPath()) ? filemtime($file->GetLocalPath()) : time());
	}
	
	// get file info
	if(!($update && $remote_redirect) && is_file($file->GetLocalPath()) && empty($data->no_scan))
	{
		$file->file_size = WPFB_FileUtils::GetFileSize($file->GetLocalPath());
		$file->file_mtime = filemtime($file->GetLocalPath());
		$old_hash = $file->file_hash;
		$file->file_hash = WPFB_Admin::GetFileHash($file->GetLocalPath());
		
		// only analyze files if changed!
		if($upload || !$update || $file->file_hash != $old_hash)
		{
			wpfb_loadclass('GetID3');
			$file_info = WPFB_GetID3::AnalyzeFile($file);
				
			if(!empty($file_info['comments']['picture'][0]['data']))
				$cover_img =& $file_info['comments']['picture'][0]['data'];
			elseif(!empty($file_info['id3v2']['APIC'][0]['data']))
				$cover_img =& $file_info['id3v2']['APIC'][0]['data'];
			else $cover_img = null;
			
			if(!$upload_thumb && empty($data->file_thumbnail) && !empty($cover_img))
			{
				$cover = $file->GetLocalPath();
				$cover = substr($cover,0,strrpos($cover,'.')).'.jpg';
				file_put_contents($cover, $cover_img);
				$file->CreateThumbnail($cover, true);
				@unlink($cover);
			}
		}
	} else {
		if(isset($data->file_size)) $file->file_size = $data->file_size;
		if(isset($data->file_hash)) $file->file_hash = $data->file_hash;
	}
	
	if($remote_redirect) {
		if(file_exists($file->GetLocalPath()))
			@unlink($file->GetLocalPath()); // when download redircet the actual files is not needed anymore
	} else {
		// set permissions
		@chmod ($file->GetLocalPath(), octdec(WPFB_PERM_FILE));
		$file->file_remote_uri = $data->file_remote_uri = '';	// no redirection, URI is not neede anymore		
	}
	
	if(!empty($data->file_languages)) $file->file_language = implode('|', $data->file_languages);
	if(!empty($data->file_platforms)) $file->file_platform = implode('|', $data->file_platforms);
	if(!empty($data->file_requirements)) $file->file_requirement = implode('|', $data->file_requirements);
	
	if(isset($data->file_tags)) $file->SetTags($data->file_tags);

	$file->file_offline = (int)(!empty($data->file_offline));
	
	if(!isset($data->file_direct_linking))
		$data->file_direct_linking = WPFB_Core::$settings->default_direct_linking;
	$file->file_direct_linking = intval($data->file_direct_linking);

	if(isset($data->file_post_id))
		$file->SetPostId(intval($data->file_post_id));
		
	$file->file_author = isset($data->file_author) ? $data->file_author : WPFB_Core::GetOpt('default_author');
	
	$var_names = array('remote_uri', 'description', 'hits', 'license'
	);
	for($i = 0; $i < count($var_names); $i++)
	{
		$vn = 'file_' . $var_names[$i];
		if(isset($data->$vn)) $file->$vn = $data->$vn;
	}
	
	
	// custom fields!
	$var_names = array_keys(WPFB_Core::GetCustomFields(true));
	for($i = 0; $i < count($var_names); $i++)
	{
		$vn = $var_names[$i];
		if(isset($data->$vn)) $file->$vn = $data->$vn;
	}	

	// set the user id
	if(!$update && !empty($current_user)) $file->file_added_by = $current_user->ID;	

	// if thumbnail was uploaded
	if($upload_thumb)
	{
		// delete the old thumbnail (if existing)
		$file->DeleteThumbnail();
		
		$thumb_dest_path = dirname($file->GetLocalPath()) . '/thumb_' . $data->file_upload_thumb['name'];
				
		if(@move_uploaded_file($data->file_upload_thumb['tmp_name'], $thumb_dest_path))
		{
			$file->CreateThumbnail($thumb_dest_path, true);
		}
	}
	
	
	// save into db
	$file->Lock(false);
	$result = $file->DBSave();
	if(!empty($result['error'])) return $result;		
	$file_id = (int)$result['file_id'];
	
	if(!empty($file_info))
		WPFB_GetID3::StoreFileInfo($file_id, $file_info);
	
	// create thumbnail
	if($upload || $remote_upload || $add_existing) {
		if($add_existing && !empty($data->file_thumbnail)) {
			$file->file_thumbnail = $data->file_thumbnail; // we already got the thumbnail on disk!		
			$file->DBSave();
		}
		elseif(empty($file->file_thumbnail) && !$upload_thumb && (!$remote_redirect || $remote_scan) && empty($data->no_scan)) {
			$file->CreateThumbnail();	// check if the file is an image and create thumbnail
			$file->DBSave();
		}
	}

	return array( 'error' => false, 'file_id' => $file_id, 'file' => $file);
}




static function ParseFileNameVersion($file_name, $file_version) {
	$fnwv = substr($file_name, 0, strrpos($file_name, '.'));// remove extension
	if(empty($file_version)) {
		$matches = array();		
		if(preg_match('/[-_\.]v?([0-9]{1,2}\.[0-9]{1,2}(\.[0-9]{1,2}){0,2})(-[a-zA-Z_]+)?$/', $fnwv, $matches)) {
			$file_version = $matches[1];
			if((strlen($fnwv)-strlen($matches[0])) > 1)
				$fnwv = substr($fnwv, 0, -strlen($matches[0]));
		}	
	} elseif(substr($fnwv, -strlen($file_version)) == $file_version) {		
		$fnwv = trim(substr($fnwv, 0, -strlen($file_version)), '-');
	}
	$title = WPFB_Core::GetOpt('no_name_formatting') ? $fnwv : wpfb_call('Output', 'Filename2Title', array($fnwv, false), true);	
	return array('title' => empty($title) ? $file_name : $title, 'version' => $file_version);
}


// size, type, name, time
static function GetRemoteFileInfo($url)
{
	wpfb_loadclass('Download');
	
	$info = array();
	$path = parse_url($url,PHP_URL_PATH);
	
	$headers = self::HttpGetHeaders($url);	
	if (empty($headers)) return null;
	
	$info['size'] = isset($headers['content-length']) ? $headers['content-length'] : -1;	
	$info['type'] = isset($headers['content-type']) ? strtolower($headers['content-type']) : null;	
	$info['time'] = isset($headers['last-modified']) ? @strtotime($headers['last-modified']) : 0;
	
	// check for filename header
	if(!empty($headers['content-disposition'])) {
		$matches = array();
		if(preg_match('/filename="(.+)"/', $headers['content-disposition'], $matches) == 1)
			$info['name'] = $matches[1];
	}
	
	if(empty($info['name']))
		$info['name'] = basename($path); 
	
	// compare extension type with http header content-type, if they are different deterime proper extension from http content-type
	$exType = WPFB_Download::GetFileType($info['name']);	
	if($exType != $info['type'] && ($e=WPFB_Download::FileType2Ext($info['type'])) != null)
		$info['name'] .= '.'.$e;
		
	return $info;
}

public static function SideloadFile($url, $dest_file = null, $size_for_progress = 0) {
	//WARNING: The file is not automatically deleted, The script must unlink() the file.
	@ini_set('max_execution_time', '0');
	@set_time_limit(0);
	require_once(ABSPATH . 'wp-admin/includes/file.php');	
		
	if(!$url) return array('error' => __('Invalid URL Provided.'));
	
	if(empty($dest_file)) { // if no dest file set, create temp file
		$fi = self::GetRemoteFileInfo($url);
		if(empty($fi)) return array('error' => sprintf( __( 'Could not get file information from %s!', WPFB), $url));		
		if(!($dest_file = self::GetTmpFile($fi['name']))) return array('error' => __('Could not create Temporary file.'));
	}
	
	if( $size_for_progress >= self::$MIN_SIZE_FOR_PROGRESSBAR) {
		if(!class_exists('progressbar')) include_once(WPFB_PLUGIN_ROOT.'extras/progressbar.class.php');
		$progress_bar = new progressbar(0, $size_for_progress, 300, 30, '#aaa');
		echo "<p><code>".esc_html($url)."</code> ...</p>";
		$progress_bar->print_code();
	} else $progress_bar = null;

	wpfb_loadclass('Download');
	$result = WPFB_Download::SideloadFile($url, $dest_file, $progress_bar);
	if(!empty($result['error'])) return $result;
	
	return array('error'=>false,'file'=>$dest_file);
}

static function CreateCatTree($file_path)
{
	$rel_path = trim(substr($file_path, strlen(WPFB_Core::UploadDir())),'/');
	$rel_dir = dirname($rel_path);
	
	if(empty($rel_dir) || $rel_dir == '.')
		return 0;
	
	$last_cat_id = 0;
	$dirs = explode('/', $rel_dir);
	foreach($dirs as $dir) {
		if(empty($dir) || $dir == '.')
			continue;
		$cat = WPFB_Item::GetByName($dir, $last_cat_id);
		if($cat != null && $cat->is_category) {
			$last_cat_id = $cat->cat_id;
		} else {
			$result = self::InsertCategory(array('add_existing' => true, 'cat_parent' => $last_cat_id, 'cat_folder' => $dir));
			if(!empty($result['error']))
				return $result;
			elseif(empty($result['cat_id']))
				wp_die('Could not create category!');
			else
				$last_cat_id = intval($result['cat_id']);
		}
	}	
	return $last_cat_id;
}

static function AddExistingFile($file_path, $thumb=null)
{
	$cat_id = self::CreateCatTree($file_path);
	
	// check if file still exists (it could be renamed while creating the category if its used for category icon!)
	if(!is_file($file_path))
		return array();
		
	return self::InsertFile(array(
		'add_existing' => true,
		'file_category' => $cat_id,
		'file_path' => $file_path,
		'file_thumbnail' => $thumb
	));
}

static function WPCacheRejectUri($add_uri, $remove_uri='')
{
	// changes the settings of wp cache
	
	global $cache_rejected_uri;
	
	$added = false;

	if(!isset($cache_rejected_uri))
		return false;

	// remove uri
	if(!empty($remove_uri))
	{
		$new_cache_rejected_uri = array();
			
		foreach($cache_rejected_uri as $i => $v)
		{
			if($v != $remove_uri)
				$new_cache_rejected_uri[$i] = $v;
		}
		
		$cache_rejected_uri = $new_cache_rejected_uri;
	}
	
	if(!in_array($add_uri, $cache_rejected_uri))
	{
		$cache_rejected_uri[] = $add_uri;
		$added = true;
	}
	
	return (self::WPCacheSaveRejectedUri() && $added);
}

static function WPCacheSaveRejectedUri()
{
	global $cache_rejected_uri, $wp_cache_config_file;
	
	if(!isset($cache_rejected_uri) || empty($wp_cache_config_file) || !function_exists('wp_cache_replace_line'))
		return false;	
	
	$text = var_export($cache_rejected_uri, true);
	$text = preg_replace('/[\s]+/', ' ', $text);
	wp_cache_replace_line('^ *\$cache_rejected_uri', "\$cache_rejected_uri = $text;", $wp_cache_config_file);

	return true;
}

static function MakeFormOptsList($opt_name, $selected = null, $add_empty_opt = false)
{
	$options = WPFB_Core::GetOpt($opt_name);	
	$options = explode("\n", $options);
	$def_sel = (is_null($selected) && !is_string($selected));
	$list = $add_empty_opt ? ('<option value=""' . ( (is_string($selected) && $selected == '') ? ' selected="selected"' : '') . '>-</option>') : '';
	$selected = explode('|', $selected);
	
	foreach($options as $opt)
	{
		$opt = trim($opt);
		$tmp = explode('|', $opt);
		$list .= '<option value="' . esc_attr(trim($tmp[1])) . '"' . ( (($def_sel && $opt{0} == '*') || (!$def_sel && in_array($tmp[1], $selected)) ) ? ' selected="selected"' : '' ) . '>' . esc_html(trim($tmp[0], '*')) . '</option>';
	}
	
	return $list;
}

static function AdminTableSortLink($order)
{
	$desc = (!empty($_GET['order']) && $order == $_GET['order'] && empty($_GET['desc']));
	$uri = add_query_arg(array('order' => $order, 'desc' => $desc ? '1' : '0'));
	return $uri;
}

static function IsAllowedFileExt($ext)
{
	static $srv_script_exts = array('php', 'php3', 'php4', 'php5', 'phtml', 'cgi', 'pl', 'asp', 'py', 'aspx', 'jsp', 'jhtml', 'jhtm');	
	
	if(WPFB_Core::GetOpt('allow_srv_script_upload'))
		return true;
	
	$ext = strtolower($ext);	
	$p = strrpos($ext, '.');
	if($p !== false)
		$ext = substr($ext, $p + 1);
	
	return !in_array($ext, $srv_script_exts);
}

static function UninstallPlugin()
{
	wpfb_loadclass('Setup');
	WPFB_Setup::RemoveOptions();
	WPFB_Setup::DropDBTables();
	// TODO: remove user opt
}

static function PrintForm($name, $item=null, $vars=array())
{
	wpfb_loadclass('Output');
	WPFB_Core::PrintJS(); /* only required for wpfbConf */
	?>
<script type="text/javascript">
//<![CDATA[

jQuery(document).ready(function($){
	WPFB_formCategoryChanged();
});

function WPFB_formCategoryChanged()
{
	var catId = jQuery('#file_category,#cat_parent').val();
	if(!catId || catId <= 0) {
		jQuery('#<?php echo $name ?>_inherited_permissions_label').html('<?php echo WPFB_Output::RoleNames(WPFB_Core::GetOpt('default_roles'), true); ?>');
	} else {
		jQuery.ajax({
			url: wpfbConf.ajurl,
			data: {action:"catinfo","id":catId},
			dataType: "json",
			success: (function(data){jQuery('#<?php echo $name ?>_inherited_permissions_label').html(data.roles_str);})
		});
	}
}
//]]>
</script>
	<?php
	extract($vars);
	if(is_writable(WPFB_Core::UploadDir()))
		include(WPFB_PLUGIN_ROOT . 'lib/wpfb_form_' . $name . '.php');
}

// creates the folder structure
static function Mkdir($dir)
{
	$parent = trim(dirname($dir), '.');
	if(trim($parent,'/\\') != '' && !is_dir($parent)) {
		$result = self::Mkdir($parent);
		if($result['error'])
			return $result;
	}
	return array('error' => !(@mkdir($dir, octdec(WPFB_PERM_DIR)) && @chmod($dir, octdec(WPFB_PERM_DIR))), 'dir' => $dir, 'parent' => $parent);
}

static function ParseTpls() {
	wpfb_loadclass('TplLib');
	
	// parse default
	WPFB_Core::UpdateOption('template_file_parsed', WPFB_TplLib::Parse(WPFB_Core::GetOpt('template_file')));
	WPFB_Core::UpdateOption('template_cat_parsed', WPFB_TplLib::Parse(WPFB_Core::GetOpt('template_cat')));
		
	// parse custom
	update_option(WPFB_OPT_NAME.'_ptpls_file', WPFB_TplLib::Parse(WPFB_Core::GetFileTpls())); 
	update_option(WPFB_OPT_NAME.'_ptpls_cat', WPFB_TplLib::Parse(WPFB_Core::GetCatTpls())); 
}


static function AddFileWidget() {
	wpfb_loadclass('Category');
	self::PrintForm('file', null, array('in_widget'=>true));
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
		if(defined('WPLANG') && WPLANG && WPLANG != '' && strpos(WPLANG, '_') > 0) {
			if(in_array(WPLANG, $supported_langs))
				$lang = WPLANG;
			else {
				$l = strtolower(substr(WPLANG, 0, strpos(WPLANG, '_')));
				if(!empty($l)) {
					foreach($supported_langs as $sl) {
						$pos = strpos($sl,$l);
						if($pos !== false && $pos == 0) {
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
    (function() {
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
public function ProcessWidgetUpload(){	
	$content = '';
	$title = '';

	if(!WPFB_Core::GetOpt('frontend_upload') && !current_user_can('upload_files'))
		wp_die(__('Cheatin&#8217; uh?'). " (disabled)");

	{
		$nonce_action = $_POST['prefix']."=&cat=".((int)$_POST['cat'])."&overwrite=".((int)$_POST['overwrite'])."&file_post_id=".((int)$_POST['file_post_id']);
		// nonce/referer check (security)
		if(!wp_verify_nonce($_POST['wpfb-file-nonce'],$nonce_action) || !check_admin_referer($nonce_action,'wpfb-file-nonce'))
			wp_die(__('Cheatin&#8217; uh?') . ' (nonce)');
	}
		
	// if category is set in widget options, force to use this. security done with nonce checking ($_POST['cat'] is reliable)
	if($_POST['cat'] >= 0) $_POST['file_category'] = $_POST['cat'];
	$result = WPFB_Admin::InsertFile(array_merge(stripslashes_deep($_POST), $_FILES, array('frontend_upload' => true, 'form' => empty($form) ? null : $form)));
	if(isset($result['error']) && $result['error']) {
		$content .= '<div id="message" class="updated fade"><p>'.$result['error'].'</p></div>';
		$title .= __('Error ');
	} else {
		// success!!!!
		$content = __('The File has been uploaded successfully.', WPFB);
		$file = WPFB_File::GetFile($result['file_id']);
		$content .= $file->GenTpl2();
		$title = trim(__('File added.', WPFB),'.');
	}
	
	wpfb_loadclass('Output');
	WPFB_Output::GeneratePage($title, $content, !empty($_POST['form_tag'])); // prepend to content if embedded form!
}

public function ProcessWidgetAddCat() {
	$content = '';
	$title = '';
	
	// nonce/referer check (security)
	$nonce_action = $_POST['prefix'];
	if(!wp_verify_nonce($_POST['wpfb-cat-nonce'],$nonce_action) || !check_admin_referer($nonce_action,'wpfb-cat-nonce'))
		wp_die(__('Cheatin&#8217; uh?'));
	
	$result = WPFB_Admin::InsertCategory(array_merge(stripslashes_deep($_POST), $_FILES));
	if(isset($result['error']) && $result['error']) {
		$content .= '<div id="message" class="updated fade"><p>'.$result['error'].'</p></div>';
		$title .= __('Error ');
	} else {
		// success!!!!
		$content = _e('New Category created.',WPFB);
		$cat = WPFB_Category::GetCat($result['cat_id']);
		$content .= $cat->GenTpl2();
		$title = trim(__('Category added.', WPFB),'.');
	}
	
	wpfb_loadclass('Output');
	WPFB_Output::GeneratePage($title, $content);	
}

public static function SyncCustomFields($remove=false) {
	global $wpdb;
	
	$messages = array();
	
	$cols = $wpdb->get_col("SHOW COLUMNS FROM $wpdb->wpfilebase_files LIKE 'file_custom_%'");
	
	$custom_fields = WPFB_Core::GetCustomFields();
	foreach($custom_fields as $ct => $cn) {		
		if(!in_array('file_custom_'.$ct, $cols)) {
			$messages[] = sprintf(__($wpdb->query("ALTER TABLE $wpdb->wpfilebase_files ADD `file_custom_".$wpdb->escape($ct)."` TEXT NOT NULL") ?
			"Custom field '%s' added." : "Could not add custom field '%s'!", WPFB), $cn);
		}
	}
	
	if(!$remove) {
		foreach($cols as $cf) {
			$ct = substr($cf, 12); // len(file_custom_)
			if(!isset($custom_fields[$ct]))
				$messages[] = sprintf(__($wpdb->query("ALTER TABLE $wpdb->wpfilebase_files DROP `$cf`") ?
				"Custom field '%s' removed!" : "Could not remove custom field '%s'!", WPFB), $ct);
		}
	}
	
	return $messages;
}

public function SettingsUpdated($old, &$new) {
	$messages = array();
	wpfb_call('Setup','ProtectUploadPath');
			
	// custom fields:
	$messages += WPFB_Admin::SyncCustomFields();
	
	if($old['thumbnail_path'] != $new['thumbnail_path']) {

		update_option(WPFB_OPT_NAME, $old); // temporaly restore old settings
		WPFB_Core::$settings = (object)$old;
		
		$items = array_merge(WPFB_File::GetFiles2(),WPFB_Category::GetCats());			
		$old_thumbs = array();				
		foreach($items as $i => $item) $old_thumbs[$i] = $item->GetThumbPath(true);

		update_option(WPFB_OPT_NAME, $new); // restore new settings
		WPFB_Core::$settings = (object)$new;
		
		$n = 0;		
		foreach($items as $i => $item) {
			if(!empty($old_thumbs[$i]) && is_file($old_thumbs[$i])) {
				$new_path = $item->GetThumbPath(true);
				$dir = dirname($new_path);
				if(!is_dir($dir)) self::Mkdir($dir);
				if(rename($old_thumbs[$i], $new_path)) $n++;
				else $messages[] = sprintf(__('Could not move thumnail %s to %s.',WPFB), $old_thumbs[$i], $new_path);
			}	
		}
		
		if(count($n > 0)) $messages[] = sprintf(__('%d Thumbnails moved.',WPFB), $n);
	}
	
	
	flush_rewrite_rules();
	
	return $messages;
}

static function RolesCheckList($field_name, $selected_roles=array(), $display_everyone=true) {
	global $wp_roles;
	$all_roles = $wp_roles->roles;
	if(empty($selected_roles)) $selected_roles = array();
	elseif(!is_array($selected_roles)) $selected_roles = explode('|', $selected_roles);
	?>
<div id="<?php echo $field_name; ?>-wrap" class=""><input value="" type="hidden" name="<?php echo $field_name; ?>[]" />
	<ul id="<?php echo $field_name; ?>-list" class="wpfilebase-roles-checklist">
<?php
	if($display_everyone) echo "<li id='{$field_name}_none'><label class='selectit'><input value='' type='checkbox' name='{$field_name}[]' id='in-{$field_name}_none' ".(empty($selected_roles)?"checked='checked'":"")." onchange=\"jQuery('[id^=in-$field_name-]').prop('checked', false);\" /> <i>".(is_string($display_everyone)?$display_everyone:__('Everyone',WPFB))."</i></label></li>";
	foreach ( $all_roles as $role => $details ) {
		$name = translate_user_role($details['name']);
		$sel = in_array($role, $selected_roles);
		echo "<li id='$field_name-$role'><label class='selectit'><input value='$role' type='checkbox' name='{$field_name}[]' id='in-$field_name-$role' ".($sel?"checked='checked'":""). /*" ".((empty($selected_roles)&&$display_everyone)? "disabled='disabled'":"").*/ " /> $name</label></li>";
		if($sel) unset($selected_roles[array_search($role, $selected_roles)]); // rm role from array
	}
	
	// other roles/users, that were not listed
	foreach($selected_roles as $role) {
		$name = substr($role,0,3) == '_u_' ? (substr($role, 3).' (user)') : $role;
		echo "<li id='$field_name-$role'><label class='selectit'><input value='$role' type='checkbox' name='{$field_name}[]' id='in-$field_name-$role' checked='checked' /> $name</label></li>";
	}
	
?>
	</ul>
	

	
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($){
	jQuery('#<?php echo $field_name; ?>-list input[value!=""]').change(function() {
		jQuery('#<?php echo "in-{$field_name}_none"; ?>').prop('checked', false);
	});
});
//]]>
</script>
</div>
<?php
}

static function HttpGetHeaders($url) {
	require_once( ABSPATH . WPINC . "/http.php" );
	$response = wp_remote_head($url);
	return is_wp_error( $response ) ? null : wp_remote_retrieve_headers( $response );
}

static function GetTmpFile($name='') {
	$dir = WPFB_Core::UploadDir().'/.tmp/';
	self::Mkdir($dir);
	return wp_tempnam($name, $dir);
}

static function GetTmpPath($name) {
	$dir = WPFB_Core::UploadDir().'/.tmp/'.uniqid($name);
	self::Mkdir($dir);
	return $dir;
}

static function LockUploadDir($lock=true)
{
	$f = WPFB_Core::UploadDir().'/.lock';
	return $lock ? touch($f) : unlink($f);
}

static function UploadDirIsLocked()
{
	$f = WPFB_Core::UploadDir().'/.lock';
	return file_exists($f) && ( (time()-filemtime($f)) < 120 ); // max lock for 120 seconds without update!
}

static function GetFileHash($filename)
{
	static $use_php_func = -1;
	if($use_php_func === -1) $use_php_func = strpos(@ini_get('disable_functions').','.@ini_get('suhosin.executor.func.blacklist'), 'exec') !== false;
	if($use_php_func) return md5_file($filename);
	$hash = substr(trim(substr(@exec("md5sum \"$filename\""), 0, 33),"\\ \t"), 0, 32); // on windows, hash starts with \ if not in same dir!
	if(empty($hash) && file_exists($filename)) {
		$use_php_func = true;
		return md5_file($filename);
	}
	return $hash;
}

static function CurUserCanUpload()
{
	return (current_user_can('upload_files'));
}

static function CurUserCanCreateCat()
{
	return  current_user_can('manage_categories');
}
}