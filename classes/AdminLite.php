<?php
class WPFB_AdminLite {
static function InitClass()
{
	global $parent_file;
	
	wp_enqueue_style(WPFB.'-admin', WPFB_PLUGIN_URI.'wp-filebase-admin.css', array(), WPFB_VERSION, 'all' );
	
	wp_register_script('jquery-deserialize', WPFB_PLUGIN_URI.'extras/jquery/jquery.deserialize.js', array('jquery'), WPFB_VERSION);
	
	if (isset($_GET['page']))
	{
		$page = $_GET['page'];
		if($page == 'wpfilebase_files') {
			wp_enqueue_script( 'postbox' );
			wp_enqueue_style('dashboard');
		} elseif($page == 'wpfilebase' && isset($_GET['action']) && $_GET['action'] == 'sync') {
			do_action('wpfilebase_sync');
			wp_die("Filebase synced.");
		}
	}
	
	add_action('wp_dashboard_setup', array(__CLASS__, 'AdminDashboardSetup'));	
	
	//wp_register_widget_control(WPFB_PLUGIN_NAME, "[DEPRECATED]".WPFB_PLUGIN_NAME .' '. __('File list'), array(__CLASS__, 'WidgetFileListControl'), array('description' => __('DEPRECATED', WPFB)));
	
	add_action('admin_print_scripts', array('WPFB_AdminLite', 'PrintCKEditorPlugin'));

	
	self::CheckChangedVer();
	
	
	if(basename($_SERVER['PHP_SELF']) === "plugins.php") {
		if(isset($_GET['wpfb-uninstall']) && current_user_can('edit_files'))
				update_option('wpfb_uninstall', !empty($_GET['wpfb-uninstall']) && $_GET['wpfb-uninstall'] != "0");
		
		if(get_option('wpfb_uninstall')) {
			function wpfb_uninstall_warning() {
				echo "
				<div id='wpfb-warning' class='updated fade'><p><strong>".__('WP-Filebase will be uninstalled completely when deactivating the Plugin! All settings and File/Category Info will be deleted. Actual files in the upload directory will not be removed.').' <a href="'.add_query_arg('wpfb-uninstall', '0').'">'.__('Cancel')."</a></strong></p></div>
				";
			}
			add_action('admin_notices', 'wpfb_uninstall_warning');
		}
	}
	
}

static function SetupMenu()
{
	global $wp_version;
	$pm_tag = WPFB_OPT_NAME.'_manage';
	$icon = (floatval($wp_version) >= 3.8) ? 'images/admin_menu_icon2.png' : 'images/admin_menu_icon.png';
	
	add_menu_page(WPFB_PLUGIN_NAME, WPFB_PLUGIN_NAME, 'manage_categories', $pm_tag, array(__CLASS__, 'DisplayManagePage'), WPFB_PLUGIN_URI.$icon /*, $position*/ );
	
	$menu_entries = array(
		array('tit'=>'Files',						'tag'=>'files',	'fnc'=>'DisplayFilesPage',	'desc'=>'View uploaded files and edit them',
				'cap'=>'upload_files',
		),
		array('tit'=>__('Categories'/*def*/),		'tag'=>'cats',	'fnc'=>'DisplayCatsPage',	'desc'=>'Manage existing categories and add new ones.',
				'cap'=>'manage_categories',
		),
		
		//array('tit'=>'Sync Filebase', 'hide'=>true, 'tag'=>'sync',	'fnc'=>'DisplaySyncPage',	'desc'=>'Synchronises the database with the file system. Use this to add FTP-uploaded files.',	'cap'=>'upload_files'),
		
		array('tit'=>'Edit Stylesheet',				'tag'=>'css',	'fnc'=>'DisplayStylePage',	'desc'=>'Edit the CSS for the file template',
				'cap'=>'edit_themes',
		),
		
		array('tit'=>'Manage Templates',			'tag'=>'tpls',	'fnc'=>'DisplayTplsPage',	'desc'=>'Edit custom file list templates',
				'cap'=>'edit_themes',
		),
		
		array('tit'=>__('Settings'),				'tag'=>'sets',	'fnc'=>'DisplaySettingsPage','desc'=>'Change Settings',
														'cap'=>'manage_options'),
		array('tit'=>'Donate &amp; Feature Request','tag'=>'sup',	'fnc'=>'DisplaySupportPage','desc'=>'If you like this plugin and want to support my work, please donate. You can also post your ideas making the plugin better.', 'cap'=>'manage_options'),
	);
	
	foreach($menu_entries as $me)
	{
		$callback = array(__CLASS__, $me['fnc']);
		add_submenu_page($pm_tag, WPFB_PLUGIN_NAME.' - '.__($me['tit'], WPFB), empty($me['hide'])?__($me['tit'], WPFB):null, empty($me['cap'])?'read':$me['cap'], WPFB_OPT_NAME.'_'.$me['tag'], $callback);
	}
}

static function DisplayManagePage(){wpfb_call('AdminGuiManage', 'Display');}


static function DisplayFilesPage(){wpfb_call('AdminGuiFiles', 'Display');}
static function DisplayCatsPage(){wpfb_call('AdminGuiCats', 'Display');}
//static function DisplaySyncPage(){wpfb_call('AdminGuiSync', 'Display');}
static function DisplayStylePage(){wpfb_call('AdminGuiCss', 'Display');}
static function DisplayTplsPage(){wpfb_call('AdminGuiTpls', 'Display');}
static function DisplaySettingsPage(){wpfb_call('AdminGuiSettings', 'Display');}
static function DisplaySupportPage(){wpfb_call('AdminGuiSupport', 'Display');}

static function McePlugins($plugins) {
	$plugins['wpfilebase'] = WPFB_PLUGIN_URI . 'tinymce/editor_plugin.js';
	return $plugins;
}

static function MceButtons($buttons) {
	array_push($buttons, 'separator', 'wpfbInsertTag');
	return $buttons;
}


private static function CheckChangedVer()
{
	$ver = wpfb_call('Core', 'GetOpt', 'version');
	if($ver != WPFB_VERSION) {
		wpfb_loadclass('Setup');
		WPFB_Setup::OnActivateOrVerChange($ver);
	}
}

static function JsRedirect($url) {
	echo '<script type="text/javascript"> window.location = "',esc_js($url),'"; </script><h1><a href="',esc_attr($url),'">',esc_html($url),'</a></h1>'; 
}

static function PrintCKEditorPlugin() {
	if(has_filter('ckeditor_external_plugins') === false) return;	
	?>
<script type="text/javascript">
//<![CDATA[
	/* CKEditor Plugin */
	if(typeof(ckeditorSettings) == 'object') {
		ckeditorSettings.externalPlugins.wpfilebase = ajaxurl+'/../../wp-content/plugins/wp-filebase/extras/ckeditor/';
		ckeditorSettings.additionalButtons.push(["WPFilebase"]);
	}
//]]>
</script>
	<?php
}

static function AdminDashboardSetup() {	
	if(wpfb_call('Admin','CurUserCanUpload'))
		wp_add_dashboard_widget('wpfb-add-file-widget', WPFB_PLUGIN_NAME.': '.__('Add File', WPFB), array('WPFB_Admin', 'AddFileWidget'));
}

}