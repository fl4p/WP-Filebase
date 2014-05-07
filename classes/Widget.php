<?php
class WPFB_Widget {
	
static function InitClass() {
	register_widget('WPFB_UploadWidget');
	register_widget('WPFB_AddCategoryWidget');
	register_widget('WPFB_SearchWidget');
	register_widget('WPFB_CatListWidget');
	register_widget('WPFB_FileListWidget');
}

function CatTree(&$root_cat)
{	
	if(!$root_cat->CurUserCanAccess(true)) return;
	echo '<li><a href="'.$root_cat->GetUrl().'">'.esc_html($root_cat->cat_name).'</a>';	
	$childs =& $root_cat->GetChildCats();
	if(count($childs) > 0)
	{
		echo '<ul>';
		foreach(array_keys($childs) as $i) self::CatTree($childs[$i]);
		echo '</ul>';
	}	
	echo '</li>';
}
}

class WPFB_UploadWidget extends WP_Widget {

	function WPFB_UploadWidget() {
		parent::WP_Widget( false, WPFB_PLUGIN_NAME .' '.__('File Upload'), array('description' => __('Allows users to upload files from the front end.',WPFB)) );
	}

	function widget( $args, $instance ) {
		if(!WPFB_Core::$settings->frontend_upload)
			return;
		wpfb_loadclass('File', 'Category', 'Output');
		
		$instance['category'] = empty($instance['category']) ? 0 : (int)$instance['category'];
		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);		
		echo $before_widget;
		echo $before_title . (empty($title) ? __('Upload File',WPFB) : $title) . $after_title;
		
		$prefix = "wpfb-upload-widget-".$this->id_base;
		$form_url = add_query_arg('wpfb_upload_file', 1);
		$form_args = array('cat' => $instance['category'], 'overwrite' => (int)$instance['overwrite']);
		$form_args['file_post_id'] = $instance['attach'] ? WPFB_Core::GetPostId() : 0; // attach file to current post
		WPFB_Output::FileForm($prefix, $form_url, $form_args);
		
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		wpfb_loadclass('Category');
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['category'] = ($new_instance['category'] > 0) ? (is_null($cat=WPFB_Category::GetCat($new_instance['category'])) ? 0 : $cat->GetId()) : (int)$new_instance['category'];
		$instance['overwrite'] = !empty($new_instance['overwrite']);
		$instance['attach'] = !empty($new_instance['attach']);
        return $instance;
	}
	
	function form( $instance ) { wpfb_call('WidgetForms','UploadWidget', array($this,$instance),true); }
}

class WPFB_AddCategoryWidget extends WP_Widget {

	function WPFB_AddCategoryWidget() {
		parent::WP_Widget( false, WPFB_PLUGIN_NAME .' '.__('Add Category',WPFB), array('description' => __('Allows users to create file categories from the front end.',WPFB)) );
	}

	function widget( $args, $instance ) {			
		if(!current_user_can('upload_files'))
			return;

		wpfb_loadclass('File', 'Category', 'Output');
		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);		
		echo $before_widget;
		echo $before_title . (empty($title) ? __('Add File Category',WPFB) : $title) . $after_title;
		
		$prefix = "wpfb-add-cat-widget-".$this->id_base;
		$form_url = add_query_arg('wpfb_add_cat', 1);
		$nonce_action = $prefix;
		?>		
		<form enctype="multipart/form-data" name="<?php echo $prefix ?>form" method="post" action="<?php echo $form_url ?>">
		<?php wp_nonce_field($nonce_action, 'wpfb-cat-nonce'); ?>
		<input type="hidden" name="prefix" value="<?php echo $prefix ?>" />
			<p>
				<label for="<?php echo $prefix ?>cat_name"><?php _e('New category name'/*def*/) ?></label>
				<input name="cat_name" id="<?php echo $prefix ?>cat_name" type="text" value="" />
			</p>
			<p>
				<label for="<?php echo $prefix ?>cat_parent"><?php _e('Parent Category'/*def*/) ?></label>
	  			<select name="cat_parent" id="<?php echo $prefix ?>cat_parent"><?php echo WPFB_Output::CatSelTree(array('check_add_perm'=>true)) ?></select>
	  		</p>
			<p style="text-align:right;"><input type="submit" class="button-primary" name="submit-btn" value="<?php _e('Add New Category'/*def*/) ?>" /></p>
		</form>
	<?php
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		//$instance['overwrite'] = !empty($new_instance['overwrite']);
        return $instance;
	}
	
	function form( $instance ) {
		if(!isset($instance['title'])) $instance['title'] = __('Add File Category',WPFB);
		?><div>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" /></label></p>
		</div><?php
	}
}

class WPFB_SearchWidget extends WP_Widget {

	function WPFB_SearchWidget() {
		parent::WP_Widget( false, WPFB_PLUGIN_NAME .' '.__('Search'), array('description' => __('Widget for searching files.',WPFB)) );
	}

	function widget( $args, $instance ) {
		wpfb_loadclass('File', 'Category', 'Output');
		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);		
		echo $before_widget, $before_title . (empty($title) ? __('Search Files',WPFB) : $title) . $after_title;
		
		$prefix = "wpfb-search-widget-".$this->id_base;
		
		$fbp_id = WPFB_Core::$settings->file_browser_post_id;
		$action = WPFB_Core::GetPostUrl($fbp_id);
		$p_in_query = (strpos($action,'?') !== false); // no permalinks?
		$action = $p_in_query ? remove_query_arg(array('p','post_id','page_id','wpfb_s')) : $action;
		
		echo WPFB_Output::GetSearchForm($action, $p_in_query ? array('p' => $fbp_id) : null, "");

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		//$instance['overwrite'] = !empty($new_instance['overwrite']);
        return $instance;
	}
	
	function form( $instance ) {
		if(!isset($instance['title'])) $instance['title'] = __('Search');
		?><div>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" /></label></p>
		</div><?php
	}
}

class WPFB_CatListWidget extends WP_Widget {

	function WPFB_CatListWidget() {
		parent::WP_Widget( false, WPFB_PLUGIN_NAME .' '.__('Category list', WPFB), array('description' => __('Simple listing of file categories', WPFB)) );
	}

	function widget( $args, $instance ) {
		
		// if no filebrowser this widget doosnt work
		if(WPFB_Core::$settings->file_browser_post_id <= 0)
			return;
		
		
		wpfb_loadclass('Category', 'Output');
		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);		
		echo $before_widget, $before_title . (empty($title) ? __('File Categories',WPFB) : $title) . $after_title;
	
		$tree = !empty($instance['hierarchical']);
	
		// load all categories
		WPFB_Category::GetCats();
	
		$cats = WPFB_Category::GetCats(($tree ? 'WHERE cat_parent = '.(empty($instance['root-cat'])?0:(int)$instance['root-cat']) : '') . ' ORDER BY '.$instance['sort-by'].' '.($instance['sort-asc']?'ASC':'DESC') /* . $options['catlist_order_by'] . ($options['catlist_asc'] ? ' ASC' : ' DESC') /*. ' LIMIT ' . (int)$options['catlist_limit']*/);
	
		echo '<ul>';
		foreach($cats as $cat){
			if($tree)
				WPFB_Widget::CatTree($cat);
			elseif($cat->CurUserCanAccess(true))
				echo '<li><a href="'.$cat->GetUrl().'">'.esc_html($cat->cat_name).'</a></li>';
		}
		echo '</ul>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		wpfb_loadclass('Models');
		
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['hierarchical'] = !empty($new_instance['hierarchical']);
		$instance['sort-by'] = strip_tags($new_instance['sort-by']);
		// TODO root-cat
		if(!in_array($instance['sort-by'], array_keys(WPFB_Models::CatSortFields())))
			$instance['sort-by'] = 'cat_name';
		$instance['sort-asc'] = !empty($new_instance['sort-asc']);
        return $instance;
	}
	
	function form( $instance ) { wpfb_call('WidgetForms','CatListWidget', array($this,$instance),true); }
}

class WPFB_FileListWidget extends WP_Widget {

	function WPFB_FileListWidget() {
		parent::WP_Widget( false, WPFB_PLUGIN_NAME .' '.__('File list', WPFB), array('description' => __('Listing of files with custom sorting', WPFB)) );
	}
	
	static function limitStrLen($str, $maxlen)
	{
		if($maxlen > 3 && strlen($str) > $maxlen) $str = (function_exists('mb_substr') ? mb_substr($str, 0, $maxlen-3,'utf8') : mb_substr($str, 0, $maxlen-3)).'...';
		return $str;
	}

	function widget( $args, $instance ) {
		wpfb_loadclass('File', 'Category', 'Output');
		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);		
		echo $before_widget, $before_title . (empty($title) ? __('Files',WPFB) : $title) . $after_title;
	
		
		// special handling for empty cats
		if(!empty($instance['cat']) && !is_null($cat = WPFB_Category::GetCat($instance['cat'])) && $cat->cat_num_files == 0)
		{
			$instance['cat'] = array();
			foreach($cat->GetChildCats() as $c)
				$instance['cat'][] = $c->cat_id;
		}
		
		$files = WPFB_File::GetFiles2(
			empty($instance['cat']) ? null : WPFB_File::GetSqlCatWhereStr($instance['cat']),
			WPFB_Core::$settings->hide_inaccessible,
			array($instance['sort-by'] => ($instance['sort-asc'] ? 'ASC' : 'DESC')),
		 	(int)$instance['limit']
		);
		
		//$instance['tpl_parsed']
		//WPFB_FileListWidget
		
		$tpl_func = WPFB_Core::CreateTplFunc($instance['tpl_parsed']);
		echo '<ul>';
		foreach($files as $file){
			echo '<li>',($tpl_func($file)),'</li>';
		}
		echo '</ul>';
		echo $after_widget;
	}
	

	function update( $new_instance, $old_instance ) {
		wpfb_loadclass('Models','TplLib', 'Output');
		
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['cat'] = max(0, intval($new_instance['cat']));
		$instance['limit'] = max(1, intval($new_instance['limit']));
		$instance['sort-by'] = strip_tags($new_instance['sort-by']);
		if(!in_array($instance['sort-by'], array_keys(WPFB_Models::FileSortFields())))
			$instance['sort-by'] = 'cat_name';
		$instance['sort-asc'] = !empty($new_instance['sort-asc']);
		$instance['tpl_parsed'] = WPFB_TplLib::Parse($instance['tpl'] = $new_instance['tpl']);
		
        return $instance;
	}
	
	function form( $instance ) { wpfb_call('WidgetForms','FileListWidget', array($this,$instance),true); }
}