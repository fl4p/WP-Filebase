<?php
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPFB_FileListTable extends WP_List_Table {
	
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'file',     //singular name of the listed records
            'plural'    => 'files',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
    
    function get_columns(){
        $columns = array(
            'cb'			=> '<input type="checkbox" />', //Render a checkbox instead of text
				'name'  =>		__('Name'/*def*/),
				'filename'     		=> __('Filename', WPFB),
				'size'     		=> __('Size'/*def*/),
				'desc'  	=> __('Description'/*def*/),
				'cat' => __('Category'/*def*/),
				'perms'    => __('Access Permission',WPFB),
				'owner'     	=> __('Owner',WPFB),
				'date'     		=> __('Date'/*def*/),
				'hits'    		=> __('Hits', WPFB),
				'last_dl_time'  => __('Last download', WPFB)
        );
		  
        return $columns;
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
				//'cb'   			=> array('file_id',false),
				'name'  =>		array('file_display_name',false),
				'filename'     		=> array('file_name',false),
				'size'     		=> array('file_size',false),
				'desc'			=> array('file_description',false),
				'cat'				=>	array('file_category_name',false),
				'perms'			=> array('file_user_roles',false),
				'owner'     	=> array('file_added_by',false),
				'date'     		=> array('file_date',false),
				'hits'    		=> array('file_hits',false),
				'last_dl_time'	=> array('file_last_dl_time',false),
        );
        return $sortable_columns;
    }
    
    function column_default($item, $column_name){
		if(strpos($column_name,'file_') !== 0)
			$column_name = "file_".$column_name;
    	return $item->$column_name;
    }
   
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" /><br /><span'.(($item->GetId()>999)?' style="font-size:10px;"':'').'>%2$s</span>', // 
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item->GetId()                //The value of the checkbox should be the record's id
        );
    }
    
    function column_name($file){
		 
		 $edit_url = esc_attr($file->GetEditUrl()."&redirect_to=".urlencode(add_query_arg('edited', $file->file_id) /*admin_url('admin.php?'.$_SERVER['QUERY_STRING'])*/));
       
		 $actions = array(
            'edit'      => '<a href="'.$edit_url.'">'.__('Edit').'</a>',
				'delete'    => '<a class="submitdelete" href="'.add_query_arg(array('action' => 'delete', 'file[]' => $file->GetId())).'" onclick="return confirm(\''.__("Are you sure you want to do this?").'\')">'.__('Delete').'</a>',
				'download'    => '<a href="'.esc_attr($file->GetUrl(false, false)).'">'.__('Download').'</a>',
        );
		 
		 if(!$file->CurUserCanEdit())
		 {
			 unset($actions['delete']);
		 }
        
        $col = '<a class="row-title" href="'.$edit_url.'" title="'.esc_attr(sprintf(__('Edit &#8220;%s&#8221;'),$file->GetTitle())).'">';
       // if(!empty($file->file_thumbnail))
        	$col .= '<img src="'.esc_attr($file->GetIconUrl()).'" alt="Icon" height="32" />';
        $col .= '<span>'.($file->IsRemote()?'*':'').esc_html($file->GetTitle(32)).'</span>';
        $col .= '</a>';							
        $col .= $this->row_actions($actions);
        return $col;
    }
    
    function column_filename($file)
    {
		$path = esc_html(dirname($file->GetLocalPathRel()));
    	return "<code>$path/</code><br />".'<a href="'.esc_attr($file->GetUrl()).'">'.esc_html($file->file_name).'</a>';
    }
    
    function column_size($file)
    {
    	return WPFB_Output::FormatFilesize($file->file_size);
    }
	 
    function column_desc($file)
    {
    	return empty($file->file_description) ? '-' : esc_html($file->file_description);
    }
    
    function column_cat($file)
    {
    	$cat = $file->GetParent();
    	return (!is_null($cat) ? ('<a href="'.esc_attr($cat->GetEditUrl()).'">'.esc_html($file->file_category_name).'</a>') : '-');
    }
    
    function column_perms($file)
    {
		return WPFB_Output::RoleNames($file->GetReadPermissions(), true);
    }
    
    function column_owner($file)
    {
    	return (empty($file->file_added_by) || !($usr = get_userdata($file->file_added_by))) ? '-' : esc_html($usr->user_login);
    }
    
    function column_date($file)
    {
    	return $file->GetFormattedDate();
    }
    
    function column_hits($file)
    {
    	return $file->file_hits;
    }
    
    function column_last_dl_time($file)
    {
    	return ( (!empty($file->file_last_dl_time) && $file->file_last_dl_time > 0) ? mysql2date(get_option('date_format'), $file->file_last_dl_time) : '-') .
				($file->file_offline ? '<br /><span class="offline">'.__('offline').'</span>' : '');
    }
    
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete',
				//'change_cat' => 'Change Category',
				'set_off' => 'Set Offline',
				'set_on' => 'Set Online',
        );
        return $actions;
    }
	 
	function get_views(){
		$current = ( !empty($_REQUEST['view']) ? $_REQUEST['view'] : 'all');
		$views = array('all' => 'All', 'own' => 'Own Files', 'offline' => 'Offline', 'notattached' => 'Not Attached');
		foreach($views as $tag => $label) {
			$class = ($current == $tag ? ' class="current"' :'');
			$url = ($tag=='all') ? remove_query_arg('view') : add_query_arg('view',$tag);
			$count =  WPFB_File::GetNumFiles2($this->get_file_where_cond($tag), 'edit');
			$views[$tag] = "<a href='{$url}' {$class} >{$label} <span class='count'>($count)</span></a>";
		}
		return $views;
	}
    
    function process_bulk_action() {
		 
		 if(!$this->current_action() || empty($_REQUEST['file']))
			 return;
		 
		 // filter files current user can edit
		 $files = array_filter(array_map(array('WPFB_File','GetFile'), $_REQUEST['file']),
					create_function('$file',
							  'return ($file && $file->CurUserCan'.'Edit'.'());'));
		 
			$message = null;
			switch($this->current_action())
			{
				case 'delete':
					foreach($files as $file) {
						$file->Remove(true);
					}
					WPFB_Admin::SyncCustomFields();
					$message = sprintf(__("%d File(s) deleted.",WPFB), count($files));
					
					break;
				
				
				case 'set_off':
					foreach($files as $file) {
						$file->file_offline = 1;
						$file->DbSave();
					}
					$message = sprintf(__("%d File(s) were set offline.",WPFB), count($files));
					break;
				
				case 'set_on':
					foreach($files as $file) {
						$file->file_offline = 0;
						$file->DbSave();
					}
					$message = sprintf(__("%d File(s) were set online.",WPFB), count($files));
					
					break;
			}
			
			if ( !empty($message) ) : ?><div id="message" class="updated fade"><p><?php echo $message; ?></p></div><?php endif; 
    }
	 
	 function get_file_where_cond($view='all')
	 {
		  global $wpdb, $current_user;
		 wpfb_loadclass('Search');
		$where = WPFB_Search::SearchWhereSql(true);
				
			if(!empty($_REQUEST['file_category'])) 
				$where = (empty($where) ? '' : ("($where) AND ")) . "file_category = " . intval($_REQUEST['file_category']);
			
			if(!empty($view) && $view != 'all') {
				$view_cond = "1=1";
				switch($view) {
					case 'own':
						$view_cond = "file_added_by = ".((int)$current_user->ID);
						break;
					case 'offline':
						$view_cond = "file_offline = '1'";
						break;
					case 'notattached':
						$view_cond = "file_post_id = 0";
						break;
				}
				$where = (empty($where) ? '' : ("($where) AND ")) . $view_cond;
			}
			
			return $where;
	 }
    
    function prepare_items() {
		 global $wpdb;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        
        $this->process_bulk_action();
         
		  
	
			$pagenum = $this->get_pagenum();
			if( !isset($filesperpage) || $filesperpage < 0 )
				$filesperpage = 50;
				
			$pagestart = ($pagenum - 1) * $filesperpage;
			
			$where = $this->get_file_where_cond(empty($_REQUEST['view']) ? null : $_REQUEST['view']);
			
			$order = "$wpdb->wpfilebase_files." . ((!empty($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_merge(array_keys(get_class_vars('WPFB_File')), array_keys(WPFB_Core::GetCustomFields(true))))) ?
				($_REQUEST['orderby']." ".( (!empty($_REQUEST['order']) && $_REQUEST['order'] == "desc") ? "DESC" : "ASC")) : "file_id DESC");

			$total_items = WPFB_File::GetNumFiles2($where, 'edit');		
			$files = WPFB_File::GetFiles2($where, 'edit', $order, $filesperpage, $pagestart);
			
			if(empty($files) && !empty($wpdb->last_error))
				wp_die("<b>Database error</b>: ".$wpdb->last_error);

        $this->items = $files;
        
    
        $this->set_pagination_args( array(
            'total_items' => $total_items, 
            'per_page'    => $filesperpage,
            'total_pages' => ceil($total_items/$filesperpage)
        ) );
    }

}