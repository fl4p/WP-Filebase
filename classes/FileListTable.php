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
        );
        return $columns;
    }
    
    function get_sortable_columns() {
        $sortable_columns = array(
        	'id'   			=> _e('ID'/*def*/),
        	'display_name'  => _e('Name'/*def*/),
        	'name'     		=> _e('Filename', WPFB),
        	'size'     		=> _e('Size'/*def*/),
        	//'description'  	=> _e('Description'/*def*/),
        	'category_name' => _e('Category'/*def*/),
        	'user_roles'    => _e('Access Permission',WPFB),
        	'added_by'     	=> _e('Owner',WPFB),
        	'date'     		=> _e('Date'/*def*/),
        	'hits'    		=> _e('Hits', WPFB),
        	'last_dl_time'  => _e('Last download', WPFB)
        );
        return $sortable_columns;
    }
    
    function column_default($item, $column_name){
    	$m = "file_".$column_name;
    	return $item->$m;
    }
   
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item->GetId()                //The value of the checkbox should be the record's id
        );
    }
    
    function column_display_name($item){
        $actions = array(
            'edit'      => '<a href="'.esc_attr($item->GetEditUrl()).'">"'.__('Edit').'</a>',
            'delete'    => '<a href="">"'.__('Delete').'</a>',
        );
        
        $col = '<a class="row-title" href="'.esc_attr($file->GetEditUrl()).'" title="'.esc_attr(sprintf(__('Edit &#8220;%s&#8221;'),$file->GetTitle())).'">';
        if(!empty($file->file_thumbnail))
        	$col .= '<img src="'.esc_attr($file->GetIconUrl()).'" height="32" />';
        $col .= '<span>'.($file->IsRemote()?'*':'').esc_html($file->GetTitle(32)).'</span>';
        $col .= '</a>';							
        $col .= $this->row_actions($actions);
        return $col;
    }
    
    function column_name($file)
    {
    	return '<a href="'.esc_attr($file->GetUrl()).'">'.esc_html($file->file_name).'</a>';
    }
    
    function column_size($file)
    {
    	return WPFB_Output::FormatFilesize($file->file_size);
    }
    
    function column_category_name($file)
    {
    	$cat = $file->GetParent();
    	return (!is_null($cat) ? ('<a href="'.esc_attr($cat->GetEditUrl()).'">'.esc_html($file->file_category_name).'</a>') : '-');
    }
    
    function column_user_roles($file)
    {
		return WPFB_Output::RoleNames($file->GetReadPermissions(), true);
    }
    
    function column_added_by($file)
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
    	return ( (!empty($file->file_last_dl_time) && $file->file_last_dl_time > 0) ? mysql2date(get_option('date_format'), $file->file_last_dl_time) : '-');
    }
    
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete',
            'edit' => 'Change Category',
        	''
        );
        return $actions;
    }
    
    function process_bulk_action() {
        
        //Detect when a bulk action is being triggered...
        if( 'delete'===$this->current_action() ) {
            wp_die('Items deleted (or they would be if we had items to delete)!');
        }
        
    }
    
    function prepare_items() {
        
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 5;
        
        
        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        
        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        
        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();
        
        
        /**
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example 
         * package slightly different than one you might build on your own. In 
         * this example, we'll be using array manipulation to sort and paginate 
         * our data. In a real-world implementation, you will probably want to 
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */
        $data = $this->example_data;
                
        
        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         * 
         * In a real-world situation involving a database, you would probably want 
         * to handle sorting by passing the 'orderby' and 'order' values directly 
         * to a custom query. The returned data will be pre-sorted, and this array
         * sorting technique would be unnecessary.
         */
        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');
        
        
        /***********************************************************************
         * ---------------------------------------------------------------------
         * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
         * 
         * In a real-world situation, this is where you would place your query.
         * 
         * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
         * ---------------------------------------------------------------------
         **********************************************************************/
        
                
        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently 
         * looking at. We'll need this later, so you should always include it in 
         * your own package classes.
         */
        $current_page = $this->get_pagenum();
        
        /**
         * REQUIRED for pagination. Let's check how many items are in our data array. 
         * In real-world use, this would be the total number of items in your database, 
         * without filtering. We'll need this later, so you should always include it 
         * in your own package classes.
         */
        $total_items = count($data);
        
        
        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to 
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
        
        
        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where 
         * it can be used by the rest of the class.
         */
        $this->items = $data;
        
        
        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }

}