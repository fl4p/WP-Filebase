<?php class WPFB_Models {

static function TplVarsDesc($for_cat=false)
{
	if($for_cat) return array(	
	'cat_name'				=> __('The category name','wp-filebase'),
	'cat_description'		=> __('Short description','wp-filebase'),
	
	'cat_url'				=> __('The category URL','wp-filebase'),
	'cat_path'				=> __('Category path (e.g cat1/cat2/)','wp-filebase'),
	'cat_folder'			=> __('Just the category folder name, not the path','wp-filebase'),
	
	'cat_icon_url'			=> __('URL of the thumbnail or icon','wp-filebase'),
	'cat_small_icon'		=> sprintf(__('HTML image tag for a small icon (height %d)'), 32),
	'cat_has_icon'			=> __('Wether the category has a custom icon (boolean 0/1)'),

	
	'cat_parent_name'		=> __('Name of the parent categories (empty if none)','wp-filebase'),
	'cat_num_files'			=> __('Number of files in the category','wp-filebase'),
	'cat_num_files_total'			=> __('Number of files in the category and all child categories','wp-filebase'),
	
	//'cat_required_level'	=> __('The minimum user level to view this category (-1 = guest, 0 = Subscriber ...)','wp-filebase'),
	'cat_user_can_access'	=> sprintf(__('Variable to check if the %s is accessible (boolean 0/1)','wp-filebase'),__('Category')),
	
	'cat_id'				=> __('The category ID','wp-filebase'),
	'uid'					=> __('A unique ID number to identify elements within a template','wp-filebase'),
	'is_mobile'             => __('1 if access from mobile device, otherwise 0','wp-filebase')
	);
	else return array_merge(array(	
	'file_display_name'		=> __('Title','wp-filebase'),
	'file_name'				=> __('Name of the file','wp-filebase'),
	
	'file_url'				=> __('Download URL','wp-filebase'),
	'file_url_encoded'		=> __('Download URL encoded for use in query strings','wp-filebase'),
	'file_url_no_preview'	=> __('Download link that always points to the actual file','wp-filebase'),
	
	'file_icon_url'			=> __('URL of the thumbnail or icon','wp-filebase'),
        'file_small_icon'              => __('A small icon (HTML element)','wp-filebase'),
	
	
	'file_size'				=> __('Formatted file size','wp-filebase'),
	'file_date'				=> __('Formatted file date','wp-filebase'),
	'file_version'			=> __('File version','wp-filebase'),	
	'file_author'			=> __('Author'),
	'file_tags'				=> __('Tags'),
	'file_description'		=> __('Short description','wp-filebase'),	
	'file_languages'		=> __('Supported languages','wp-filebase'),
	'file_platforms'		=> __('Supported platforms (operating systems)','wp-filebase'),
	'file_requirements'		=> __('Requirements to use this file','wp-filebase'),
	'file_license'			=> __('License','wp-filebase'),
	
	'file_category'			=> __('The category name','wp-filebase'),
	
	
	//'file_thumbnail'		=> __('Name of the thumbnail file','wp-filebase'), // useless
	'cat_icon_url'			=> __('URL of the category icon (if any)','wp-filebase'),
	'cat_small_icon'		=> __('Category').': '.sprintf(__('HTML image tag for a small icon (height %d)'), 32),
	'cat_id'					=> __('The category ID','wp-filebase'),

	
	//'file_required_level'	=> __('The minimum user level to download this file (-1 = guest, 0 = Subscriber ...)','wp-filebase'),
	'file_user_can_access'	=> sprintf(__('Variable to check if the %s is accessible (boolean 0/1)','wp-filebase'),__('File','wp-filebase')),
	
	'file_offline'			=> __('1 if file is offline, otherwise 0','wp-filebase'),
	'file_direct_linking'	=> __('1 if direct linking is allowed, otherwise 0','wp-filebase'),
	
	//'file_update_of'		=>
	'file_post_id'			=> __('ID of the post/page this file belongs to','wp-filebase'),
	'file_added_by'			=> __('User Name of the owner','wp-filebase'),
	'file_hits'				=> __('How many times this file has been downloaded.','wp-filebase'),
	//'file_ratings'			=>
	//'file_rating_sum'		=>
	'file_last_dl_ip'		=> __('IP Address of the last downloader','wp-filebase'),
	'file_last_dl_time'		=> __('Time of the last download','wp-filebase'),
	
	'file_extension'		=> sprintf(__('Lowercase file extension (e.g. \'%s\')','wp-filebase'), 'pdf'),
	'file_type'				=> sprintf(__('File content type (e.g. \'%s\')','wp-filebase'), 'image/png'),
	

	'file_post_url'			=> __('URL of the post/page this file belongs to','wp-filebase'),
	
	'file_path'				=> __('Category path and file name (e.g cat1/cat2/file.ext)','wp-filebase'),
	
	'file_id'				=> __('The file ID','wp-filebase'),
	
	'uid'					=> __('A unique ID number to identify elements within a template','wp-filebase'),
	'post_id'				=> __('ID of the current post or page','wp-filebase'),
	'wpfb_url'				=> sprintf(__('Plugin root URL (%s)','wp-filebase'), WPFB_PLUGIN_URI),
	'is_mobile'             => __('1 if access from mobile device, otherwise 0','wp-filebase'),

		'button_edit' => __('Edit button, only visible for users with permission.'),
		'button_delete' => __('Delete button, only visible for users with permission.'),
	), WPFB_Core::GetCustomFields(true));
}

	static function FileSortFields()
	{
		return array_merge(array(
			'file_display_name'		=> __('Title','wp-filebase'),
			'file_name'				=> __('Name of the file','wp-filebase'),
			'file_version'			=> __('File version','wp-filebase'),

			'file_hits'				=> __('How many times this file has been downloaded.','wp-filebase'),
			'file_size'				=> __('Formatted file size','wp-filebase'),
			'file_date'				=> __('Formatted file date','wp-filebase'),
			'file_last_dl_time'		=> __('Time of the last download','wp-filebase'),

			'file_path'				=> __('Relative path of the file'),
			'file_id'				=> __('File ID'),

			'file_category_name'	=> __('Category Name','wp-filebase'),
			'file_category'			=> __('Category ID','wp-filebase'),

			'file_description'		=> __('Short description','wp-filebase'),
			'file_author'			=> __('Author','wp-filebase'),
			'file_license'			=> __('License','wp-filebase'),

			'file_post_id'			=> __('ID of the post/page this file belongs to','wp-filebase'),
			'file_added_by'			=> __('User Name of the owner','wp-filebase'),

			//'file_offline'			=> __('Offline &gt; Online','wp-filebase'),
			//'file_direct_linking'	=> __('Direct linking &gt; redirect to post','wp-filebase'),

		), WPFB_Core::GetCustomFields(true));
	}

	static function FileListColumns()
	{
		return array_merge(array(
			'file_display_name'		=> __('Name'),
			'file_name'				=> __('Filename','wp-filebase'),
			'file_version'			=> __('Version','wp-filebase'),

			'file_hits'				=> __('Hits','wp-filebase'),
			'file_size'				=> __('Size'/*def*/),
			'file_date'				=> __('Date'/*def*/),
			'file_last_dl_time'		=> __('Last download','wp-filebase'),

			'file_path'				=> __('Path','wp-filebase'),
			'file_id'				=> __('ID'),

			'file_category_name'	=> __('Category Name','wp-filebase'),

			'file_description'		=> __('Description'/*def*/),
			'file_author'			=> __('Author','wp-filebase'),
			'file_license'			=> __('License','wp-filebase'),

			'file_added_by'			=> __('Owner','wp-filebase'),

			'file_thumbnail'		=> __('Icon')

			//'file_offline'			=> __('Offline &gt; Online','wp-filebase'),
			//'file_direct_linking'	=> __('Direct linking &gt; redirect to post','wp-filebase'),

		), WPFB_Core::GetCustomFields(true));
	}

static function CatSortFields()
{
	return array(
	'cat_name'			=> __('Category Name','wp-filebase'),
	'cat_folder'		=> __('Name of the Category folder','wp-filebase'),
	'cat_description'	=> __('Short description','wp-filebase'),	
	
	'cat_path'			=> __('Relative path of the category folder','wp-filebase'),
	'cat_id'			=> __('Category ID','wp-filebase'),
	'cat_parent'		=> __('Parent Category ID','wp-filebase'),
	
	'cat_num_files'		=> __('Number of files directly in the category','wp-filebase'),
	'cat_num_files_total' => __('Number of all files in the category and all sub-categories','wp-filebase'),
	
	'cat_order'			=> __('Custom Category Order','wp-filebase')
	
	//'cat_required_level' => __('The minimum user level to access (-1 = guest, 0 = Subscriber ...)','wp-filebase')
	);
}

static function TplFieldsSelect($input, $short=false, $for_cat=false)
{
	$out = __('Add template variable:','wp-filebase') . ' <select name="_wpfb_tpl_fields" onchange="WPFB_AddTplVar(this, \'' . $input . '\')"><option value="">'.__('Select').'</option>';	
	foreach(wpfb_call('Models','TplVarsDesc',$for_cat) as $tag => $desc)
		$out .= '<option value="'.$tag.'" title="'.$desc.'">'.$tag.($short ? '' : ' ('.$desc.')').'</option>';
	$out .= '</select>';
	$out .= '<small>('.__('For some files there are more tags available. You find a list of all tags below the form when editing a file.','wp-filebase').'</small>';
	return $out;
}
}
