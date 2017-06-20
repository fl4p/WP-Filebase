<?php class WPFB_AdminBar {


static function AdminBar() {
	global $wp_admin_bar;
	
        wpfb_call('Output', 'PrintJS');
	
	$wp_admin_bar->add_menu(array('id' => WPFB, 'title' => WPFB_PLUGIN_NAME, 'href' => admin_url('admin.php?page=wpfilebase_manage')));
	
	$wp_admin_bar->add_menu(array('parent' => WPFB, 'id' => WPFB.'-add-file', 'title' => __('Add File','wp-filebase'), 'href' => admin_url('admin.php?page=wpfilebase_files#addfile')));
	
	$current_object = get_queried_object();
	$is_filebrowser = false;
	if ( !empty($current_object) && !empty($current_object->post_type) && $current_object->ID > 0) {
            
             if($current_object->post_type != 'wpfb_filepage') {
		$is_filebrowser = ($current_object->ID == WPFB_Core::$settings->file_browser_post_id);
		$link = esc_attr(admin_url('admin.php?wpfilebase-screen=editor-plugin&manage_attachments=1&post_id='.$current_object->ID));
		$wp_admin_bar->add_menu( array( 'parent' => WPFB, 'id' => WPFB.'-attachments', 'title' => __('Manage attachments','wp-filebase'), 'href' => $link,
		'meta' => array('onclick' => 'window.open("'.$link.'", "wpfb-manage-attachments", "width=680,height=400,menubar=no,location=no,resizable=no,status=no,toolbar=no,scrollbars=yes");return false;')));
             } else {
                 $wp_admin_bar->add_menu(array('parent' => WPFB, 'id' => WPFB.'-edit-file', 'title' => __('Edit File','wp-filebase'), 'href' => get_edit_post_link($current_object->ID)));
             }
	}
	
	$wp_admin_bar->add_menu(array('parent' => WPFB, 'id' => WPFB.'-add-file', 'title' => __('Sync Filebase','wp-filebase'), 'href' => admin_url('admin.php?page=wpfilebase_manage&action=sync')));
	
	$wp_admin_bar->add_menu(array('parent' => WPFB, 'id' => WPFB.'-toggle-context-menu', 'title' => !empty(WPFB_Core::$settings->file_context_menu)?__('Disable file context menu','wp-filebase'):__('Enable file context menu','wp-filebase'), 'href' => 'javascript:;',
	'meta' => array('onclick' => 'return wpfb_toggleContextMenu();')));
	
	if($is_filebrowser) {
		$wp_admin_bar->add_menu(array('parent' => WPFB, 'id' => WPFB.'-toggle-drag-drop', 'title' => get_user_option('wpfb_set_fbdd') ? __('Disable file browser Drag &amp; Drop','wp-filebase') : __('Enable file browser Drag &amp; Drop','wp-filebase'), 'href' => 'javascript:;',
		'meta' => array('onclick' => 'jQuery.ajax({url:wpfbConf.ajurl,type:"POST",data:{wpfb_action:"set-user-setting",name:"fbdd",value:'.(get_user_option('wpfb_set_fbdd')?0:1).'},async:false});location.reload();return false;')));
	}
	
}
}
