<?php
class WPFB_AdminGuiFiles {
static $FilesPerPage = 50;

static function Display()
{
	global $wpdb, $user_ID;

	wpfb_loadclass('File', 'Category', 'Admin', 'Output');
	
	$_POST = stripslashes_deep($_POST);
	$_GET = stripslashes_deep($_GET);
	
	$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';
	$clean_uri = remove_query_arg(array('message', 'action', 'file_id', 'cat_id', 'deltpl', 'hash_sync' /* , 's'*/)); // keep search keyword
	
	// nonce/referer check (security)
	if($action == 'updatefile' || $action == 'addfile') {
		$nonce_action = WPFB."-".$action;
		if($action == 'updatefile') $nonce_action .= $_POST['file_id'];
		if(!wp_verify_nonce($_POST['wpfb-file-nonce'],$nonce_action) || !check_admin_referer($nonce_action,'wpfb-file-nonce'))
			wp_die(__('Cheatin&#8217; uh?'));		
	}
	
	// switch simple/extended form
	if(isset($_GET['exform'])) {
		$exform = (!empty($_GET['exform']) && $_GET['exform'] == 1);
		update_user_option($user_ID, WPFB_OPT_NAME . '_exform', $exform); 
	} else
		$exform = (bool)get_user_option(WPFB_OPT_NAME . '_exform');
	
	if(!empty($_REQUEST['redirect']) && !empty($_REQUEST['redirect_to'])) WPFB_AdminLite::JsRedirect($_REQUEST['redirect_to']);
	
	?>
	<div class="wrap">
	<?php

	switch($action)
	{		
		case 'editfile':
			if(!current_user_can('upload_files')) wp_die(__('Cheatin&#8217; uh?'));
			
			if(!empty($_POST['files'])) {
				if(!is_array($_POST['files'])) $_POST['files'] = explode(',',$_POST['files']);
				$files = array();
				foreach($_POST['files'] as $file_id) {
					$file = WPFB_File::GetFile($file_id);
					if(!is_null($file) && $file->CurUserCanEdit()) $files[] = $file;
				}
				if(count($files) > 0)
					WPFB_Admin::PrintForm('file', $files, array('multi_edit' => true));
				else 
					wp_die('No files to edit.');
			} else {
				$file = WPFB_File::GetFile($_GET['file_id']);
				if(is_null($file) || !$file->CurUserCanEdit())
					wp_die(__('You do not have the permission to edit this file!',WPFB));
				WPFB_Admin::PrintForm('file', $file);
			}
			break;

		case 'updatefile':
			$file_id = (int)$_POST['file_id'];
			$update = true;
			$file = WPFB_File::GetFile($file_id);
			if(is_null($file) || !$file->CurUserCanEdit())
				wp_die(__('Cheatin&#8217; uh?'));
			
		case 'addfile':
			$update = !empty($update);
		
			if ( !WPFB_Admin::CurUserCanUpload() )
				wp_die(__('Cheatin&#8217; uh?'));
			
			extract($_POST);
			if(isset($jj) && isset($ss))
			{
				$jj = ($jj > 31 ) ? 31 : $jj;
				$hh = ($hh > 23 ) ? $hh -24 : $hh;
				$mn = ($mn > 59 ) ? $mn -60 : $mn;
				$ss = ($ss > 59 ) ? $ss -60 : $ss;
				$_POST['file_date'] =  sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss );
			}
			
			$result = WPFB_Admin::InsertFile(array_merge($_POST, $_FILES), true);
			if(isset($result['error']) && $result['error']) {
				$message = $result['error'] . '<br /><a href="javascript:history.back()">' . __("Go back") . '</a>';
			} else {
				$message = $update?__('File updated.', WPFB):__('File added.', WPFB);
			}

		default:
			if(!current_user_can('upload_files'))
				wp_die(__('Cheatin&#8217; uh?'));
				
			
			if(!empty($_POST['deleteit'])) {
				foreach ( (array)$_POST['delete'] as $file_id ) {					
					if(is_object($file = WPFB_File::GetFile($file_id))  && $file->CurUserCanEdit())
						$file->Remove(true);
				}
				WPFB_File::UpdateTags();
			}
?>
	<h2><?php
	echo str_replace(array('(<','>)'),array('<','>'), sprintf(__('Manage Files (<a href="%s">add new</a>)', WPFB), '#addfile" class="add-new-h2'));
	echo '<a href="'.admin_url('admin.php?page=wpfilebase_manage&amp;action=batch-upload').'" class="add-new-h2">'.__('Batch Upload',WPFB).'</a>';
	
	if ( isset($_GET['s']) && $_GET['s'] )
		printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;'/*def*/) . '</span>', esc_html(stripslashes($_GET['s'])));
	?></h2>
	<?php if ( !empty($message) ) : ?><div id="message" class="updated fade"><p><?php echo $message; ?></p></div><?php endif; 
	if(WPFB_Admin::CurUserCanUpload() && ($action == 'addfile' || $action == 'updatefile'))
	{
		unset($file);
		WPFB_Admin::PrintForm('file', null, array('exform' => $exform, 'item' => new WPFB_File((isset($result['error']) && $result['error']) ? $_POST : null)));
	}
	wpfb_loadclass('FileListTable');
$file_table = new WPFB_FileListTable();
$file_table->prepare_items();

?>
	
<form class="search-form topmargin" action="" method="get">
	<input type="hidden" value="<?php echo esc_attr($_GET['page']); ?>" name="page" />
	<input type="hidden" value="<?php echo empty($_GET['view']) ? '' : esc_attr(@$_GET['view']); ?>" name="view" />
<?php $file_table->search_box( __("Search Files",WPFB), 's' ); ?>
</form>	
 
<?php $file_table->views(); ?>
 <form id="posts-filter" action="" method="post">
 <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
 <?php $file_table->display() ?>
 </form>
 <br class="clear" />

<?php

	if($action != 'addfile' && $action != 'updatefile' && WPFB_Admin::CurUserCanUpload())
	{
		unset($file);
		WPFB_Admin::PrintForm('file', null, array('exform' => $exform));
	}	
	break; // default
	}

	
	
	/*
	
	$file_list_table = new WPFB_File_List_Table();
	$pagenum = $file_list_table->get_pagenum();
	$doaction = $file_list_table->current_action();
	
	$file_list_table->prepare_items();
	
	$file_list_table->views();
	$file_list_table->search_box( "asdf", 'post' );
	
	$file_list_table->display();
	
	*/
	?>
	
	
	
	
</div> <!-- wrap -->
<?php
}

static function PrintFileInfo($info, $path='file_info')
{
	foreach($info as $key => $val)
	{
		$p = $path.'/'.$key;
		if(is_array($val) && count($val) == 1 && isset($val[0])) // if its a single array, just take the first element
			$val = $val[0];
		echo '<b>',esc_html($p),"</b> = ",esc_html($val),"\n";
		if(is_array($val) || is_object($val))
		{			
			self::PrintFileInfo($val, $p);
		}
	}
}

static function FileInfoPathsBox($info)
{
	?><p><?php printf(__('The following tags can be used in templates. For example, if you want to display the Artist of a MP3 File, put %s inside the template code.', WPFB), '<code>%file_info/tags/id3v2/artist%</code>'); ?></p>
	<pre>
	<?php self::PrintFileInfo(empty($info->value) ? $info : $info->value); ?>
	</pre>	
	<?php
	if(!empty($info->keywords)) {
		?><p><b><?php _e('Keywords used for search:',WPFB) ?></b> <?php echo esc_html($info->keywords) ?></p> <?php
	}
}
}

/*
class WPFB_File_List_Table extends WP_List_Table {
	static $FilesPerPage = 50;
	
	function __construct() {
		$this->detached = isset( $_REQUEST['detached'] ) || isset( $_REQUEST['find_detached'] );
		$this->offline = isset( $_REQUEST['offline'] ) || isset( $_REQUEST['find_offline'] );
		
		$this->file_category = empty($_GET['file_category']) ? 0 : intval($_GET['file_category']);

		parent::__construct( array(
			'plural' => 'media'
		) );
	}

	function ajax_user_can() {
		return current_user_can('upload_files');
	}

	function prepare_items() {
		
		$where = WPFB_Search::SearchWhereSql(true);
		if($this->file_category > 0) 
			$where = (empty($where) ? '' : ("($where) AND ")) . "file_category = $this->file_category";
		
		$this->where = $where;
 		
		$per_page = self::$FilesPerPage;
		$this->num_total_files = $num_total_files = WPFB_File::GetNumFiles2($where, false);		
		$total_pages = ceil( $num_total_files / $per_page );

		$this->set_pagination_args( array(
			'total_items' => $num_total_files,
			'total_pages' => $total_pages,
			'per_page' => $per_page,
		) );
	}

	function get_views() {
		global $wpdb, $post_mime_types, $avail_post_mime_types;

		$type_links = array();
/*		$_num_posts = (array) wp_count_attachments();
		$_total_posts = array_sum($_num_posts) - $_num_posts['trash'];
		if ( !isset( $total_orphans ) )
				$total_orphans = $wpdb->get_var( "SELECT COUNT( * ) FROM $wpdb->posts WHERE post_type = 'attachment' AND post_status != 'trash' AND post_parent < 1" );
		$matches = wp_match_mime_types(array_keys($post_mime_types), array_keys($_num_posts));
		foreach ( $matches as $type => $reals )
			foreach ( $reals as $real )
				$num_posts[$type] = ( isset( $num_posts[$type] ) ) ? $num_posts[$type] + $_num_posts[$real] : $_num_posts[$real];

		$class = ( empty($_GET['post_mime_type']) && !$this->detached && !isset($_GET['status']) ) ? ' class="current"' : '';
		$type_links['all'] = "<a href='upload.php'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $_total_posts, 'uploaded files' ), number_format_i18n( $_total_posts ) ) . '</a>';
		foreach ( $post_mime_types as $mime_type => $label ) {
			$class = '';

			if ( !wp_match_mime_types($mime_type, $avail_post_mime_types) )
				continue;

			if ( !empty($_GET['post_mime_type']) && wp_match_mime_types($mime_type, $_GET['post_mime_type']) )
				$class = ' class="current"';
			if ( !empty( $num_posts[$mime_type] ) )
				$type_links[$mime_type] = "<a href='upload.php?post_mime_type=$mime_type'$class>" . sprintf( translate_nooped_plural( $label[2], $num_posts[$mime_type] ), number_format_i18n( $num_posts[$mime_type] )) . '</a>';
		}
		$type_links['detached'] = '<a href="upload.php?detached=1"' . ( $this->detached ? ' class="current"' : '' ) . '>' . sprintf( _nx( 'Unattached <span class="count">(%s)</span>', 'Unattached <span class="count">(%s)</span>', $total_orphans, 'detached files' ), number_format_i18n( $total_orphans ) ) . '</a>';

		if ( !empty($_num_posts['trash']) )
			$type_links['trash'] = '<a href="upload.php?status=trash"' . ( (isset($_GET['status']) && $_GET['status'] == 'trash' ) ? ' class="current"' : '') . '>' . sprintf( _nx( 'Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>', $_num_posts['trash'], 'uploaded files' ), number_format_i18n( $_num_posts['trash'] ) ) . '</a>';
* /
		
		$type_links['remote'] = 'remote';
		$type_links['offline'] = 'off';
		$type_links['detached'] = 'det';
		$type_links['public'] = 'pub';
		
		
		return $type_links;
	}

	function get_bulk_actions() {
		$actions = array();
		$actions['delete'] = __( 'Delete Permanently' );
		$actions['edit'] = __( 'Edit' );
		if ( $this->detached )
			$actions['attach'] = __( 'Attach to a post' );

		return $actions;
	}

	function extra_tablenav( $which ) {
?>
		<div class="alignleft actions">
<?php
		if ( $this->offline )
			submit_button( __( 'Set online' ), 'secondary', 'set_online', false );
		?>
		</div>
<?php
	}

	function current_action() {
		if ( isset( $_REQUEST['find_detached'] ) )
			return 'find_detached';

		if ( isset( $_REQUEST['found_post_id'] ) && isset( $_REQUEST['media'] ) )
			return 'attach';

		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) )
			return 'delete_all';

		return parent::current_action();
	}

	function has_items() {
		if(!isset($this->num_total_files))
			$this->prepare_items();
		return ($this->num_total_files > 0);
	}

	function no_items() {
		_e( 'No media attachments found.' );
	}

	function get_columns() {
		$posts_columns = array();
		
		$posts_columns['cb'] = '<input type="checkbox" />';
		$posts_columns['id'] = 'ID';
		$posts_columns['title'] = __('Name');
		$posts_columns['file'] = __('Filename', WPFB);
		$posts_columns['size'] = __('Size'/*def* /);
		$posts_columns['desc'] = __('Description'/*def* /);		
		$posts_columns['cat'] = __('Category'/*def* /);
		$posts_columns['perm'] = __('Access Permission',WPFB);
		$posts_columns['uploader'] = __('Owner',WPFB);
		$posts_columns['hits'] = __('Hits', WPFB);
		$posts_columns['lastdl'] = __('Last download', WPFB);
		
		return $posts_columns;
	}

	function get_sortable_columns() {				
		return array(
			//'id'		=> 'file_id',
			'title'    	=> 'file_display_name',
			//'file'   	=> 'file_name',
			'size'   	=> 'file_size',
			//'desc'   	=> 'file_description',
			'cat'   	=> 'file_category_name',
			'perm'   	=> 'file_user_roles',
			//'uploader'  => 'file_added_by',
			//'hits'   	=> 'file_hits',
			'lastdl'   	=> 'file_last_dl_time'
		);
	}

	function display_rows() {
		global $wpdb;
		wpfb_loadclass('Search');
		
		list( $columns, $hidden, $sortable ) = $this->get_column_info();
		$columns = array_merge($columns, $sortable);

		
		$where = $this->where;
		$per_page = self::$FilesPerPage;			
		$pagestart = ($this->get_pagenum() - 1 + 1) * $per_page; // TODO
		$order = "$wpdb->wpfilebase_files." . ((!empty($_GET['order']) && in_array($_GET['order'], array_keys(get_class_vars('WPFB_File')))) ?
			($_GET['order']." ".(!empty($_GET['desc']) ? "DESC" : "ASC")) : "file_id DESC");


		$files = WPFB_File::GetFiles2($where, true, $order, $per_page, $pagestart);
		if(empty($files) && !empty($wpdb->last_error))
			wp_die("<b>Database error</b>: ".$wpdb->last_error);
		foreach($files as $file_id => $file)
		{
?>
	<tr id='file-<?php echo $file_id ?>'<?php if($file->file_offline) { echo " class='offline'"; } ?>>
<?php


foreach ( $columns as $column_name => $column_display_name ) {
	$class = "class='$column_name column-$column_name'";

	$style = '';
	if ( in_array( $column_name, $hidden ) )
		$style = ' style="display:none;"';

	$attributes = $class . $style;

	switch ( $column_name ) {
	case 'cb': ?> <th scope="row" class="check-column"><input type='checkbox' name='files[]' value='<?php echo $file_id ?>' /></th> <?php break;
	case 'id':  echo "<td class='num'>$file_id</td>"; break;	

	case 'title': ?>
	<td class="wpfilebase-admin-list-row-title">
		<a class="row-title" href="<?php echo $file->GetEditUrl() ?>" title="<?php printf(__("Edit &#8220;%s&#8221;"), esc_attr($file->file_display_name)); ?>">
			<?php if(!empty($file->file_thumbnail)) { ?><img src="<?php echo $file->GetIconUrl(); ?>" height="32" /><?php } ?>
			<span><?php if($file->IsRemote()){echo '*';} echo esc_html($file->file_display_name); ?></span>
		</a>
	</td>
	<?php
		break;
		
	case 'file': ?> <td><a href="<?php echo $file->GetUrl() ?>"><?php echo esc_html($file->file_name); ?></a></td> <?php break;
	
	case 'size': ?> <td><?php echo WPFB_Output::FormatFilesize($file->file_size); ?></td> <?php break;
	
	case 'desc': ?> <td><?php echo empty($file->file_description) ? '-' : esc_html($file->file_description); ?></td> <?php break;
	case 'cat': ?> <td><?php echo (!is_null($cat)) ? ('<a href="'.$cat->GetEditUrl().'">'.esc_html($file->file_category_name).'</a>') : '-'; ?></td> <?php break;
	case 'perm': ?> <td><?php echo WPFB_Output::RoleNames($user_roles,true) ?></td> <?php break;
	case 'uploader': ?> <td><?php echo (empty($file->file_added_by) || !($usr = get_userdata($file->file_added_by))) ? '-' : esc_html($usr->user_login) ?></td> <?php break;
	case 'hits': ?> <td class='num'><?php echo $file->file_hits; ?></td> <?php break;
	case 'lastdl': ?> <td><?php echo ( (!empty($file->file_last_dl_time) && $file->file_last_dl_time > 0) ? mysql2date(get_option('date_format'), $file->file_last_dl_time) : '-') ?></td> <?php break;
	//<!-- TODO <td class='num'><?php echo $rating </td> -->


	default:
?>
		<td <?php echo $attributes ?>>
			<?php do_action( 'manage_media_custom_column', $column_name, $id ); ?>
		</td>
<?php
		break;
	}
}
?>
	</tr>
<?php } // foreach file
	}
}
*/

