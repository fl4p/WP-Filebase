<?php

// used for debug output:
//@ini_set( 'display_errors', 1 );
@error_reporting(E_ERROR | E_PARSE);
register_shutdown_function('wpfb_on_shutdown');
function wpfb_on_shutdown()
{
	 $error = error_get_last( );
	 if( $error && $error['type'] != E_STRICT && $error['type'] != E_NOTICE && $error['type'] != E_WARNING  ) {
		  echo '<pre>FATAL ERROR:';
		  print_r( $error );
		  echo '</pre>';
	 } else { return true; }
}


define('WPFB_EDITOR_PLUGIN', 1);
if ( ! isset( $_GET['inline'] ) )
	define( 'IFRAME_REQUEST' , true );

// prevent other plugins from loading
define('WP_INSTALLING', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/wp-load.php');

/*
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
// check if WP-Filebase is active
$wpfb_rpath = basename(untrailingslashit(dirname(__FILE__))).'/wp-filebase.php';
if(!is_plugin_active($wpfb_rpath))
	wp_die("WP-Filebase not active. ($wpfb_rpath not in [".implode(',',get_option('active_plugins'))."]) <!-- FATAL ERROR: WP-Filebase DISABLED -->");
*/

require_once(ABSPATH . 'wp-admin/includes/admin.php');

// load wpfilebase only!
require_once('wp-filebase.php');

if(!function_exists('get_current_screen')) {	function get_current_screen() { return null; } }
if(!function_exists('add_meta_box')) {	function add_meta_box() { return null; } }

auth_redirect(); 

wpfb_loadclass('Core', 'File', 'Category', 'AdminLite', 'Admin', 'ListTpl', 'Output', 'Models');

wp_enqueue_script( 'common' );
wp_enqueue_script('jquery-ui-widget');
wp_enqueue_script( 'jquery-color' ); 
wp_enqueue_script('jquery-treeview-async');
wp_enqueue_script('postbox');
wp_enqueue_script('wpfb-editor-plugin', WPFB_PLUGIN_URI."js/editor-plugin.js", array(), WPFB_VERSION);

wp_enqueue_style( 'global' );
wp_enqueue_style( 'wp-admin' );
//wp_enqueue_style( 'colors' );
wp_enqueue_style( 'media' );
wp_enqueue_style( 'ie' );
wp_enqueue_style('jquery-treeview');

do_action('admin_init');

if(!current_user_can('publish_posts') && !current_user_can('edit_posts') && !current_user_can('edit_pages'))
	wp_die(__('Cheatin&#8217; uh?'));
	
@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

$action = empty($_REQUEST['action']) ? '' : $_REQUEST['action'];
$post_id = empty($_REQUEST['post_id']) ? 0 : intval($_REQUEST['post_id']);
$file_id = empty($_REQUEST['file_id']) ? 0 : intval($_REQUEST['file_id']);
$file = ($file_id > 0) ? WPFB_File::GetFile($file_id) : null;

$manage_attachments = !empty($_REQUEST['manage_attachments']);
$post_title = $post_id ? get_the_title($post_id) : null;

switch($action){
case 'detachfile':
	if($file && $file->CurUserCanEdit() && $file->file_post_id == $post_id) {
		$file->SetPostId(0);
		$file = null;
	}
	break;
	
case 'delfile':
	if($file && $file->CurUserCanEdit()) $file->Remove();
	$file = null;
	break;
	
case 'addfile':
	if ( !WPFB_Admin::CurUserCanUpload() ) wp_die(__('Cheatin&#8217; uh?'));
	break;
	
case 'updatefile':
	if ( !$file || !$file->CurUserCanEdit() ) wp_die(__('Cheatin&#8217; uh?'));
	break;
	
case 'change-order':
	foreach($_POST as $n => $v) {
		if(strpos($n, 'file_attach_order-') === 0)
		{
			$file_id = intval(substr($n, strlen('file_attach_order-')));
			if(!is_null($f = WPFB_File::GetFile($file_id))) {
				$f->file_attach_order = intval($v);
				$f->DBSave();
			}
		}
	}
	break;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<title><?php echo WPFB_PLUGIN_NAME ?></title>

<?php
//do_action('admin_enqueue_scripts', 'media-upload-popup'); // this caused fatal errors with other plugins
do_action('admin_print_styles-media-upload-popup');
do_action('admin_print_styles');
do_action('admin_print_scripts-media-upload-popup');
do_action('admin_print_scripts');
do_action('admin_head-media-upload-popup');
do_action('admin_head');

wp_admin_css( 'wp-admin', true );
wp_admin_css( 'colors-fresh', true );
?>

<style type="text/css">
<!--
	h2{
		margin: 8px 0 5px 0;
		font-size: 12px;
		padding: 0 0 4px 0;
		border-bottom: 1px #BAC3CA solid;
	}
	
	h3{
		font-size: 10px;
		margin-left: -4px;
	}
	
	a {color: #00457A; }
	
	#menu {
		text-align: center;
	}
	
	#menu .button {
		width: 120px;
	}
	
	#filelist, #insfilelist {
		margin: 5px;
	}
	
	#tpllist {
		margin-top: 10px;
	}
	
	.media-item a {
		margin-top: 10px;
	}
	
	.media-item input {
		text-align: right;
		width: 30px;
		display: inline-block;
	}
	
	.media-item img.pinkynail {
		display: inline-block;
		vertical-align: middle;
		float: none;
	}
	
	.media-item .filename {
		display: inline-block;
		vertical-align: middle;
	}
	
	form, .container {
		padding: 0;
		margin: 10px;
	}
	
	#media-upload .widefat {
		width: 100% !important;
	}
	
-->
</style>

<script type="text/javascript">
//<![CDATA[ 

var userSettings = {'url':'<?php echo SITECOOKIEPATH; ?>','uid':'<?php if ( ! isset($current_user) ) $current_user = wp_get_current_user(); echo $current_user->ID; ?>','time':'<?php echo time(); ?>'};
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>', pagenow = 'wpfilebase-popup', adminpage = 'wpfilebase-popup', isRtl = <?php echo (int) is_rtl(); ?>;
var wpfbAjax = '<?php echo WPFB_PLUGIN_URI."wpfb-ajax.php" ?>';
var usePathTags = <?php echo (int)WPFB_Core::$settings->use_path_tags ?>;
var yesImgUrl = '<?php echo admin_url( 'images/yes.png' ) ?>';
var manageAttachments = <?php echo (int)$manage_attachments ?>;
var autoAttachFiles = <?php echo (int)WPFB_Core::$settings->auto_attach_files ?>;

var theEditor;
var currentTab = '';
var selectedCats = [];
var includeAllCats = false;

function selectFile(id, name)
{
	var theTag = {"tag":currentTab, <?php echo WPFB_Core::$settings->use_path_tags ? '"path": getFilePath(id)' : '"id":id'; ?>};
	var el = jQuery('span.file','#wpfb-file-'+id).first();
	
	if(manageAttachments || currentTab == 'attach') {
		jQuery.ajax({
			url: wpfbAjax,
			data: {
				action:"attach-file",
				post_id:<?php echo $post_id ?>,
				file_id:id
			},
			async: false});
		//delayedReload();
		el.css('background-image', 'url('+yesImgUrl+')');
		return;
	} else if(currentTab == 'fileurl') {
<?php if(empty($_GET['content'])) {?>
		var linkText = prompt("<?php echo esc_attr(__('Enter link text. Prepend * to open link in a new tab.', WPFB)); ?>", name);
		if(!linkText || linkText == null || linkText == '')	return;
<?php } else echo " var linkText = '".$_GET['content']."'; "; ?>
		theTag.linktext = linkText;
	} else {
		var tpl = jQuery('input[name=filetpl]:checked', '#filetplselect').val();
		if(tpl && tpl != '' && tpl != 'default') theTag.tpl = tpl;
	}
	insertTag(theTag);
}

function insBrowserTag()
{
	var tag = {tag:currentTab};
	var root = parseInt(jQuery('#browser-root').val());
	if(root > 0)
		<?php echo WPFB_Core::$settings->use_path_tags ? 'tag.path = getCatPath(root);' : 'tag.id = root;'; ?>
				
		
	return insertTag(tag);
}


//]]>
</script>

</head>
<body id="media-upload" class="wp-core-ui" style="background:none;">

<div id="media-upload-header">
<?php if(!$manage_attachments) {?>
	<ul id='sidemenu'>
		<li><a href="#attach" onclick="return tabclick(this)"><?php _e('Attachments', WPFB) ?></a></li>
		<li><a href="#file" onclick="return tabclick(this)"><?php _e('Single file', WPFB) ?></a></li>
		<li><a href="#fileurl" onclick="return tabclick(this)"><?php _e('File URL', WPFB) ?></a></li>
		<li><a href="#list" onclick="return tabclick(this)"><?php _e('File list', WPFB) ?></a></li>
		<li><a href="#browser" onclick="return tabclick(this)"><?php _e('File Tree View', WPFB) ?></a></li>

	</ul>
<?php } ?>
</div>

<div id="attach" class="container">
<?php
if(!WPFB_Core::$settings->auto_attach_files) {
	echo '<div id="no-auto-attach-note" class="updated">';
	printf(__('Note: Listing of attached files is disabled. You have to <a href="%s">insert the attachments tag</a> to show the files in the content.'),'javascript:insAttachTag();');
	echo '</div>';
}


if($action =='addfile' || $action =='updatefile')
{
	// nonce/referer check (security)
	$nonce_action = WPFB."-".$action;
	if($action == 'updatefile') $nonce_action .= $_POST['file_id'];
	
	// check both nonces, since when using ajax uploader, the nonce if witout suffix -editor
	if(!wp_verify_nonce($_POST['wpfb-file-nonce'], $nonce_action."-editor") && !wp_verify_nonce($_POST['wpfb-file-nonce'], $nonce_action) )
		wp_die(__('Cheatin&#8217; uh?'));
	
	$result = WPFB_Admin::InsertFile(array_merge(stripslashes_deep($_POST), $_FILES));
	if(isset($result['error']) && $result['error']) {
		?><div id="message" class="updated fade"><p><?php echo $result['error']; ?></p></div><?php
		$file = new WPFB_File($_POST);
	} else {
		// success!!!!
		$file_id = $result['file_id'];
		if($action !='addfile')
			$file = null;
	}
}

$post_attachments = ($post_id > 0) ? WPFB_File::GetAttachedFiles($post_id, true) : array();
	
if($action != 'editfile' && (!empty($post_attachments) || $manage_attachments)) {
	?>
	<form action="<?php echo add_query_arg(array('action'=>'change-order')) ?>" method="post">	
	<h3 class="media-title"><?php echo $post_title ? sprintf(__('Files attached to <i>%s</i>',WPFB), $post_title) : __('Files', WPFB) ?></h3>
	<div id="media-items">
	<?php 
	if(empty($post_attachments)) echo "<div class='media-item'>",__('No items found.'),"</div>";
	else foreach($post_attachments as $pa) { ?>
		<div class='media-item'>
			<input type="text" size="3" name="file_attach_order-<?php echo $pa->file_id ?>" value="<?php echo $pa->file_attach_order ?>" />

			<?php if(!empty($pa->file_thumbnail)) { ?><img class="pinkynail toggle" src="<?php echo $pa->GetIconUrl(); ?>" alt="" /><?php } ?>

			<a class='toggle describe-toggle-on' href="<?php echo add_query_arg(array('file_id'=>$pa->file_id,'action'=>'delfile')) ?>" onclick="return confirm('Do you really want to delete this file?')" title="<?php _e('Delete') ?>"><img style="display: inline;" src="<?php echo WPFB_PLUGIN_URI.'extras/jquery/contextmenu/delete_icon.gif'; ?>" /></a>
			<a class='toggle describe-toggle-on' href="<?php echo add_query_arg(array('file_id'=>$pa->file_id,'action'=>'detachfile')) ?>" title="<?php _e('Remove') ?>"><img src="<?php echo WPFB_PLUGIN_URI.'extras/jquery/contextmenu/page_white_delete.png'; ?>" /></a>
			<a class='toggle describe-toggle-on' href="<?php echo add_query_arg(array('file_id'=>$pa->file_id,'action'=>'editfile')) ?>" title="<?php _e('Edit') ?>"><img src="<?php echo WPFB_PLUGIN_URI.'extras/jquery/contextmenu/page_white_edit.png'; ?>" /></a>

			<div class='filename'>
				<span class='title'><?php echo $pa->file_display_name ?></span>
			</div>
		</div>
	<?php }	?>
	</div>
	<input type="submit" name="change-order" value="<?php _e('Change Order', WPFB) ?>" />
	</form>
	<?php
}
	// switch simple/extended form
	if(isset($_GET['exform'])) {
		$exform = (!empty($_GET['exform']) && $_GET['exform'] == 1);
		update_user_option($user_ID, WPFB_OPT_NAME . '_exform_ep', $exform); 
	} else {
		$exform = (bool)get_user_option(WPFB_OPT_NAME . '_exform_ep');
	}
	
//if( (WPFB_Admin::CurUserCanUpload()&&empty($file))) TODO
	WPFB_Admin::PrintForm('file', $file, array('exform'=>$exform, 'in_editor'=>true, 'post_id'=>$post_id));
?>
<h3 class="media-title"><?php _e('Attach existing file', WPFB) ?></h3>
<ul id="attachbrowser" class="filetree"></ul>
</div> <!-- attach -->
	
<?php if(!$manage_attachments) {?>
<form id="filetplselect">
	<h2><?php _e('Select Template', WPFB) ?></h2>
	<label><input type="radio" name="filetpl" value="" checked="checked" /><i><?php _e('Default Template', WPFB) ?></i></label><br />
	<?php $tpls = WPFB_Core::GetFileTpls();
		if(!empty($tpls)) {
			foreach($tpls as $tpl_tag => $tpl_src)
				echo '<label><input type="radio" name="filetpl" value="' . esc_attr($tpl_tag) . '" />' . esc_html($tpl_tag) . '</label><br />';
		} ?>
	<i><a href="<?php echo admin_url('admin.php?page=wpfilebase_tpls#file') ?>" target="_parent"><?php _e('Add Template', WPFB) ?></a></i>
</form>
<div id="fileselect" class="container">
	<h2><?php _e('Select File', WPFB); ?></h2>
	<ul id="filebrowser" class="filetree"></ul>
</div>
<div id="catselect" class="container">
	<h2><?php _e('Select Category'/*def*/); ?></h2>
	<div id="catselect-filter">
		<p><?php _e('Select the categories containing the files you would like to list.',WPFB); ?></p>
		<p><input type="checkbox" id="list-all-files" name="list-all-files" value="1" onchange="incAllCatsChanged(this.checked)"/> <label for="list-all-files"><?php _e('Include all Categories',WPFB); ?></label></p>
	
	</div>
	
	<ul id="catbrowser" class="filetree"></ul>
</div>
<form id="listtplselect">
	<h2><?php _e('Select Template', WPFB) ?></h2>
	<?php $tpls = WPFB_ListTpl::GetAll();
		if(!empty($tpls)) {
			foreach($tpls as $tpl)
				echo '<label><input type="radio" name="listtpl" value="'.$tpl->tag.'" />'.$tpl->GetTitle().'</label><br />';
		} ?>
	<i><a href="<?php echo admin_url('admin.php?page=wpfilebase_tpls#list') ?>" target="_parent"><?php _e('Add Template', WPFB) ?></a></i>
</form>

<form id="list">
	<p>
	<label for="list-num"><?php _e('Files per page:',WPFB) ?></label>
	<input name="list-num" type="text" id="list-num" value="0" class="small-text" />
	<?php printf(__('Set to 0 to use the default limit (%d), -1 will disable pagination.',WPFB), WPFB_Core::$settings->filelist_num) ?>
		
	</p>
	
	<p id="list-pagenav-wrap">
	<input type="checkbox" id="list-pagenav" name="list-pagenav" value="1" checked="checked" />
	<label for="list-pagenav"><?php _e('Display Page Navigation',WPFB); ?></label>
	</p>
	
	<p>
	<input type="checkbox" id="list-show-cats" name="list-show-cats" value="1" />
	<label for="list-show-cats"><?php _e('Group by Categories',WPFB); echo " / "; _e('List selected Categories',WPFB) ?></label>
	</p>
	
	<p><a class="button-primary" style="position: fixed; right: 8px; bottom: 8px;" href="javascript:void(0)" onclick="return insListTag()"><?php echo _e('Insert') ?></a><br />
	 </p>
</form>


<form id="browser">
	<p><?php _e('Select the root category of the tree view file browser:',WPFB); ?><br />	
	<select name="browser-root" id="browser-root"><?php echo WPFB_Output::CatSelTree(array('none_label' => __('All'))); ?></select>
	</p>
	
	
	<p><a class="button-primary" style="position: fixed; right: 8px; bottom: 8px;" href="javascript:void(0)" onclick="return insBrowserTag()"><?php echo _e('Insert') ?></a></p>
</form>

<form id="filesort">
	<h2><?php _e('Sort Order:'); ?></h2>
	<p>
	<label for="list-sort-by"><?php _e("Sort by:") ?></label>
	<select name="list-sort-by" id="list-sort-by" style="width:100%">
		<option value=""><?php _e('Default'); echo ' ('.WPFB_Core::$settings->filelist_sorting.')'; ?></option>
		<?php $opts = WPFB_Models::FileSortFields();
		foreach($opts as $tag => $name) echo '<option value="'.$tag.'">'.$tag.' - '.$name.'</option>'; ?>
	</select>	
	<input type="radio" checked="checked" name="list-sort-order" id="list-sort-order-asc" value="asc" />
	<label for="list-sort-order-asc" class="radio"><?php _e('Ascending'); ?></label>
	<input type="radio" name="list-sort-order" id="list-sort-order-desc" value="desc" />
	<label for="list-sort-order-desc" class="radio"><?php _e('Descending'); ?></label>
	</p>
</form>

<form id="catsort">
	<p>
	<label for="list-cat-sort-by"><?php _e("Category order",WPFB) ?>:</label>
	<select name="list-cat-sort-by" id="list-cat-sort-by" style="width:100%">
		<option value=""><?php _e('None (order of IDs in shortcode)', WPFB); ?></option>
		<?php $opts = WPFB_Models::CatSortFields();
		foreach($opts as $tag => $name) echo '<option value="'.$tag.'">'.$tag.' - '.$name.'</option>'; ?>
	</select>	
	<input type="radio" checked="checked" name="list-cat-sort-order" id="list-cat-sort-order-asc" value="asc" />
	<label for="list-cat-sort-order-asc" class="radio"><?php _e('Ascending'); ?></label>
	<input type="radio" name="list-cat-sort-order" id="list-cat-sort-order-desc" value="desc" />
	<label for="list-cat-sort-order-desc" class="radio"><?php _e('Descending'); ?></label>
	</p>
</form>




<?php } /*manage_attachments*/ ?>

<?php
do_action('admin_print_footer_scripts');
?>
<script type="text/javascript">
	initEditorPlugin();
	if(typeof wpOnload=='function')wpOnload();
</script>
<?php WPFB_Core::PrintJS(); /* only required for wpfbConf */ ?>
</body>
</html>