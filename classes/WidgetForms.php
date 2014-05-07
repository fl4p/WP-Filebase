<?php class WPFB_WidgetForms {
	public static function UploadWidget($obj, $instance ) {
		if(!WPFB_Core::$settings->frontend_upload) {
			_e('Frontend upload is disabled in security settings!', WPFB);
			return;
		}
		wpfb_loadclass('File', 'Category', 'Output');
		if(!isset($instance['title'])) $instance['title'] = __('Upload File',WPFB);
		?><div>
			<p><label for="<?php echo $obj->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input type="text" id="<?php echo $obj->get_field_id('title'); ?>" name="<?php echo $obj->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" /></label></p>
			<p><label for="<?php echo $obj->get_field_id('category'); ?>"><?php _e('Category:'); ?>
				<select id="<?php echo $obj->get_field_id('category'); ?>" name="<?php echo $obj->get_field_name('category'); ?>">
					<option value="-1"  style="font-style:italic;"><?php _e('Selectable by Uploader',WPFB); ?></option>
					<?php echo WPFB_Output::CatSelTree(array('none_label' => __('Upload to Root',WPFB), 'selected'=> empty($instance['category']) ? 0 : $instance['category'])); ?>
				</select>
			</label></p>
			<p><input type="checkbox" id="<?php echo $obj->get_field_id('overwrite'); ?>" name="<?php echo $obj->get_field_name('overwrite'); ?>" value="1" <?php checked(!empty($instance['overwrite'])) ?> /> <label for="<?php echo $obj->get_field_id('overwrite'); ?>"><?php _e('Overwrite existing files', WPFB) ?></label></p>
			<p><input type="checkbox" id="<?php echo $obj->get_field_id('attach'); ?>" name="<?php echo $obj->get_field_name('attach'); ?>" value="1" <?php checked(!empty($instance['attach'])) ?> /> <label for="<?php echo $obj->get_field_id('attach'); ?>"><?php _e('Attach file to current post/page', WPFB) ?></label></p>
		</div><?php
	}	
	
	public static function CatListWidget( $obj, $instance ) {
		if(WPFB_Core::$settings->file_browser_post_id <= 0) {
			echo '<div>';
			_e('Before you can use this widget, please set a Post ID for the file browser in WP-Filebase settings.', WPFB);
			echo '<br /><a href="'.admin_url('admin.php?page=wpfilebase_sets#file-browser').'">';
			_e('Goto File Browser Settings');
			echo '</a></div>';
			return;
		}
	
		if(!isset($instance['title'])) $instance['title'] = __('File Categories');
		$instance['hierarchical'] = !empty($instance['hierarchical']);
		if(!isset($instance['sort-by'])) $instance['sort-by'] = 'cat_name';
		$instance['sort-asc'] = !empty($instance['sort-asc']);
		
		wpfb_loadclass('Models');
	?>
	<div>
		<p><label for="<?php echo $obj->get_field_id('title'); ?>"><?php _e('Title:'); ?>
			<input type="text" id="<?php echo $obj->get_field_id('title'); ?>" name="<?php echo $obj->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" /></label>
		</p>
		
		<p><input type="checkbox" id="<?php echo $obj->get_field_id('hierarchical'); ?>" name="<?php echo $obj->get_field_name('hierarchical'); ?>" value="1" <?php checked($instance['hierarchical']); ?> />
		<label for="<?php echo $obj->get_field_id('hierarchical'); ?>"><?php _e( 'Show hierarchy' ); ?></label>
		</p>
		
		<p>
			<label for="<?php echo $obj->get_field_id('sort-by'); ?>"><?php _e('Sort by:'/*def*/); ?></label>
			<select id="<?php echo $obj->get_field_id('sort-by'); ?>" name="<?php echo $obj->get_field_name('sort-by'); ?>">
			<?php
				$sort_vars = WPFB_Models::CatSortFields();
				foreach($sort_vars as $tag => $name)
				{
					echo '<option value="' . esc_attr($tag) . '" title="' . esc_attr($name) . '"' . ( ($instance['sort-by'] == $tag) ? ' selected="selected"' : '' ) . '>' .$tag.'</option>';
				}
			?>
			</select><br />
			<label for="<?php echo $obj->get_field_id('sort-asc0'); ?>"><input type="radio" name="<?php echo $obj->get_field_name('sort-asc'); ?>" id="<?php echo $obj->get_field_id('sort-asc0'); ?>" value="0"<?php checked($instance['sort-asc'], false) ?>/><?php _e('Descending'); ?></label>
			<label for="<?php echo $obj->get_field_id('sort-asc1'); ?>"><input type="radio" name="<?php echo $obj->get_field_name('sort-asc'); ?>" id="<?php echo $obj->get_field_id('sort-asc1'); ?>" value="1"<?php checked($instance['sort-asc'], true) ?>/><?php _e('Ascending'); ?></label>
		</p>
		<!--
		<p><label for="wpfilebase-catlist-limit"><?php _e('Limit:', WPFB); ?>
			<input type="text" id="wpfilebase-catlist-limit" name="wpfilebase-catlist-limit" size="4" maxlength="3" value="<?php echo $options['catlist_limit']; ?>" />
		</label></p> -->
	</div>
	<?php
	}
	
	public static function FileListWidget( $obj, $instance ) {
		
		$defaults = array(
			'title' => 'Top Downloads',
			'sort-by' => 'file_hits',
			'sort-asc' => false,
			'limit' => 10,
			'tpl' => '<a href="%file_post_url%">%file_display_name%</a> (%file_hits%)'
		);
		
		foreach($defaults as $prop => $val)
			if(!isset($instance[$prop])) $instance[$prop] = $val;
		
		wpfb_loadclass('Admin','Models','Output');
	?>
	<div>
		<p><label for="<?php echo $obj->get_field_id('title'); ?>"><?php _e('Title:'); ?>
			<input type="text" id="<?php echo $obj->get_field_id('title'); ?>" name="<?php echo $obj->get_field_name('title'); ?>" value="<?php echo esc_attr($instance['title']); ?>" /></label>
		</p>
		
		<p><label for="<?php echo $obj->get_field_id('cat'); ?>"><?php _e('Category:', WPFB); ?>
			<select name="<?php echo $obj->get_field_name('cat'); ?>" id="<?php echo $obj->get_field_id('cat'); ?>">
			<?php echo WPFB_Output::CatSelTree(array('selected'=>empty($instance['cat']) ? 0 : $instance['cat'], 'none_label'=>__('All'))) ?>
			</select></label>
		</p>
		<!-- 
		<p><input type="checkbox" id="<?php echo $obj->get_field_id('hierarchical'); ?>" name="<?php echo $obj->get_field_name('hierarchical'); ?>" value="1" <?php checked($instance['hierarchical']); ?> />
		<label for="<?php echo $obj->get_field_id('hierarchical'); ?>"><?php _e( 'Show hierarchy' ); ?></label>
		</p>
		 -->
		
		<p>
			<label for="<?php echo $obj->get_field_id('sort-by'); ?>"><?php _e('Sort by:'/*def*/); ?></label>
			<select id="<?php echo $obj->get_field_id('sort-by'); ?>" name="<?php echo $obj->get_field_name('sort-by'); ?>">
			<?php
				$sort_vars = WPFB_Models::FileSortFields();
				foreach($sort_vars as $tag => $name)
				{
					echo '<option value="' . esc_attr($tag) . '" title="' . esc_attr($name) . '"' . ( ($instance['sort-by'] == $tag) ? ' selected="selected"' : '' ) . '>' .$tag.'</option>';
				}
			?>
			</select><br />
			<label for="<?php echo $obj->get_field_id('sort-asc0'); ?>"><input type="radio" name="<?php echo $obj->get_field_name('sort-asc'); ?>" id="<?php echo $obj->get_field_id('sort-asc0'); ?>" value="0"<?php checked($instance['sort-asc'], false) ?>/><?php _e('Descending'); ?></label>
			<label for="<?php echo $obj->get_field_id('sort-asc1'); ?>"><input type="radio" name="<?php echo $obj->get_field_name('sort-asc'); ?>" id="<?php echo $obj->get_field_id('sort-asc1'); ?>" value="1"<?php checked($instance['sort-asc'], true) ?>/><?php _e('Ascending'); ?></label>
		</p>
		
		<p><label for="<?php echo $obj->get_field_id('limit'); ?>"><?php _e('Limit:', WPFB); ?>
			<input type="text" id="<?php echo $obj->get_field_id('limit'); ?>" name="<?php echo $obj->get_field_name('limit'); ?>" value="<?php echo intval($instance['limit']); ?>" size="4" maxlength="3" /></label>
		</p>
		
		<p><label for="<?php echo $obj->get_field_id('tpl'); ?>"><?php _e('Template:', WPFB); ?>
			<input class="widefat" type="text" id="<?php echo $obj->get_field_id('id'); ?>" name="<?php echo $obj->get_field_name('tpl'); ?>" value="<?php echo esc_attr($instance['tpl']); ?>" /></label>
			<br /><?php	echo WPFB_Models::TplFieldsSelect($obj->get_field_id('id'), true); ?>
		</p>
	</div>
	<?php
	}
}
