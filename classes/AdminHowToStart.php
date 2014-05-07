<?php class WPFB_AdminHowToStart {

static function Display()
{
	?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function(){
	jQuery('div.widgets-holder-wrap').children('.sidebar-name').click(function() {
		jQuery(this).parent().find('.widgets-sortables').slideToggle();
	});
});
//]]>
</script>

<div id="wpfb-how-start">
<div class="widget-liquid-right">
<div class="widgets-holder-wrap closed">
	<div class="sidebar-name">
	<div class="sidebar-name-arrow"><br /></div>
	<h3><?php printf(__('How to get started with %s?', WPFB), WPFB_PLUGIN_NAME); ?></h3></div>
	<div class='widgets-sortables' style="display:none;">
		<ul>
			<li><a href="<?php echo esc_attr(admin_url("admin.php?page=wpfilebase_cats#addcat")); ?>"><?php _e('Create a Category',WPFB) ?></a></li>
			
			<li><?php _e('Add a file. There are different ways:', WPFB); ?>
				<ul>
					<li><?php printf(__('<a href="%s">Use the normal File Upload Form.</a> You you can either upload a file from you local harddisk or you can provide a URL to a file that will be sideloaded to your blog.', WPFB), esc_attr(admin_url("admin.php?page=wpfilebase_files#addfile"))); ?></li>
					<li><?php printf(__('Use FTP: Use your favorite FTP Client to upload any directories/files to <code>%s</code>. Afterwards <a href="%s">sync the filebase</a> to add the newly uploaded files to the database.', WPFB), esc_html(WPFB_Core::$settings->upload_path), esc_attr(admin_url('admin.php?page=wpfilebase_manage&action=sync'))); ?></li>
				</ul>
			</li>
			<li><?php printf(__('Goto <a href="%s">WP-Filebase Settings -> Filebrowser</a> and set the Page ID to get a nice AJAX Tree View of all your files.', WPFB), esc_attr(admin_url('admin.php?page=wpfilebase_sets#'.sanitize_title(__('File Browser',WPFB))))); ?></li>
			<li><?php printf(__('WP-Filebase adds a new button to the visual editor. When creating or editing posts/pages, use the Editor Plugin %s to insert single files, file lists and other stuff into your content.', WPFB), '<img src="'.esc_attr(WPFB_PLUGIN_URI).'tinymce/images/btn.gif" style="vertical-align:middle;" />'); ?></li>
			<li><?php printf(__('Take a look at the <a href="%s">Widgets</a>. WP-Filebase adds three widgets for file listing, category listing and user uploads.', WPFB), admin_url('widgets.php')); ?></li>
			<li><?php printf(__('<a href="%s">Manage the Templates</a> (for advanced users): You can modify any file- or category template to fit your Wordpress theme.', WPFB), esc_attr(admin_url('admin.php?page=wpfilebase_tpls'))); ?></li>
		</ul>
		<?php if(!get_user_option(WPFB_OPT_NAME . '_hide_how_start')) {?>
		<p style="text-align: right"><a href="<?php echo esc_attr(add_query_arg('wpfb-hide-how-start', '1')) ?>"><?php _e('Never show this again.',WPFB) ?></a></p>
		<?php } ?>
	</div>
</div>
</div>
</div>
<?php 
}

}