<?php class WPFB_Models {

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
	
	
	//'file_thumbnail'		=> __('Name of the thumbnail file', WPFB), // useless
	'cat_icon_url'			=> __('URL of the category icon (if any)', WPFB),
	'cat_small_icon'		=> __('Category').': '.sprintf(__('HTML image tag for a small icon (height %d)'), 32),
	'cat_id'					=> __('The category ID', WPFB),

	
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
	foreach(wpfb_call('Models','TplVarsDesc',$for_cat) as $tag => $desc)
		$out .= '<option value="'.$tag.'" title="'.$desc.'">'.$tag.($short ? '' : ' ('.$desc.')').'</option>';
	$out .= '</select>';
	$out .= '<small>('.__('For some files there are more tags available. You find a list of all tags below the form when editing a file.',WPFB).'</small>';
	return $out;
}
}
