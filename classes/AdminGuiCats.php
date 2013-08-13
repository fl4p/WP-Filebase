<?php
class WPFB_AdminGuiCats {
	
static function CatRow($cat, $sub_level=0)
{
	$cat_id = $cat->cat_id;
	$parent_cat = $cat->GetParent();
	$user_roles = $cat->GetReadPermissions();
	$title = esc_attr($cat->cat_name);
	if($sub_level > 0) $title = str_repeat('-', $sub_level) . " $title";
	
	?>
			<tr id="cat-<?php echo $cat_id; ?>">
				<th scope="row" class="check-column"><input type="checkbox" name="delete[]" value="<?php echo $cat_id; ?>" /><div style="font-size:11px; text-align:center;"><?php echo $cat_id; ?></div></th>
				<td class="wpfilebase-admin-list-row-title"><a class="row-title" href="<?php echo esc_attr($cat->GetEditUrl()); ?>" title="&quot;<?php echo $title; ?>&quot; bearbeiten">
				<?php if(!empty($cat->cat_icon)) { ?><img src="<?php echo $cat->GetIconUrl(); ?>" height="32" /><?php } ?>
				<span><?php echo $title; ?></span>
				</a></td>
				<td><?php echo esc_html($cat->cat_description) ?></td>
				<td class="num"><?php echo "<a href='".admin_url("admin.php?page=wpfilebase_files&file_category=".$cat->GetId())."'>$cat->cat_num_files</a> / $cat->cat_num_files_total" ?></td>
				<td><?php echo $parent_cat?('<a href="'.$parent_cat->GetEditUrl().'">'.esc_html($parent_cat->cat_name).'</a>'):'-' ?></td>
				<td><code><?php echo esc_html($cat->cat_path) ?></code></td>
				<td><?php echo WPFB_Output::RoleNames($user_roles,true) ?></td>

				<td><?php echo ($cat->GetOwnerId() <= 0 || !($usr = get_userdata($cat->GetOwnerId()))) ? '-' : esc_html($usr->user_login) ?></td>
				<td class="num"><?php echo $cat->cat_order ?></td>
			</tr>
	<?php
}

static function Display()
{
	global $wpdb, $user_ID;
	
	if ( !WPFB_Admin::CurUserCanCreateCat() )
		wp_die(__('Cheatin&#8217; uh?'));
	
	wpfb_loadclass('Category', 'File', 'Admin', 'Output');
	
	$_POST = stripslashes_deep($_POST);
	$_GET = stripslashes_deep($_GET);
	
	$action = (!empty($_POST['action']) ? $_POST['action'] : (!empty($_GET['action']) ? $_GET['action'] : ''));
	$clean_uri = remove_query_arg(array('message', 'action', 'file_id', 'cat_id', 'deltpl', 'hash_sync' /* , 's'*/)); // keep search keyword
	
	// switch simple/extended form
	if(isset($_GET['exform'])) {
		$exform = (!empty($_GET['exform']) && $_GET['exform'] == 1);
		update_user_option($user_ID, WPFB_OPT_NAME . '_exform', $exform); 
	} else {
		$exform = (bool)get_user_option(WPFB_OPT_NAME . '_exform');
	}
	
	?>
	<div class="wrap">
	<?php
	
	switch($action)
	{
		case 'editcat':				
			$cat_id = (int)$_GET['cat_id'];
			$file_category = WPFB_Category::GetCat($cat_id);
			if(is_null($file_category) ||  !$file_category->CurUserCanEdit())
				wp_die(__('Cheatin&#8217; uh?'));
			WPFB_Admin::PrintForm('cat', $file_category);
			break;
			
		case 'updatecat':
			$cat_id = (int)$_POST['cat_id'];
			$update = true;
			$file_category = WPFB_Category::GetCat($cat_id);
			if(is_null($file_category) || !$file_category->CurUserCanEdit())
				wp_die(__('Cheatin&#8217; uh?'));
			
		case 'addcat':
			$update = !empty($update);
			
			$result = WPFB_Admin::InsertCategory(array_merge(stripslashes_deep($_POST), $_FILES));
			if(isset($result['error']) && $result['error']) {
				$message = $result['error'];
			} else {
				$message = $update?__('Category updated.'):__('Category added.');/*def*/
			}
			
			//wp_redirect($clean_uri . '&action=manage_cats&message=' . urlencode($message));
		
		default:				
			if(!empty($_POST['deleteit']))
			{
				foreach ( (array) $_POST['delete'] as $cat_id ) {
					if(is_object($cat = WPFB_Category::GetCat($cat_id)) && $cat->CurUserCanEdit())
						$cat->Delete();
				}
			}
?>
	<h2><?php
	echo str_replace(array('(<','>)'),array('<','>'), sprintf(__('Manage Categories (<a href="%s">add new</a>)', WPFB), '#addcat" class="add-new-h2'));
	if ( isset($_GET['s']) && $_GET['s'] )
		printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;'/*def*/) . '</span>', esc_html(stripslashes($_GET['s'])));
	?></h2>

	<?php if ( !empty($message) ) : ?><div id="message" class="updated fade"><p><?php echo $message; ?></p></div><?php endif; ?> 

	<form class="search-form topmargin" action="" method="get"><p class="search-box">
		<input type="hidden" value="<?php echo esc_attr($_GET['page']); ?>" name="page" />
		<label class="hidden" for="category-search-input"><?php _e('Search Categories'/*def*/); ?>:</label>
		<input type="text" class="search-input" id="category-search-input" name="s" value="<?php echo(isset($_GET['s']) ? esc_attr($_GET['s']) : ''); ?>" />
		<input type="submit" value="<?php _e( 'Search Categories'/*def*/); ?>" class="button" />
	</p></form>	
	
	<br class="clear" />
	
	<form id="posts-filter" action="" method="post">
		<div class="tablenav">
			<?php
			$pagenum = max(isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 0, 1);
			if(!isset($catsperpage) || $catsperpage < 0)
				$catsperpage = 20;
				
			$pagestart = ($pagenum - 1) * $catsperpage;

			$extra_sql = '';
			if(!empty($_GET['s'])) {
				$s = esc_sql(trim($_GET['s']));
				$extra_sql .= "WHERE cat_name LIKE '%$s%' OR cat_description LIKE '%$s%' OR cat_folder LIKE '%$s%' ";
			}
			
			if(!empty($_GET['order']) && in_array($_GET['order'], array_keys(get_class_vars('WPFB_Category'))))
				$extra_sql .= "ORDER BY " . $_GET['order'] . " " . (!empty($_GET['desc']) ? "DESC" : "ASC");		

			$cats = WPFB_Category::GetCats($extra_sql . " LIMIT $pagestart, $catsperpage");

			$page_links = paginate_links(array(
				'base' => add_query_arg( 'pagenum', '%#%' ),
				'format' => '',
				'total' => ceil(count(WPFB_Category::GetCats($extra_sql)) / $catsperpage),
				'current' => $pagenum
			));

			if ( $page_links )
				echo "<div class='tablenav-pages'>$page_links</div>";
			?>

			<div class="alignleft"><input type="submit" value="<?php _e('Delete'); ?>" name="deleteit" class="button delete" /><?php wp_nonce_field('bulk-categories'); ?></div>
		</div>
	
		<br class="clear" />

		<table class="widefat">
			<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" /></th>
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('cat_name') ?>"><?php _e('Name'/*def*/) ?></a></th>
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('cat_description') ?>"><?php _e('Description'/*def*/) ?></a></th>
				<th scope="col" class="num"><a href="<?php echo WPFB_Admin::AdminTableSortLink('cat_num_files') ?>"><?php _e('Files', WPFB) ?></a></th>
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('cat_parent') ?>"><?php _e('Parent Category'/*def*/) ?></a></th>
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('cat_path') ?>"><?php _e('Path'/*def*/) ?></a></th>
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('cat_user_roles') ?>"><?php _e('Access Permission',WPFB) ?></a></th>

				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('cat_owner') ?>"><?php _e('Owner',WPFB) ?></a></th>
				<th scope="col"><a href="<?php echo WPFB_Admin::AdminTableSortLink('cat_order') ?>"><?php _e('Custom Sort Order',WPFB) ?></a></th>
			</tr>
			</thead>
			<tbody id="the-list" class="list:cat">
			
			<?php
			foreach($cats as $cat_id => &$cat)
			{
				if($cat->CurUserCanEdit())
					self::CatRow($cat);			
			}
			
			?>
			
			</tbody>
		</table>
		<div class="tablenav"><?php if ( $page_links ) { echo "<div class='tablenav-pages'>$page_links</div>"; } ?></div>
	</form>
	<br class="clear" />
	
	<?php if ( WPFB_Admin::CurUserCanCreateCat() ) : ?>
		<p><?php _e('<strong>Note:</strong><br />Deleting a category does not delete the files in that category. Instead, files that were assigned to the deleted category are set to the parent category.', WPFB) ?></p><?php
		WPFB_Admin::PrintForm('cat');
		endif;

	break;
	}	
	?>
</div> <!-- wrap -->
<?php
}
}