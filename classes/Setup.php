<?php

class WPFB_Setup {
const MANY_FILES = 50;
const MANY_CATEGORIES = 200;

static function AddOptions()
{
	$default_opts = WPFB_Admin::SettingsSchema();		
	$existing_opts = get_option(WPFB_OPT_NAME);
	$new_opts = array();
	
	foreach($default_opts as $opt_name => $opt_data)
	{
		$new_opts[$opt_name] = $opt_data['default'];
	}
	
	$new_opts['widget'] = array(); // placeholder to keep old widget settings!
	
	$new_opts['version'] = WPFB_VERSION;
	$new_opts['tag_ver'] = WPFB_TAG_VER;
	
	
	if(empty($existing_opts)) //if no opts at all
		add_option(WPFB_OPT_NAME, $new_opts);
	else {		
		foreach($new_opts as $opt_name => $opt_data)
		{
			// check if this option already exists, and if changed, take the existing value
			if($opt_name != 'version' && $opt_name != 'tag_ver' && isset($existing_opts[$opt_name]) && $existing_opts[$opt_name] != $opt_data)
				$new_opts[$opt_name] = $existing_opts[$opt_name];
		}
		
		// check for old tags
		if(empty($existing_opts['tag_ver']) || intval($existing_opts['tag_ver']) < WPFB_TAG_VER){
			$new_opts['tag_conv_req'] = true;
		}

		update_option(WPFB_OPT_NAME, $new_opts);
	}
	
	WPFB_Core::$settings = (object)get_option(WPFB_OPT_NAME);
	
	add_option(WPFB_OPT_NAME.'_ftags', array(), null, 'no'/*autoload*/); 
	
	
	
	// for static css caching
	add_option('wpfb_css', WPFB_PLUGIN_URI . 'wp-filebase.css');
 
}
static function AddTpls($old_ver=null) {	
	$def_tpls_file = array(
		'filebrowser' => '%file_small_icon% <a href="%file_url%" title="Download %file_display_name%">%file_display_name%</a> (%file_size%)',
		'download-button' => '<div style="text-align:center; width:250px; margin: auto; font-size:smaller;"><a href="%file_url%" class="wpfb-dlbtn"><div></div></a>
%file_display_name% (%file_size%, %file_hits% downloads)
</div>',
		'image_320' => '[caption id="file_%file_id%" align="alignnone" width="320" caption="<!-- IF %file_description% -->%file_description%<!-- ELSE -->%file_display_name%<!-- ENDIF -->"]<img class="size-full" title="%file_display_name%" src="%file_url%" alt="%file_display_name%" width="320" />[/caption]'."\n\n",
		'thumbnail' => '<div class="wpfilebase-fileicon"><a href="%file_url%" title="Download %file_display_name%"><img align="middle" src="%file_icon_url%" /></a></div>'."\n",
		'simple'	=> '<p><img src="%file_icon_url%" style="height:20px;vertical-align:middle;" /> <a href="%file_url%" title="Download %file_display_name%">%file_display_name%</a> (%file_size%)</p>',
		'3-col-row' => '<tr><td><a href="%file_url%">%file_display_name%</a></td><td>%file_size%</td><td>%file_hits%</td></tr>',
		'mp3' => '<div class="wpfilebase-attachment">
 <div class="wpfilebase-fileicon"><a href="%file_url%" title="Download %file_display_name%"><img align="middle" src="%file_icon_url%" alt="%file_display_name%" height="80"/></a></div>
 <div class="wpfilebase-rightcol">
  <div class="wpfilebase-filetitle">
   <a href="%file_url%" title="Download %file_display_name%">%file_info/tags/id3v2/title%</a><br />
%file_info/tags/id3v2/artist%<br />
%file_info/tags/id3v2/album%<br />
   <!-- IF %file_post_id% AND %post_id% != %file_post_id% --><a href="%file_post_url%" class="wpfilebase-postlink">%\'View post\'%</a><!-- ENDIF -->
  </div>
 </div>
 <div class="wpfilebase-fileinfo">
  %file_info/playtime_string%<br />
  %file_info/bitrate%<br />
  %file_size%<br />
  %file_hits% %\'Downloads\'%<br />
 </div>
 <div style="clear: both;"></div>
</div>',
	
	'flv-player' => "<!-- the player only works when permalinks are enabled!!! -->
 <object width='%file_info/video/resolution_x%' height='%file_info/video/resolution_y%' id='flvPlayer%uid%'>
  <param name='allowFullScreen' value='true'>
   <param name='allowScriptAccess' value='always'> 
  <param name='movie' value='%wpfb_url%extras/flvplayer/OSplayer.swf?movie=%file_url_encoded%&btncolor=0x333333&accentcolor=0x31b8e9&txtcolor=0xdddddd&volume=30&autoload=on&autoplay=off&vTitle=%file_display_name%&showTitle=yes'>
  <embed src='%wpfb_url%extras/flvplayer/OSplayer.swf?movie=%file_url_encoded%&btncolor=0x333333&accentcolor=0x31b8e9&txtcolor=0xdddddd&volume=30&autoload=on&autoplay=off&vTitle=%file_display_name%&showTitle=yes' width='%file_info/video/resolution_x%' height='%file_info/video/resolution_y%' allowFullScreen='true' type='application/x-shockwave-flash' allowScriptAccess='always'>
 </object>",
	
	'data-table' => '<tr><td><a href="%file_url%">%file_display_name%</a></td><td>%file_size%</td><td>%file_hits%</td></tr>',
	);
	
	$def_tpls_cat = array(
		'filebrowser' => '%cat_small_icon% <a href="%cat_url%" onclick="return false;">%cat_name%</a>',
		'3-col-row' => '<tr><td colspan="3" style="text-align:center;font-size:120%;">%cat_name%</td></tr>',
		'data-table' => '<!-- EMPTY: categories should not be listed in DataTables -->',
	);
	
	add_option(WPFB_OPT_NAME.'_tpls_file', $def_tpls_file, null, 'no'/*autoload*/); 
	add_option(WPFB_OPT_NAME.'_tpls_cat', $def_tpls_cat, null, 'no'/*autoload*/);	
	add_option(WPFB_OPT_NAME.'_ptpls_file', array(), null, 'no'/*autoload*/); 
	add_option(WPFB_OPT_NAME.'_ptpls_cat', array(), null, 'no'/*autoload*/); 
	
	$def_tpls_list = array(
		'default' => array(
			'header' => '',
			'footer' => '',
			'file_tpl_tag' => 'default',
			'cat_tpl_tag' => 'default'
		),
		'table' => array(
			'header' => '%search_form%
<table>
<thead>
	<tr><th scope="col"><a href="%sortlink:file_name%">Name</a></th><th scope="col"><a href="%sortlink:file_size%">Size</a></th><th scope="col"><a href="%sortlink:file_hits%">Hits</a></th></tr>
</thead>
<tfoot>
	<tr><th scope="col"><a href="%sortlink:file_name%">Name</a></th><th scope="col"><a href="%sortlink:file_size%">Size</a></th><th scope="col"><a href="%sortlink:file_hits%">Hits</a></th></tr>
</tfoot>
<tbody>',
			'footer' => '</tbody>
</table>
<div class="tablenav-pages">%page_nav%</div>',
			'file_tpl_tag' => '3-col-row',
			'cat_tpl_tag' => '3-col-row'
		),		
		'mp3-list' => array(
			'header' => '',
			'footer' => '',
			'file_tpl_tag' => 'mp3',
			'cat_tpl_tag' => 'default'
		),
		
		'data-table' => array(
			'header' =>
'%print_script:jquery-dataTables%
%print_style:jquery-dataTables%
<table id="wpfb-data-table-%uid%">
<thead>
	<tr><th scope="col">Name</th><th scope="col">Size</th><th scope="col">Hits</th></tr>
</thead>
<tbody>',
			'footer' =>
'</tbody>
</table>
<script type="text/javascript" charset="utf-8">
	jQuery(document).ready(function() {
		jQuery(\'#wpfb-data-table-%uid%\').dataTable();
	} );
</script>',
			'file_tpl_tag' => 'data-table',
			'cat_tpl_tag' => 'data-table'
		
		)
	);		
	add_option(WPFB_OPT_NAME.'_list_tpls', $def_tpls_list, null, 'no'/*autoload*/); 
		
	// delete old (<0.2.0) tpl options and copy to new
	$old_tpls = get_option(WPFB_OPT_NAME . '_tpls');
	delete_option(WPFB_OPT_NAME . '_tpls');
	delete_option(WPFB_OPT_NAME . '_tpls_parsed');
	if(!empty($old_tpls)) {
		$file_tpls = array_merge(WPFB_Core::GetFileTpls(), $old_tpls);
		WPFB_Core::SetFileTpls($file_tpls);
	}
	
	// add protected tpls
	$tpls_file = get_option(WPFB_OPT_NAME.'_tpls_file');
	$tpls_cat = get_option(WPFB_OPT_NAME.'_tpls_cat');
	$tpls_list = get_option(WPFB_OPT_NAME.'_list_tpls');
	
	wpfb_loadclass('AdminGuiTpls');
	$default_templates = WPFB_AdminGuiTpls::$protected_tags;
	
	// add new data table template
	if(!empty($old_ver)) {
		if(version_compare($old_ver, '0.2.9.22') < 0) {
			$default_templates[] = 'data-table';
			$default_templates[] = 'download-button';
		}
	}
	
	foreach($default_templates as $pt) {
		if(empty($tpls_file[$pt]) && !empty($def_tpls_file[$pt])) $tpls_file[$pt] = $def_tpls_file[$pt];
		if(empty($tpls_cat[$pt]) && !empty($def_tpls_cat[$pt])) $tpls_cat[$pt] = $def_tpls_cat[$pt];
		if(empty($tpls_list[$pt]) && !empty($def_tpls_list[$pt])) $tpls_list[$pt] = $def_tpls_list[$pt];
	}
	
	update_option(WPFB_OPT_NAME.'_tpls_file', $tpls_file);
	update_option(WPFB_OPT_NAME.'_tpls_cat', $tpls_cat);
	update_option(WPFB_OPT_NAME.'_list_tpls', $tpls_list);
	
	WPFB_Admin::ParseTpls();
}

static function RemoveOptions()
{
	delete_option(WPFB_OPT_NAME);
	
	delete_option('wpfb_css');
	
	// delete old options too
	$options = WPFB_Admin::SettingsSchema();
	foreach($options as $opt_name => $opt_data)
		delete_option(WPFB_OPT_NAME . '_' . $opt_name);
	WPFB_Core::$settings = new stdClass();
}

static function RemoveTpls() {
	delete_option(WPFB_OPT_NAME.'_tpls_file'); 
	delete_option(WPFB_OPT_NAME.'_tpls_cat');	
	delete_option(WPFB_OPT_NAME.'_ptpls_file'); 
	delete_option(WPFB_OPT_NAME.'_ptpls_cat'); 
	delete_option(WPFB_OPT_NAME.'_list_tpls');
}

static function ResetOptions()
{
	$traffic = WPFB_Core::$settings->traffic_stats; 	// keep stats
	self::RemoveOptions();
	self::AddOptions();
	WPFB_Core::UpdateOption('traffic_stats', $traffic);
	WPFB_Admin::ParseTpls();
}

static function ResetTpls()
{
	self::RemoveTpls();
	self::AddTpls();
}


static function SetupDBTables($old_ver=null)
{
	global $wpdb;

	$queries = array();
	$tbl_cats = $wpdb->prefix . 'wpfb_cats';
	$tbl_files = $wpdb->prefix . 'wpfb_files';
	$tbl_files_id3 = $wpdb->prefix . 'wpfb_files_id3';
	
	$queries[] = "CREATE TABLE IF NOT EXISTS `$tbl_cats` (
  `cat_id` int(8) unsigned NOT NULL auto_increment,
  `cat_name` varchar(255) NOT NULL default '',
  `cat_description` text,
  `cat_folder` varchar(300) NOT NULL,
  `cat_path` varchar(2000) NOT NULL,
  `cat_parent` int(8) unsigned NOT NULL default '0',
  `cat_num_files` int(8) unsigned NOT NULL default '0',
  `cat_num_files_total` int(8) unsigned NOT NULL default '0',
  `cat_user_roles` text NOT NULL default '',
  `cat_owner` bigint(20) unsigned default NULL,
  `cat_icon` varchar(255) default NULL,
  `cat_exclude_browser` enum('0','1') NOT NULL default '0',
  `cat_order` int(8) NOT NULL default '0',
  PRIMARY KEY  (`cat_id`),
  FULLTEXT KEY `USER_ROLES` (`cat_user_roles`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
				
	
	$queries[] = "CREATE TABLE IF NOT EXISTS `$tbl_files` (
  `file_id` bigint(20) unsigned NOT NULL auto_increment,
  `file_name` varchar(300) NOT NULL default '',
  `file_path` varchar(2000) NOT NULL default '',
  `file_size` bigint(20) unsigned NOT NULL default '0',
  `file_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `file_mtime` bigint(20) unsigned NOT NULL default '0',
  `file_hash` char(32) NOT NULL,
  `file_remote_uri` varchar(255) NOT NULL default '',
  `file_thumbnail` varchar(255) default NULL,
  `file_display_name` varchar(255) NOT NULL default '',
  `file_description` text,
  `file_tags` varchar(255) NOT NULL default '',
  `file_requirement` varchar(255) default NULL,
  `file_version` varchar(64) default NULL,
  `file_author` varchar(255) default NULL,
  `file_language` varchar(255) default NULL,
  `file_platform` varchar(255) default NULL,
  `file_license` varchar(255) NOT NULL default '',
  `file_user_roles` text NOT NULL default '',
  `file_offline` enum('0','1') NOT NULL default '0',
  `file_direct_linking` enum('0','1','2') NOT NULL default '0',
  `file_force_download` enum('0','1') NOT NULL default '0',
  `file_category` int(8) unsigned NOT NULL default '0',
  `file_category_name` varchar(127) NOT NULL default '',
  `file_update_of` bigint(20) unsigned default NULL,
  `file_post_id` bigint(20) unsigned default NULL,
  `file_attach_order` int(8) NOT NULL default '0',
  `file_wpattach_id` bigint(20) NOT NULL default '0',
  `file_added_by` bigint(20) unsigned default NULL,
  `file_hits` bigint(20) unsigned NOT NULL default '0',
  `file_ratings` bigint(20) unsigned NOT NULL default '0',
  `file_rating_sum` bigint(20) unsigned NOT NULL default '0',
  `file_last_dl_ip` varchar(100) NOT NULL default '',
  `file_last_dl_time` datetime NOT NULL default '0000-00-00 00:00:00',
  ". /*`file_meta` TEXT NULL DEFAULT NULL,*/ "
  PRIMARY KEY  (`file_id`),
  FULLTEXT KEY `DESCRIPTION` (`file_description`),
  FULLTEXT KEY `USER_ROLES` (`file_user_roles`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";	
	
	$queries[] = "CREATE TABLE IF NOT EXISTS `$tbl_files_id3` (
  `file_id` bigint(20) unsigned NOT NULL auto_increment,
  `analyzetime` INT(11) NOT NULL DEFAULT '0',
  `value` LONGTEXT NOT NULL,
  `keywords` TEXT NOT NULL,
  PRIMARY KEY  (`file_id`),
  FULLTEXT KEY `KEYWORDS` (`keywords`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8";


	

	// errors of queries starting with @ are supressed
	
	$queries[] = "@ALTER TABLE `$tbl_cats` DROP INDEX `FULLTEXT`";
	$queries[] = "@ALTER TABLE `$tbl_cats` DROP INDEX `CAT_NAME`";
	$queries[] = "@ALTER TABLE `$tbl_cats` DROP INDEX `CAT_FOLDER`";
	
	$queries[] = "@ALTER TABLE `$tbl_cats` ADD UNIQUE `UNIQUE_FOLDER` ( `cat_folder` , `cat_parent` ) ";	
	$queries[] = "@ALTER TABLE `$tbl_files` ADD UNIQUE `UNIQUE_FILE` ( `file_name` , `file_category` )";
	
	// <= v0.1.2.2
	$queries[] = "@ALTER TABLE `$tbl_cats` ADD `cat_icon` VARCHAR(255) NULL DEFAULT NULL";
	
	// since v0.2.0.0
	$queries[] = "@ALTER TABLE `$tbl_files` ADD `file_remote_uri` VARCHAR( 255 ) NULL DEFAULT NULL AFTER `file_hash`"; 
	$queries[] = "@ALTER TABLE `$tbl_files` ADD `file_force_download` enum('0','1') NOT NULL default '0'";
	$queries[] = "@ALTER TABLE `$tbl_files` ADD `file_path` varchar(255) NOT NULL default '' AFTER `file_name`";
	$queries[] = "@ALTER TABLE `$tbl_cats` ADD `cat_exclude_browser` enum('0','1') NOT NULL default '0'";
	$queries[] = "@ALTER TABLE `$tbl_cats` ADD `cat_path` varchar(255) NOT NULL default '' AFTER `cat_folder`";
	
	// removed since 0.2.9.25
	//$queries[] = "@ALTER TABLE `$tbl_cats` ADD UNIQUE `UNIQUE_PATH` ( `cat_path` ) ";	
	//$queries[] = "@ALTER TABLE `$tbl_files` ADD UNIQUE `UNIQUE_PATH` ( `file_path` )";
	
	// the new cat file counters
	$queries[] = "@ALTER TABLE `$tbl_cats` ADD `cat_num_files` int(8) unsigned NOT NULL default '0' AFTER `cat_parent`";
	$queries[] = "@ALTER TABLE `$tbl_cats` CHANGE `cat_files` `cat_num_files_total` INT( 8 ) UNSIGNED NOT NULL DEFAULT '0'";
	$queries[] = "@ALTER TABLE `$tbl_cats` ADD `cat_num_files_total` int(8) unsigned NOT NULL default '0' AFTER `cat_num_files`";
	
	// since 0.2.8
	$queries[] = "@ALTER TABLE `$tbl_files` ADD `file_category_name` varchar(127) NOT NULL default '' AFTER `file_category`";
	
	
	// since 0.2.9.1
	$queries[] = "@ALTER TABLE `$tbl_files` ADD `file_user_roles` varchar(2000) NOT NULL default '' AFTER `file_license`";
	$queries[] = "@ALTER TABLE `$tbl_cats` ADD `cat_user_roles` varchar(2000) NOT NULL default '' AFTER `cat_num_files_total`";
	
	$queries[] = "@ALTER TABLE `$tbl_files` ADD `file_attach_order` int(8) NOT NULL default '0'  AFTER `file_post_id`";
	
	// since 0.2.9.3
	$queries[] = "@ALTER TABLE `$tbl_files` ADD `file_wpattach_id` bigint(20) NOT NULL default '0'  AFTER `file_attach_order`";
	
	// since 0.2.9.9
	$queries[] = "@ALTER TABLE `$tbl_files` ADD `file_tags` varchar(255) NOT NULL default ''  AFTER `file_description`";
	
	// 0.2.9.10
	$queries[] = "@ALTER TABLE `$tbl_files_id3` CHANGE `value` `value` LONGTEXT";
	
	// 0.2.9.12
	$queries[] = "@ALTER TABLE `$tbl_cats` ADD `cat_order` int(8) NOT NULL default '0'  AFTER `cat_exclude_browser`";

	// since 0.2.9.25
	$queries[] = "@ALTER TABLE  `$tbl_cats` DROP INDEX  `UNIQUE_PATH`";
	$queries[] = "@ALTER TABLE  `$tbl_files` DROP INDEX  `UNIQUE_PATH`";
	$queries[] = "ALTER TABLE  `$tbl_cats` CHANGE  `cat_path`  `cat_path` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  ''";
	$queries[] = "ALTER TABLE  `$tbl_files` CHANGE  `file_path`  `file_path` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  ''";
	$queries[] = "ALTER TABLE  `$tbl_cats` CHANGE  `cat_folder`  `cat_folder` VARCHAR( 300 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  ''";
	$queries[] = "ALTER TABLE  `$tbl_files` CHANGE  `file_name`  `file_name` VARCHAR( 300 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  ''";

	
	$queries[] = "@ALTER TABLE `$tbl_cats` ADD `cat_owner` bigint(20) unsigned NOT NULL default 0 AFTER `cat_user_roles`";

	// add fulltext indices
	if(!empty($old_ver) && version_compare($old_ver, '0.2.9.24') < 0) { 	// TODO: search fields fulltext index!
		$queries[] = "@ALTER TABLE `$tbl_files` ADD FULLTEXT `USER_ROLES` (`file_user_roles`)";
		$queries[] = "@ALTER TABLE `$tbl_cats` ADD FULLTEXT `USER_ROLES` (`cat_user_roles`)";		
		$queries[] = "@ALTER TABLE `$tbl_files_id3` ADD FULLTEXT `KEYWORDS` (`keywords`)";
	}
	
	// 2 is for file pages
	if(!empty($old_ver) && version_compare($old_ver, '0.2.9.24') < 0)
		$queries[] = "ALTER TABLE  `$tbl_files` CHANGE  `file_direct_linking`  `file_direct_linking` ENUM(  '0',  '1',  '2' ) NOT NULL DEFAULT '0'";

	
	// since 0.2.9.25
	
	// fix (0,1,3) => (0,1,2)
	$queries[] = "@ALTER TABLE `$tbl_files` CHANGE  `file_direct_linking`  `file_direct_linking` ENUM(  '0',  '1',  '2' )  NOT NULL DEFAULT  '0'";
	
	// roles text
	$queries[] = "ALTER TABLE  `$tbl_files` CHANGE  `file_user_roles`  `file_user_roles` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  ''";
	$queries[] = "ALTER TABLE  `$tbl_cats` CHANGE  `cat_user_roles`  `cat_user_roles` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  ''";
				
				
	$queries[] = "OPTIMIZE TABLE `$tbl_cats`";
	$queries[] = "OPTIMIZE TABLE `$tbl_files`";

	// dont use wpdb->query, because it prints errors
	foreach($queries as $sql)
	{
		if($sql{0} == '@') {
			$sql = substr($sql, 1);
			@mysql_query($sql, $wpdb->dbh);
		} else {
			$wpdb->query($sql);
		}
			
	}
	
	// since 0.2.9.13 : file_mtime, use file_date as default
	if(!$wpdb->get_var("SHOW COLUMNS FROM `$tbl_files` LIKE 'file_mtime'")) {		
		$wpdb->query("ALTER TABLE `$tbl_files` ADD `file_mtime` bigint(20) unsigned NOT NULL default '0' AFTER `file_date`");
		
		$files = $wpdb->get_results("SELECT file_id,file_date FROM $tbl_files");
		foreach ( (array) $files as $file ) {
			$wpdb->query("UPDATE `$tbl_files` SET `file_mtime` = '".mysql2date('U', $file->file_date)."' WHERE `file_id` = $file->file_id");
		}
		// this is faster, but UNIX_TIMESTAMP adds leap seconds, so all files will be synced again!
		//$wpdb->query("UPDATE `$tbl_files` SET `file_mtime` = UNIX_TIMESTAMP(`file_date`) WHERE file_mtime = 0;");
	}
	

	// convert all required_level -> user_roles
	if(!!$wpdb->get_var("SHOW COLUMNS FROM `$tbl_files` LIKE 'file_required_level'")) {		
		$files = $wpdb->get_results("SELECT file_id,file_required_level FROM $tbl_files WHERE file_required_level <> 0");
		foreach ( (array) $files as $file ) {
			$wpdb->query("UPDATE `$tbl_files` SET `file_user_roles` = '|".WPFB_Setup::UserLevel2Role($file->file_required_level - 1)."' WHERE `file_id` = $file->file_id");
		}
		$wpdb->query("ALTER TABLE `$tbl_files` DROP `file_required_level`");
	}
	
	if(!!$wpdb->get_var("SHOW COLUMNS FROM `$tbl_cats` LIKE 'cat_required_level'")) {		
		$cats = $wpdb->get_results("SELECT cat_id,cat_required_level FROM $tbl_cats WHERE cat_required_level <> 0");
		foreach ( (array) $cats as $cat ) {
			$wpdb->query("UPDATE `$tbl_cats` SET `cat_user_roles` = '|".WPFB_Setup::UserLevel2Role($cat->cat_required_level - 1)."' WHERE `cat_id` = $cat->cat_id");
		}
		$wpdb->query("ALTER TABLE `$tbl_cats` DROP `cat_required_level`");
	}
	
	/* NOT neeeded since using fulltext index!
	// add leading | to user_roles
	if(!empty($old_ver) && version_compare($old_ver, '0.2.9.24') < 0) {
		$wpdb->query("UPDATE `$tbl_files` SET `file_user_roles` = CONCAT('|', `file_user_roles`) WHERE LEFT(`file_user_roles`, 1) <> '|'");
		$wpdb->query("UPDATE `$tbl_cats` SET `cat_user_roles` = CONCAT('|', `cat_user_roles`) WHERE LEFT(`cat_user_roles`, 1) <> '|'");
	}
	*/
}

static function UserLevel2Role($level)
{
	if($level >= 8) return 'administrator';
	if($level >= 5)	return 'editor';
	if($level >= 2)	return 'author';
	if($level >= 1)	return 'contributor';
	if($level >= 0)	return 'subscriber';
	return null;
}

static function DropDBTables()
{
	global $wpdb;	
	$tables = array($wpdb->wpfilebase_files, $wpdb->wpfilebase_files_id3, $wpdb->wpfilebase_cats
);		
	foreach($tables as $tbl)
		$wpdb->query("DROP TABLE IF EXISTS `$tbl`");
}

static function ConvertOldTags()
{
	global $wpdb;	
	$result = array('n_tags' => 0, 'tags' => array(), 'errors' => array());	
	
	$results = $wpdb->get_results("SELECT ID,post_content,post_title FROM $wpdb->posts WHERE post_content LIKE '%[filebase:%'", ARRAY_A);	
	if(empty($results)) return;

	foreach(array_keys($results) as $i)
	{	$post =& $results[$i];
		$uid = $post['ID'].' - '.$post['post_title'];
		$ctags = self::ContentReplaceOldTags($post['post_content']);
		if(($nt = count($ctags)) > 0) {
			if($wpdb->update($wpdb->posts, $post, array('ID' => $post['ID']))) {
				$result['tags'][$uid] = $ctags;
				$result['n_tags'] += $nt;
			} else $result['errors'][$uid] = 'DB Error: '.$wpdb->last_error;
		} else $result['errors'][$uid] = 'Invalid tag';		
	}
	
	return $result;
}

static function ContentReplaceOldTags(&$content)
{
	$converted = array();
	// new tag parser, complex but fast & flexible
	$offset = 0;
	$num = 0;
	while(($tag_start = strpos($content, '[filebase:', $offset)) !== false)
	{
		$tag_end = strpos($content, ']', $tag_start + 10);  // len of '[filebase:'
		if($tag_end === false)  break; // no more tag ends, break
		$tag_len = (++$tag_end) - $tag_start;		
		$tag_str = substr($content, $tag_start, $tag_len);
		$tag = explode(':', substr($tag_str, 10, -1));
		if(!empty($tag[0])) {
			$args = array();
			for($i = 1; $i < count($tag); ++$i) {
				$ta = $tag[$i];
				if($pos = strpos($ta, '='))
					$args[substr($ta, 0, $pos)] = substr($ta, $pos + 1);
				elseif(substr($ta, 0, 4) == 'file' && is_numeric($tmp = substr($ta, 4))) // support for old tags
					$args['file'] = intval($tmp);
				elseif(substr($ta, 0, 3) == 'cat' && is_numeric($tmp = substr($ta, 3)))
					$args['cat'] = intval($tmp);
			}
			$tag_content = '';
			
			// convert!!
			$tag_type = $tag[0];
			if($tag_type == 'filelist') $tag_type = 'list';			
			$tag_content = "[wpfilebase tag=$tag_type";
			
			$id = !empty($args['file']) ? $args['file'] : (!empty($args['cat']) ? $args['cat'] : 0);		
			if($id > 0) $tag_content .= " id=$id";
			
			if(!empty($args['tpl'])) $tag_content .= " tpl=".$args['tpl']."";
			
			$tag_content .= ']';
			
			$converted[$tag_str] = $tag_content;
		}

		// insert the content (replace tag)
		$content = (substr($content, 0, $tag_start) . $tag_content . substr($content, $tag_end));
		$offset += strlen($tag_content);
		$num++;
	}
	
	return $converted;
}

static function UnProtectUploadPath()
{
	$dir = WPFB_Core::UploadDir();
	if(!is_dir($dir)) WPFB_Admin::Mkdir($dir);
	$htaccess = "$dir/.htaccess";
	
	if(is_file($htaccess)) @unlink($htaccess);	
	return $htaccess;
}

static function ProtectUploadPath()
{
	$htaccess = self::UnProtectUploadPath();
	
	if(WPFB_Core::$settings->protect_upload_path && is_writable(WPFB_Core::UploadDir()) && ($fp = @fopen($htaccess, 'w')) )
	{
		@fwrite($fp, "Order deny,allow\n");
		@fwrite($fp, "Deny from all\n");
		@fclose($fp);
		return @chmod($htaccess, octdec(WPFB_PERM_FILE));
	}	
	return false;
}

static function OnActivateOrVerChange($old_ver=null) {
	global $wpdb;
	wpfb_loadclass('Admin','File','Category');
	self::SetupDBTables($old_ver);
	$old_options = get_option(WPFB_OPT_NAME);
	self::AddOptions();
	self::AddTpls($old_ver);
	$new_options = get_option(WPFB_OPT_NAME);
	WPFB_Admin::SettingsUpdated($old_options, $new_options);
	self::ProtectUploadPath();
	
	WPFB_Admin::WPCacheRejectUri(WPFB_Core::$settings->download_base . '/', $old_options['download_base'] . '/');
		
	$ncats = WPFB_Category::GetNumCats();
	$nfiles = WPFB_File::GetNumFiles();
	
	if($ncats < self::MANY_CATEGORIES && $nfiles < self::MANY_FILES) { // avoid long activation time
		wpfb_loadclass('Sync');
		WPFB_Sync::SyncCats();
		WPFB_Sync::UpdateItemsPath();
	}
	
	if (!wp_next_scheduled(WPFB.'_cron'))	
		wp_schedule_event(time(), 'hourly', WPFB.'_cron');	
	if(!get_option('wpfb_install_time')) add_option('wpfb_install_time', (($ft=(int)mysql2date('U',$wpdb->get_var("SELECT file_mtime FROM $wpdb->wpfilebase_files ORDER BY file_mtime ASC LIMIT 1")))>0)?$ft:time(), null, 'no');
	
	
	
	// move old css
	if(file_exists(WPFB_Core::GetOldCustomCssPath())) {
		$wp_upload = wp_upload_dir();
		$wp_upload_ok = (empty($wp_upload['error']) && is_writable($wp_upload['basedir']));
		if($wp_upload_ok && @rename(WPFB_Core::GetOldCustomCssPath(), $wp_upload['basedir'] . '/wp-filebase.css')) {
			update_option('wpfb_css', $wp_upload['baseurl'] . '/wp-filebase.css?t='.time());
		}
	}
	
	flush_rewrite_rules();
	
	delete_option('wpfilebase_dismiss_support_ending');
}

static function OnDeactivate() {
	wp_clear_scheduled_hook(WPFB.'_cron');
	
	self::UnProtectUploadPath();
	
	delete_option('wpfilebase_dismiss_support_ending');
	
	if(get_option('wpfb_uninstall')) {
		self::RemoveOptions();
		self::DropDBTables();
		self::RemoveTpls();
		
		delete_option('wpfilebase_cron_sync_time');		
		delete_option('wpfb_license_key');
		delete_option('wpfilebase_last_check');
		delete_option('wpfilebase_forms');
		delete_option('wpfilebase_ftags');
		delete_option('wpfilebase_rsyncs');
		
		delete_option('wpfb_uninstall');
	}
}

}