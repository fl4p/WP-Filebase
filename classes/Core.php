<?php

class WPFB_Core {
	
static $load_js = false;
static $file_browser_search = false;
static $file_browser_item = null;
static $post_url_cache = array();

/**
 * WP-Filebase Settings Object
 *
 * @since 3.0.14
 * @access public
 * @var WPFB_Settings
 */
static $settings;

static function InitClass()
{	
	if(defined('WPFB_NO_CORE_INIT')) return;	// used with CSS proxy
	
	//Load settings
	self::$settings = (object)get_option(WPFB_OPT_NAME);
	
	// load lang
	$lang_dir = defined('WPFB_LANG_DIR') ? ('../../'.WPFB_LANG_DIR) : basename(WPFB_PLUGIN_ROOT).'/languages';
	load_plugin_textdomain(WPFB, 'wp-content/plugins/'.$lang_dir, $lang_dir);

	add_action('parse_query', array(__CLASS__, 'ParseQuery')); // search
	add_action('wp_enqueue_scripts', array(__CLASS__, 'EnqueueScripts'));
	add_action('wp_footer', array(__CLASS__, 'Footer'));	
	add_action('generate_rewrite_rules', array(__CLASS__, 'GenRewriteRules'));
	add_action(WPFB.'_cron', array(__CLASS__, 'Cron'));
	add_action('wpfilebase_sync', array(__CLASS__, 'Sync')); // for Developers: New wp-filebase actions
	
	add_shortcode('wpfilebase', array(__CLASS__, 'ShortCode'));
	
	// for attachments and file browser
	add_filter('the_content',	array(__CLASS__, 'ContentFilter'), 10); // must be lower than 11 (before do_shortcode) and after wpautop (>9)
	add_filter('ext2type', array(__CLASS__, 'Ext2TypeFilter'));

	
	
	// register treeview stuff
	//wp_register_script('jquery-cookie', WPFB_PLUGIN_URI.'extras/jquery/jquery.cookie.js', array('jquery'));
	wp_register_script('jquery-treeview', WPFB_PLUGIN_URI.'extras/jquery/treeview/jquery.treeview.js', array('jquery'), WPFB_VERSION);
	wp_register_script('jquery-treeview-edit', WPFB_PLUGIN_URI.'extras/jquery/treeview/jquery.treeview.edit.js', array('jquery-treeview'), WPFB_VERSION);
	wp_register_script('jquery-treeview-async', WPFB_PLUGIN_URI.'extras/jquery/treeview/jquery.treeview.async.js', array('jquery-treeview-edit'), WPFB_VERSION);
	wp_register_style('jquery-treeview', WPFB_PLUGIN_URI.'extras/jquery/treeview/jquery.treeview.css', array(), WPFB_VERSION);
	
	// DataTables
	wp_register_script('jquery-dataTables', WPFB_PLUGIN_URI.'extras/jquery/dataTables/js/jquery.dataTables.min.js', array('jquery'), WPFB_VERSION);
	wp_register_style('jquery-dataTables', WPFB_PLUGIN_URI.'extras/jquery/dataTables/css/jquery.dataTables.css', array(), WPFB_VERSION);
	
	wp_register_script('jquery-dataTables-columnFilter', WPFB_PLUGIN_URI.'extras/jquery/dataTables/js/jquery.dataTables.columnFilter.js', array('jquery-dataTables'), WPFB_VERSION);

	wp_register_script(WPFB, WPFB_PLUGIN_URI.'js/common.js', array('jquery'), WPFB_VERSION); // cond loading (see Footer)
	
	$wpfb_css = get_option('wpfb_css');
	if($wpfb_css) { // static file?
		wp_enqueue_style(WPFB, $wpfb_css, array(), WPFB_VERSION, 'all');
	} else {
		$upload_path = path_is_absolute(WPFB_Core::$settings->upload_path) ? '' : WPFB_Core::$settings->upload_path;
		wp_enqueue_style(WPFB, WPFB_PLUGIN_URI."wp-filebase_css.php?rp=$upload_path", array(), WPFB_VERSION, 'all');
	}

	
	if((is_admin() && !empty($_GET['page']) && strpos($_GET['page'], 'wpfilebase_') !== false) || defined('WPFB_EDITOR_PLUGIN'))
		wpfb_loadclass('Admin');
	
	// live admin
	if(current_user_can('upload_files') && !is_admin()) {
		wp_enqueue_script(WPFB.'-live-admin', WPFB_PLUGIN_URI.'js/live-admin.js', array('jquery'), WPFB_VERSION);
		if(self::GetOpt('admin_bar'))
			add_action( 'admin_bar_menu', array(__CLASS__, 'AdminBar'), 80 );
		if(self::GetOpt('file_context_menu')) {
			wp_enqueue_script('jquery-contextmenu', WPFB_PLUGIN_URI.'extras/jquery/contextmenu/jquery.contextmenu.js', array('jquery'));
			wp_enqueue_style('jquery-contextmenu', WPFB_PLUGIN_URI.'extras/jquery/contextmenu/jquery.contextmenu.css', array(), WPFB_VERSION);
		}
	}

	// for admin
	if (current_user_can('edit_posts') || current_user_can('edit_pages'))
		self::MceAddBtns();
		
	self::DownloadRedirect();
	
	if( (WPFB_Core::$settings->frontend_upload || current_user_can('upload_files')) && (!empty($_GET['wpfb_upload_file']) || !empty($_GET['wpfb_add_cat'])))
		wpfb_call('Admin', empty($_GET['wpfb_upload_file'])?'ProcessWidgetAddCat':'ProcessWidgetUpload');
}

static function GetOpt($name = null) {	return empty($name) ? (array)WPFB_Core::$settings : (isset(WPFB_Core::$settings->$name) ? WPFB_Core::$settings->$name : null); }

static function AdminInit() { 
	wpfb_loadclass('AdminLite');
	if(!empty($_GET['page']) && strpos($_GET['page'], 'wpfilebase_') !== false)
		wpfb_loadclass('Admin');
}
static function AdminMenu() {wpfb_call('AdminLite', 'SetupMenu');}
static function AdminBar() { wpfb_call('AdminBar','AdminBar'); }

static function Sync() { wpfb_call('Sync', 'Sync'); }
static function GenRewriteRules() { wpfb_call('Misc','GenRewriteRules'); }

static function GetPostId($query = null)
{
	global $wp_query, $post;
	
	if(!empty($post->ID)) return $post->ID;
	
	if(empty($query)) $query =& $wp_query;	
	
	return (!empty($query->post->ID) ? $wp_query->post->ID :
			(!empty($query->queried_object_id) ? $query->queried_object_id : 
			(!empty($query->query['post_id']) ? $query->query['post_id'] : 
			(!empty($query->query['page_id'])? $query->query['page_id'] :
			0))));
}

static function ParseQuery(&$query)
{
	// conditional loading of the search hooks
	global $wp_query;
	
	if (!empty($wp_query->query_vars['s']))
			wpfb_loadclass('Search');
			
	
	if(!empty($_GET['wpfb_s']) || !empty($_GET['s'])) {
		WPFB_Core::$file_browser_search = true;		
		add_filter('the_excerpt',	array(__CLASS__, 'SearchExcerptFilter'), 100); // must be lower than 11 (before do_shortcode) and after wpautop (>9)
	}
	
	// check if current post is file browser
	if( ($id=self::GetPostId($query)) == WPFB_Core::$settings->file_browser_post_id)
	{
		wpfb_loadclass('File','Category');
		if(!empty($_GET['wpfb_file'])) self::$file_browser_item = WPFB_File::GetFile($_GET['wpfb_file']);
		elseif(!empty($_GET['wpfb_cat'])) self::$file_browser_item = WPFB_Category::GetCat($_GET['wpfb_cat']);
		else {
			$url = (is_ssl()?'https':'http').'://'.$_SERVER["HTTP_HOST"].stripslashes($_SERVER['REQUEST_URI']);
			if( ($qs=strpos($url,'?')) !== false ) $url = substr($url,0,$qs); // remove query string	
			$path = trim(substr($url, strlen(WPFB_Core::GetPostUrl($id))), '/');
			if(!empty($path)) {
				self::$file_browser_item = WPFB_Item::GetByPath(urldecode($path));
				if(is_null(self::$file_browser_item)) self::$file_browser_item = WPFB_Item::GetByPath($path);
			}
		}
	}	
}



static function DownloadRedirect()
{
	$file = null;
	
	if(!empty($_GET['wpfb_dl'])) {
		wpfb_loadclass('File');
		$file = WPFB_File::GetFile($_GET['wpfb_dl']);
		@ob_end_clean(); // FIX: clean the OB so any output before the actual download is truncated (OB is started in wp-filebase.php)
	} else {
		if(!WPFB_Core::$settings->download_base || is_admin()) return;
		$dl_url_path = parse_url(home_url(WPFB_Core::$settings->download_base.'/'), PHP_URL_PATH);
		$pos = strpos($_SERVER['REQUEST_URI'], $dl_url_path);
		if($pos === 0) {
			$filepath = trim(substr(stripslashes($_SERVER['REQUEST_URI']), strlen($dl_url_path)), '/');
			if( ($qs=strpos($filepath,'?')) !== false ) $filepath = substr($filepath,0,$qs); // remove query string
			if(!empty($filepath)) {
				wpfb_loadclass('File','Category');
				$file = is_null($file=WPFB_File::GetByPath($filepath)) ? WPFB_File::GetByPath(urldecode($filepath)) : $file;
			}
		}
	}
	
	if(!empty($file) && is_object($file) && !empty($file->is_file)) {
		$file->Download();		
		exit;
	} /* else { // don't set coockies anymore
		// no download, a normal request: set site visited coockie to disable referer check
		if(empty($_COOKIE[WPFB_OPT_NAME])) {
			@setcookie(WPFB_OPT_NAME, '1');
			$_COOKIE[WPFB_OPT_NAME] = '1';
		}
	} */
}

static function Ext2TypeFilter($arr) {
	$arr['interactive'][] = 'exe';
	$arr['interactive'][] = 'msi';
	return $arr;
}

static function SearchExcerptFilter($content)
{
	global $id;
	
	// replace file browser post content with search results
	if(WPFB_Core::$file_browser_search && $id == WPFB_Core::$settings->file_browser_post_id)
	{
		wpfb_loadclass('Search','File','Category');
		$content = '';
		WPFB_Search::FileSearchContent($content);
	}
	
	return $content;
}

static function ContentFilter($content)
{
	global $id, $wpfb_fb, $post;
	
	if(!WPFB_Core::$settings->parse_tags_rss && is_feed())
		return $content;
	
	if(is_object($post) && !post_password_required())
	{
		// TODO: file resulst are generated twice, 2nd time in the_excerpt filter (SearchExcerptFilter)
		// some themes do not use excerpts in search resulsts!!
		// replace file browser post content with search results
		if(WPFB_Core::$file_browser_search && $id == WPFB_Core::$settings->file_browser_post_id)
		{
			wpfb_loadclass('Search','File','Category');
			$content = '';
			WPFB_Search::FileSearchContent($content);
		} else { // do not hanlde attachments when searching
			$single = is_single() || is_page();
			
			if($single && $post->ID == WPFB_Core::$settings->file_browser_post_id) {
				$wpfb_fb = true;
				wpfb_loadclass('Output', 'File', 'Category');
				WPFB_Output::FileBrowser($content, 0, empty($_GET['wpfb_cat']) ? 0 : intval($_GET['wpfb_cat']));
			}
		
			if(self::GetOpt('auto_attach_files') && ($single || self::GetOpt('attach_loop'))) {
				wpfb_loadclass('Output');			
				if(WPFB_Core::$settings->attach_pos == 0)
					$content = WPFB_Output::PostAttachments(true) . $content;
				else
					$content .= WPFB_Output::PostAttachments(true);
			}
		}
	}

    return $content;
}


static function ShortCode($atts, $content=null, $tag=null) {
	wpfb_loadclass('Output');
	return WPFB_Output::ProcessShortCode(shortcode_atts(array(
		'tag' => 'list', // file, fileurl, attachments
		'id' => -1,
		'path' => null,
		'tpl' => null,
		'sort' => null,
		'showcats' => false,
		'sortcats' => null,
		'num' => 0,
		'pagenav' => 1,
		'linktext' => null,
			
	), $atts), $content, $tag);
}


static function Footer() {
	global $wpfb_fb; // filebrowser loaded?
	
	// TODO: use enque and no cond loading ?
	if(!empty(self::$load_js)) {
		self::PrintJS();
	}
	
	if(!empty($wpfb_fb) && !WPFB_Core::$settings->disable_footer_credits) {
		echo '<div id="wpfb-credits" name="wpfb-credits" style="'.esc_attr(WPFB_Core::$settings->footer_credits_style).'">';
		printf(__('<a href="%s" title="Wordpress Download Manager Plugin" style="color:inherit;font-size:inherit;">Downloads served by WP-Filebase</a>',WPFB),'http://wpfilebase.com/');
		echo '</div>';
	}
}



static function MceAddBtns() {
	add_filter('mce_external_plugins', array('WPFB_Core', 'McePlugins'));
	add_filter('mce_buttons', array('WPFB_Core', 'MceButtons'));
}
static function McePlugins($plugins) { wpfb_loadclass('AdminLite'); return WPFB_AdminLite::McePlugins($plugins); }
static function MceButtons($buttons) { wpfb_loadclass('AdminLite'); return WPFB_AdminLite::MceButtons($buttons); }

static function UpdateOption($name, $value = null) {
	WPFB_Core::$settings->$name = $value;
	update_option(WPFB_OPT_NAME, (array)WPFB_Core::$settings);
}

static function UploadDir() {
	static $upload_path = '';
	return empty($upload_path) ? ($upload_path = path_join(ABSPATH, empty(WPFB_Core::$settings->upload_path) ? 'wp-content/uploads/filebase' : WPFB_Core::$settings->upload_path)) : $upload_path;
}

static function GetPostUrl($id) { return isset(self::$post_url_cache[$id]) ? self::$post_url_cache[$id] : (self::$post_url_cache[$id] = get_permalink($id)); }



static function GetSortSql($sort=null, $attach_order=false, $for_cat=false)
{
	global $wpdb;
	wpfb_loadclass('Output');
	list($sort, $sortdir) = WPFB_Output::ParseSorting($sort, $for_cat);	
	$sort = esc_sql($sort);
	$of = $for_cat ? 'cat_order' : 'file_attach_order';	
	return $attach_order ? "`$of` ASC, `$sort` $sortdir" : "`$sort` $sortdir";
}

static function EnqueueScripts()
{
	global $wp_query;
	
	if( !WPFB_Core::$settings->late_script_loading
			&& ((!empty($wp_query->queried_object_id) && $wp_query->queried_object_id == WPFB_Core::$settings->file_browser_post_id) ||
			!empty($wp_query->post) && $wp_query->post->ID == WPFB_Core::$settings->file_browser_post_id)) {
		wp_enqueue_script('jquery-treeview-async');
		wp_enqueue_style('jquery-treeview');
	}
}

static function PrintJS() {
	static $printed = false;
	if($printed) return;
	$printed = true;
	
	wp_print_scripts(WPFB);
	
	$context_menu = current_user_can('upload_files') && self::GetOpt('file_context_menu') && !defined('WPFB_EDITOR_PLUGIN') && !is_admin();
	
	$conf = array(
		'ql'=>!is_admin(), // querylinks with jQuery
		'hl'=> (int)self::GetOpt('hide_links'), // hide links
		'pl'=>(self::GetOpt('disable_permalinks') ? 0 : (int)!!get_option('permalink_structure')), // permlinks
		'hu'=> trailingslashit(home_url()),// home url
		'db'=> self::GetOpt('download_base'),// urlbase
		'fb'=> self::GetPostUrl(self::GetOpt('file_browser_post_id')),
		'cm'=>(int)$context_menu,
		'ajurl'=>WPFB_PLUGIN_URI.'wpfb-ajax.php'
	);
	
	if($context_menu) {
		$conf['fileEditUrl'] = admin_url("admin.php?page=wpfilebase_files&action=editfile&file_id=");
		
		//wp_print_scripts('jquery-contextmenu');
		//wp_print_styles	('jquery-contextmenu');
	}
		
	echo "<script type=\"text/javascript\">\n//<![CDATA[\n",'wpfbConf=',json_encode($conf),';';
	
	if($context_menu) {
		echo
"wpfbContextMenu=[
	{'",__('Edit'),"':{onclick:wpfb_menuEdit,icon:'".WPFB_PLUGIN_URI."extras/jquery/contextmenu/page_white_edit.png'}, },
	jQuery.contextMenu.separator,
	{'",__('Delete'),"':{onclick:wpfb_menuDel,icon:'".WPFB_PLUGIN_URI."extras/jquery/contextmenu/delete_icon.gif'}}
];\n";
		
	}
	
	echo "function wpfb_ondl(file_id,file_url,file_path){ ",WPFB_Core::$settings->dlclick_js," }";	
	echo "\n//]]>\n</script>\n";
}

// OPTIMZE: not so deep function calls

// gets custom template list or single if tag specified
static function GetFileTpls($tag=null) {
	if($tag == 'default') return self::GetOpt('template_file');
	$tpls = get_option(WPFB_OPT_NAME.'_tpls_file');
	return empty($tag) ? $tpls : $tpls[$tag];
}

static function GetCatTpls($tag=null) {
	if($tag == 'default') return self::GetOpt('template_cat');
	$tpls = get_option(WPFB_OPT_NAME.'_tpls_cat');
	return empty($tag) ? $tpls : $tpls[$tag];
}

static function GetTpls($type, $tag=null) { return ($type == 'cat') ? self::GetCatTpls($tag) : self::GetFileTpls($tag);}

static function SetFileTpls($tpls) { return is_array($tpls) ? update_option(WPFB_OPT_NAME.'_tpls_file', $tpls) : false; }
static function SetCatTpls($tpls) { return is_array($tpls) ? update_option(WPFB_OPT_NAME.'_tpls_cat', $tpls) : false; }

static function GetParsedTpl($type, $tag) {
	if(empty($tag)) return null;
	if($tag == 'default') return self::GetOpt("template_{$type}_parsed");
	$on = WPFB_OPT_NAME.'_ptpls_'.$type;
	$ptpls = get_option($on);
	if(empty($ptpls)) {
		$ptpls = wpfb_call('TplLib','Parse',self::GetTpls($type));
		update_option($on, $ptpls);
	}
	return empty($ptpls[$tag]) ? null : $ptpls[$tag];
}



static function Cron() {
	if(self::$settings->cron_sync ) {
		wpfb_call('Sync', 'Sync');
		update_option(WPFB_OPT_NAME.'_cron_sync_time', empty($_SERVER["REQUEST_TIME"]) ? time() : $_SERVER["REQUEST_TIME"]);
	}
}

static function GetMaxUlSize() {	return wpfb_call('Misc','ParseIniFileSize', ini_get('upload_max_filesize')); }

public static function GetCustomFields($full_field_names=false, &$default_values=null) {
	$custom_fields = isset(WPFB_Core::$settings->custom_fields)?explode("\n",WPFB_Core::$settings->custom_fields):array();
	$arr = array();
	$default_values = array();
	if(empty($custom_fields[0])) return array();
	foreach($custom_fields as $cf) {
		$cfa = explode("|", $cf);
		$arr[$k = $full_field_names?('file_custom_'.trim($cfa[1])):trim($cfa[1])] = $cfa[0];
		$default_values[$k] = empty($cfa[2]) ? '' : $cfa[2];
	}
	return $arr;
}


static function GetOldCustomCssPath($path=null) {
	$path = empty($path) ? self::UploadDir() : (ABSPATH .'/'.trim(str_replace('\\','/',str_replace('..','', $path)),'/'));
	return @is_dir($path) ? "$path/_wp-filebase.css" : null;
}

static function CreateTplFunc($parsed_tpl) {	return create_function('$f', "return ($parsed_tpl);"); }

}

 
