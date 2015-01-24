<?php class WPFB_AdminGuiFileBrowser {
	static function Display()
	{
		wpfb_loadclass('Output', 'File', 'Category','TplLib');
				
		$content ='';
		
		$file_tpls = WPFB_Core::GetTpls('file');
		$cat_tpls = WPFB_Core::GetTpls('cat');
		if(true || !isset($file_tpls['filebrowser_admin'])) {
			$file_tpls['filebrowser_admin'] = 
				'%file_small_icon% '.
				'%file_display_name% (%file_size%) <a href="%file_edit_url%" class="edit" onclick="wpfbFBEditFile(event)">Edit</a>'
			;
			WPFB_Core::SetFileTpls($file_tpls);
			WPFB_Admin::ParseTpls();
		}
		
		if(true || !isset($cat_tpls['filebrowser_admin'])) {
			$cat_tpls['filebrowser_admin'] = 
				'<span class="cat-icon" style="background-image:url(%cat_icon_url%);"><span class="cat-icon-overlay"></span></span>'.
				'%cat_name% <a href="%cat_edit_url%" class="edit" onclick="wpfbFBEditCat(event)">Edit</a> '
			;			
			WPFB_Core::SetCatTpls($cat_tpls);
			WPFB_Admin::ParseTpls();
		}
	

		WPFB_Output::FileBrowser($content, 0, empty($_GET['wpfb_cat']) ? 0 : intval($_GET['wpfb_cat']));	
		WPFB_Core::PrintJS();
		
?>
    <div class="wrap filebrowser-admin"> 
    <h2><?php _e('File Browser', WPFB) ?></h2>    
<?php
		echo '<div>'.__('You can Drag &amp; Drop (multiple) files directly on Categories to upload them. Dragging a category or an existing file to another category is also possible.',WPFB).'</div>';
		
		echo $content;
?>
	 </div>
<script>
	function wpfbFBEditFile(e) {
		e.stopPropagation();
	}
	
	function wpfbFBEditFile(e) {
		e.stopPropagation();
	}	
</script>
	
<?php
	}
}
