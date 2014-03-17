<?php
class WPFB_AdminGuiTpls {
	
static $sample_file = null;
static $sample_cat = null;
static $protected_tags = array('default','single','excerpt','filebrowser','filepage','filepage_excerpt');

static function InitClass() {
	global $user_identity;
	wpfb_loadclass('File', 'Category');
	
	self::$sample_file = new WPFB_File(array(
		'file_id' => 0,
		'file_name' => 'example.pdf',
		'file_display_name' => 'Example Document',
		'file_size' => 1024*1024*1.5,
		'file_date' => gmdate('Y-m-d H:i:s', time()),
		'file_hash' => md5(''),
		'file_thumbnail' => 'thumb.png',
		'file_description' => 'This is a sample description.',
		'file_version' => WPFB_VERSION,
		'file_author' => $user_identity,
		'file_hits' => 3,
		'file_added_by' => wp_get_current_user()->ID
	));
	
	self::$sample_cat = new WPFB_Category(array(
		'cat_id' => 0,
		'cat_name' => 'Example Category',
		'cat_description' => 'This is a sample description.',
		'cat_folder' => 'example',
		'cat_num_files' => 0, 'cat_num_files_total' => 0
	));
	
	self::$sample_file->Lock();
	self::$sample_cat->Lock();
}

static function Display()
{
	global $wpdb, $user_ID, $user_identity;
	
	wpfb_loadclass('Admin', 'Output', 'TplLib', 'ListTpl');
	
	WPFB_Core::PrintJS();
	
	$_POST = stripslashes_deep($_POST);
	$_GET = stripslashes_deep($_GET);	
	$action = (!empty($_POST['action']) ? $_POST['action'] : (!empty($_GET['action']) ? $_GET['action'] : ''));
	$clean_uri = remove_query_arg(array('message', 'action', 'file_id', 'cat_id', 'deltpl', 'hash_sync' /* , 's'*/)); // keep search keyword
	
	if($action == 'add' || $action == 'update')
	{
		if(empty($_POST['type'])) wp_die(__('Type missing!', WPFB));		
		if(empty($_POST['tpltag'])) wp_die(__('Please enter a template tag.', WPFB));	
		
		$type = $_POST['type'];
		$for_cat = ($type == 'cat');
		$tpl_tag = preg_replace('/[^a-z0-9_-]/', '', str_replace(' ', '_', strtolower($_POST['tpltag'])));
		if(empty($tpl_tag)) wp_die('Tag is invalid!');	
		
		if($type == 'list') {
			$data = array(
				'header' => $_POST['tpl-list-header'],
				'footer' => $_POST['tpl-list-footer'],
				'cat_tpl_tag' => $_POST['tpl-list-cat-tpl'],
				'file_tpl_tag' => $_POST['tpl-list-file-tpl'],
			);
			$tpl = new WPFB_ListTpl($tpl_tag, $data);
			$tpl->Save();
		} else {
			if(empty($_POST['tplcode'])) wp_die('Please enter some template code.');
			
			if($tpl_tag == 'default') {
				// hanle default tpls a bit different
				WPFB_Core::UpdateOption("template_$type", $_POST['tplcode']);
			} else { 
				$tpls = WPFB_Core::GetTpls($type);
				$tpls[$tpl_tag] = $_POST['tplcode'];
				if($for_cat) WPFB_Core::SetCatTpls($tpls);
				else WPFB_Core::SetFileTpls($tpls);
			}
		}
		WPFB_Admin::ParseTpls();
		
		unset($_POST['type'], $_POST['tpltag'], $_POST['tplcode']);	
	} elseif($action == 'del') {
		if(!empty($_GET['type']) && !empty($_GET['tpl']) && !in_array($_GET['tpl'], self::$protected_tags)) {
			$type = $_GET['type'];
			if($type == 'list') {
				$tpl = WPFB_ListTpl::Get($_GET['tpl']);
				if($tpl) $tpl->Delete();
			}
			$for_cat = ($type == 'cat');
			$tpls = WPFB_Core::GetTpls($type);
			unset($tpls['default']);
			if(!empty($tpls)) {
				unset($tpls[$_GET['tpl']]);
				if($for_cat) WPFB_Core::SetCatTpls($tpls);
				else WPFB_Core::SetFileTpls($tpls);
			}
			
			unset($_POST['type'], $_POST['tpl']);	
		}	
		WPFB_Admin::ParseTpls();
	}
	
	if(!empty($_POST['reset-tpls'])) {
		wpfb_call('Setup', 'ResetTpls');
		
		// also reset default templates stored in settings
		wpfb_loadclass('Admin');
		$settings_schema = WPFB_Admin::SettingsSchema();		
		WPFB_Core::UpdateOption('template_file', $settings_schema['template_file']['default']);
		WPFB_Core::UpdateOption('template_cat', $settings_schema['template_cat']['default']);
		
		WPFB_Admin::ParseTpls();
	}
	?>
	
<script type="text/javascript">
function WPFB_GenSuccess(data, textStatus, request)
{
	this.html(data);
}

function WPFB_PreviewTpl(ta, ty)
{
	var tplc = (ty != 'list') ? jQuery(ta).val() : {
		header: jQuery('#tpl-list-header').val(),
		footer: jQuery('#tpl-list-footer').val(),
		file_tpl_tag: jQuery('#tpl-list-file-tpl').val(),
		cat_tpl_tag: jQuery('#tpl-list-cat-tpl').val()
	};
	
	var previewId = 'tplinp_'+ty+'_preview';
	
	jQuery.ajax({
		type: 'POST',
		url: '<?php echo WPFB_PLUGIN_URI.'wpfb-ajax.php' ?>',
		data: {
			action: "tpl-sample",
			tpl: tplc,
			type: ty
		},
		async: true,
		success: WPFB_GenSuccess,
		context: jQuery('#'+previewId)
	});
}


jQuery(document).ready( function() {
	try { jQuery('#wpfb-tabs').tabs(); }
	catch(ex) {}
});

</script>

	<?php
	
	switch($action)
	{
	case 'edit':
		if(empty($_REQUEST['type']) || empty($_REQUEST['tpl'])) wp_die('Request error');
		
		$tpl_tag = $_REQUEST['tpl'];
		$type = $_REQUEST['type'];
		if($type == 'list') {
			if(WPFB_ListTpl::Get($tpl_tag) == null) wp_die('No such template!');
		} else {
			$for_cat = ($type == 'cat');		
			$tpl_src = WPFB_Core::GetTpls($type, $tpl_tag);		
			if(empty($tpl_src)) wp_die('No such template!');
		}
		echo '<div class="wrap">';
		self::TplForm($type, $tpl_tag);
		echo '</div>';
		
	break;
	
			
		default:
?>
<div class="wrap">
<h2><?php _e('Templates',WPFB); ?> <a href="<?php echo add_query_arg('iframe-preview',(int)empty($_GET['iframe-preview'])); ?>" class="add-new-h2">iframe preview</a></h2>
<div id="wpfb-tabs">
	<ul class="wpfb-tab-menu">
		<li><a href="#file"><?php _e('Files', WPFB) ?></a></li>
		<li><a href="#cat"><?php _e('Categories') ?></a></li>
		<li><a href="#list"><?php _e('File List', WPFB) ?></a></li>
	</ul>
	
	<div id="file" class="wrap">
	<p><?php _e('Templates used for single embedded files or file lists.',WPFB); ?></p>
	<?php self::TplsTable('file'); ?>
	</div>
	
	<div id="cat" class="wrap">
	<p><?php _e('These templates can be used for categories.',WPFB); ?></p>
	<?php self::TplsTable('cat'); ?>
	</div>
	
	<div id="list" class="wrap">
	<p><?php _e('A list-template consists of header, footer and file template. It can optionally have a category template to list sub-categories.',WPFB); ?></p>
	<?php self::TplsTable('list'); ?>
	</div>

	
	<div id="browser" class="wrap">
	</div>
</div> <!-- tabs -->

<form action="<?php echo remove_query_arg(array('action','type','tpl')) ?>" method="post" onsubmit="return confirm('<?php _e('This will reset all File, Category and List Templates! Are your sure?', WPFB) ?>');"><p>
	<input type="submit" name="reset-tpls" value="<?php _e('Reset all Templates to default', WPFB) ?>" class="button" />
</p></form>

</div>
<?php 
	break;

	}
}

static function TplsTable($type, $exclude=array(), $include=array()) {
	global $user_identity;
	$cat = ($type == 'cat');
	$list = ($type == 'list');
	$tpls = $list ? get_option(WPFB_OPT_NAME.'_list_tpls') : WPFB_Core::GetTpls($type);
	if(!$list) $tpls['default'] = WPFB_Core::GetOpt("template_$type");	
	
	$item = ($cat ? self::$sample_cat: self::$sample_file);
?>
<table class="widefat post fixed" cellspacing="0">
	<thead>
	<tr>
	<th scope="col" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
	<th scope="col" class="manage-column" style="width:200px"><?php _e('Name') ?></th>
	<th scope="col" class="manage-column column-title" style=""><?php _e('Preview') ?></th>
	</tr>
	</thead>

	<tfoot>
	<tr>
	<th scope="col" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
	<th scope="col" class="manage-column" style=""><?php _e('Name') ?></th>
	<th scope="col" class="manage-column column-title" style=""><?php _e('Preview') ?></th>
	</tr>
	</tfoot>

	<tbody>
<?php foreach($tpls as $tpl_tag => $tpl_src) {
	if( (!empty($include) && !in_array($tpl_tag, $include)) || (!empty($exclude) && in_array($tpl_tag, $exclude))) continue;
	$edit_link = add_query_arg(array('action'=>'edit','type'=>$type,'tpl'=>$tpl_tag));
	if($list) $tpl = WPFB_ListTpl::Get($tpl_tag);
	

	?>
	<tr id="tpl-<?php echo "$type-$tpl_tag" ?>" class="iedit" valign="top">
		<th scope="row" class="check-column"><input type="checkbox" name="tpl[]" value="<?php echo esc_attr($tpl_tag) ?>" /></th>
		<td class="column-title">
			<strong><a class="row-title" href="<?php echo $edit_link ?>" title="<?php printf(__('Edit &#8220;%s&#8221;'), $tpl_tag) ?>"><?php echo self::TplTitle($tpl_tag); ?></a></strong><br />
			<code>tpl=<?php echo $tpl_tag; ?></code>
			<div class="row-actions"><span class='edit'><a href="<?php echo $edit_link ?>" title="<?php _e('Edit this item') ?>"><?php _e('Edit') ?></a></span>
			<?php if(!in_array($tpl_tag, self::$protected_tags)){ ?><span class='trash'>| <a class='submitdelete' title='<?php _e('Delete this item permanently') ?>' href='<?php echo add_query_arg(array('action'=>'del','type'=>$type,'tpl'=>$tpl_tag)).'#'.$type ?>'><?php _e('Delete') ?></a></span><?php } ?>
			</div>
		</td>
		<td>
			<div class="entry-content wpfilebase-tpl-preview">
				<div id="tpl-preview_<?php echo $tpl_tag ?>">
					<?php if(!empty($_GET['iframe-preview'])) { ?>					
					<iframe src="<?php echo WPFB_PLUGIN_URI."tpl-preview.php?type=$type&tag=$tpl_tag"; ?>" style="width:100%;height:220px;"></iframe>
					<?php } else {
						$table_found = !$list && (strpos($tpl_src, '<table') !== false);
						if(!$list && !$table_found && strpos($tpl_src, '<tr') !== false) {
							$tpl_src = "<table>$tpl_src</table>";
						}
						echo do_shortcode($list ? $tpl->Sample(self::$sample_cat, self::$sample_file) : $item->GenTpl(WPFB_TplLib::Parse($tpl_src), 'sample'));
					} ?>
				</div>
					
				<div style="height: 50px; float: left;"></div>
				<div class="clear"></div>
			</div>
		</td>
	</tr>
		
	<?php } ?>
	</tbody>
</table>
<?php

	self::TplForm($type);
}

static function TplForm($type, $tpl_tag=null)
{	
	$new = empty($tpl_tag);
	$cat = ($type == 'cat');
	$list = ($type == 'list');
	$code_id = 'tplinp_'.$type;
	
	if(!$list) {
		if($new) {
			$tpl_code = empty($_POST['tplcode']) ? '' : $_POST['tplcode'];
		} else {
			$tpl_code = WPFB_Core::GetTpls($type, $tpl_tag);
			if(empty($tpl_code)) $tpl_code = '';
		}
		
		$item = ($cat?self::$sample_cat:self::$sample_file);
	} else {
		$tpl = $new ? new WPFB_ListTpl() : WPFB_ListTpl::Get($tpl_tag);
	}
?>
<h2><?php _e($new?'Add Template' : 'Edit Template', WPFB);
		if(!empty($tpl_tag)) echo ' '.self::TplTitle($tpl_tag);  ?></h2>
<form action="<?php echo remove_query_arg(array('action','type','tpl')).'#'.$type ?>" method="post">
	<input type="hidden" name="action" value="<?php echo $new?'add':'update'; ?>" />	
	<input type="hidden" name="type" value="<?php echo $type; ?>" />	
	<?php if($new) {?>
	<p>
		<label for="tpltag"><?php _e('Template Tag (a single word to describe the template):', WPFB) ?></label>
		<input type="text" name="tpltag" value="<?php if(!empty($_POST['tpltag'])) echo esc_attr($_POST['tpltag']); ?>" tabindex="1" maxlength="20" />
	</p>
	<?php } else { ?><input type="hidden" name="tpltag" value="<?php echo esc_attr($tpl_tag); ?>" /><?php }
	if($list) {?>
<table class="form-table">
	<tr class="form-field">
		<th scope="row" valign="top"><label for="tpl-list-header"><?php _e('Header', WPFB) ?></label></th>
		<td width="100%">
			<textarea id="tpl-list-header" name="tpl-list-header" cols="70" rows="<?php echo (max(2, count(explode("\n",$tpl->header)))+3); ?>" wrap="off" class="codepress html wpfilebase-tpledit" onkeyup="WPFB_PreviewTpl(this, '<?php echo $type ?>')" onchange="WPFB_PreviewTpl(this, '<?php echo $type ?>')"><?php echo htmlspecialchars($tpl->header) ?></textarea><br />
		</td>
	</tr>	
	<tr class="form-field">
		<th scope="row" valign="top"><label for="tpl-list-cat-tpl"><?php _e('Category Template', WPFB) ?></label></th>
		<td width="">
			<select id="tpl-list-cat-tpl" name="tpl-list-cat-tpl" onchange="WPFB_PreviewTpl(this, '<?php echo $type ?>')"><?php echo WPFB_Admin::TplDropDown('cat', $tpl->cat_tpl_tag); ?></select>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="tpl-list-file-tpl"><?php _e('File Template', WPFB) ?></label></th>
		<td>
			<select id="tpl-list-file-tpl" name="tpl-list-file-tpl" onchange="WPFB_PreviewTpl(this, '<?php echo $type ?>')"><?php echo WPFB_Admin::TplDropDown('file', $tpl->file_tpl_tag); ?></select>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row" valign="top"><label for="tpl-list-footer"><?php _e('Footer', WPFB) ?></label></th>
		<td>
			<textarea id="tpl-list-footer" name="tpl-list-footer" cols="70" rows="<?php echo (max(2, count(explode("\n",$tpl->footer)))+3); ?>" wrap="off" class="codepress html wpfilebase-tpledit" onkeyup="WPFB_PreviewTpl(this, '<?php echo $type ?>')" onchange="WPFB_PreviewTpl(this, '<?php echo $type ?>')"><?php echo htmlspecialchars($tpl->footer) ?></textarea><br />
		</td>
	</tr>

</table>
	<?php } else { ?>
	<p>
		<?php _e('Template Code:', WPFB) ?><br />
		<textarea id="<?php echo $code_id ?>" cols="70" rows="<?php echo (max(2, count(explode("\n",$tpl_code)))+3); ?>" wrap="off" name="tplcode" class="codepress html wpfilebase-tpledit" onkeyup="WPFB_PreviewTpl(this, '<?php echo $type ?>')" onchange="WPFB_PreviewTpl(this, '<?php echo $type ?>')"><?php echo htmlspecialchars($tpl_code) ?></textarea><br />
		<?php wpfb_loadclass('Models'); echo WPFB_Models::TplFieldsSelect($code_id, false, $cat) ?>
	</p>
	<?php } ?>
			
	<p class="submit"><input type="submit" name="submit" class="button-primary" value="<?php echo esc_attr__($new?'Add Template':'Submit Template Changes', WPFB) ?>" /></p>
</form>

<div class="entry-content wpfilebase-tpl-preview">
	<div id="<?php echo $code_id ?>_preview"><?php		
	if($list) echo $tpl->Sample(self::$sample_cat, self::$sample_file);
	else echo empty($tpl_code)?'<i>'.__('Preview').'</i>' : $item->GenTpl(WPFB_TplLib::Parse($tpl_code), 'sample');
	?></div>
	<div style="height: 50px; float: left;"></div>
	<div class="clear"></div>
</div>
<?php
}



static function TplTitle($tpl_tag)
{
 	return __(__(esc_html(WPFB_Output::Filename2Title($tpl_tag))), WPFB);
}
}