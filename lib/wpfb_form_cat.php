<?php
$update = isset($item) && is_object($item) && !empty($item->cat_id);

if($update) {
	$file_category = &$item;
	$exform = true;
} else
	$file_category = new WPFB_Category();
	
$action = $update ? 'updatecat' : 'addcat';
$title = $update ? __('Edit Category') : __('Add Category',WPFB);/*def*/
$form_name = $update ? 'editcat' : 'addcat';
$nonce_action = WPFB . "-" . $action . ($update ? $file_category->cat_id : '');	

$default_roles = WPFB_Core::$settings->default_roles;
$user_roles = ($update || empty($default_roles)) ? $file_category->GetReadPermissions() : $default_roles;
$cat_members_only = !empty($user_roles);

$form_action = add_query_arg('page', 'wpfilebase_cats', remove_query_arg(array('cat_id', 'page', 'action')));
?>

<div class="wrap">
<h2><?php echo $title ?></h2>
<div id="ajax-response"></div>
<form enctype="multipart/form-data" method="post" name="<?php echo $form_name ?>" id="<?php echo $form_name ?>" action="<?php echo $form_action ?>" class="validate">
<input type="hidden" name="action" value="<?php echo $action ?>" />
<input type="hidden" name="cat_id" value="<?php echo ($update ? $file_category->cat_id : 0) ?>" />
<?php wp_nonce_field($nonce_action, 'wpfb-cat-nonce'); ?>
	<table class="form-table">
		<tr class="form-field form-required">
			<th scope="row" valign="top"><label for="cat_name"><?php _e('New category name'/*def*/) ?></label></th>
			<td><input name="cat_name" id="cat_name" type="text" value="<?php echo esc_attr($file_category->cat_name); ?>" size="40" aria-required="true" /></td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="cat_folder"><?php _e('Category Folder', WPFB) ?></label></th>
			<td><input name="cat_folder" id="cat_folder" type="text" value="<?php echo esc_attr($file_category->cat_folder); ?>" size="40" /><br />
            <?php _e('The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.'/*def*/); ?></td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="cat_parent"><?php _e('Parent Category'/*def*/) ?></label></th>
			<td>
	  			<select name="cat_parent" id="cat_parent" class="postform" onchange="WPFB_formCategoryChanged();"><?php echo WPFB_Output::CatSelTree(array('selected'=>($update?$file_category->cat_parent:0),'exclude'=>$update?$file_category->cat_id:0)) ?></select><br />
                <?php _e('Categories, unlike tags, can have a hierarchy. You might have a Jazz category, and under that have children categories for Bebop and Big Band. Totally optional.'/*def*/); ?>
	  		</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="cat_description"><?php _e('Description') ?></label></th>
			<td><textarea name="cat_description" id="cat_description" rows="5" cols="50" style="width: 97%;"><?php echo esc_html($file_category->cat_description); ?></textarea></td>
		</tr>
		<tr>
			<th scope="row" valign="top" class="form-field"><label for="cat_icon"><?php _e('Category Icon', WPFB) ?></label></th>
			<td><input type="file" name="cat_icon" id="cat_icon" />
			<?php if(!empty($file_category->cat_icon)) { ?>
				<br /><img src="<?php echo $file_category->GetIconUrl(); ?>" alt="Icon" /><br />
				<input type="checkbox" value="1" name="cat_icon_delete" id="file_delete_thumb" /><label for="cat_icon_delete"><?php _e('Delete'/*def*/); ?></label>
			<?php } ?>
			</td>
		</tr>		 
		<tr>
			<th scope="row" valign="top"><?php _e('Access Permission',WPFB) ?></th>
			<td>
			<?php if($update) { ?><input type="hidden" name="cat_perm_explicit" value="1" />
			<?php } else { ?>		
				<label><input type="radio" name="cat_perm_explicit" value="0" <?php checked(true); ?> onchange="jQuery('#cat_perm_wrap').hide()" /><?php _e('Inherit Permissions', WPFB) ?> (<span id="cat_inherited_permissions_label"></span>)</label>
				<br />
				<label><input type="radio" name="cat_perm_explicit" value="1" onchange="jQuery('#cat_perm_wrap').show()" /><?php _e('Explicitly set permissions', WPFB) ?></label>
			<?php } ?>
				<div id="cat_perm_wrap" <?php if(!$update) { echo 'class="hidden"'; } ?>>
					<?php _e('Limit category access by selecting one or more user roles.',WPFB)?>
					<div id="cat_user_roles"><?php WPFB_Admin::RolesCheckList('cat_user_roles', $user_roles) ?></div>
				</div>
			</td>
		</tr>
		
		
		<?php if($update) { ?>
		<tr>
			<th scope="row" valign="top"><label for="cat_child_apply_perm"><?php _e('Apply permission to all child files', WPFB) ?></label></th>
			<td><input type="checkbox" name="cat_child_apply_perm" value="1" /> <?php _e('This will recursively update permissions of all existing child categories and files. Note that permissions of new files in this category are inherited automatically, without having checked this checkbox.',WPFB); ?></td>
		</tr>
		<?php } ?>
		<tr>
			<th scope="row" valign="top"><label for="cat_exclude_browser"><?php _e('Exclude from file browser', WPFB) ?></label></th>
			<td><input type="checkbox" name="cat_exclude_browser" value="1" <?php checked($file_category->cat_exclude_browser) ?> /></td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top"><label for="cat_order"><?php _e('Custom Sort Order', WPFB) ?></label></th>
			<td><input name="cat_order" id="cat_order" type="text" value="<?php echo esc_attr($file_category->cat_order); ?>" class="small-text" size="8" style="width:60px; text-align:right;" /></td>
		</tr>
	</table>
<p class="submit"><input type="submit" class="button-primary" name="submit" value="<?php echo _e($update?'Update':'Add New Category') ?>" /></p>
</form>
</div>