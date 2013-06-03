<?php

class WPFB_SWFUpload extends WPFB_AdvUploader
{
	function Scripts($prefix)
	{
		$id = $this->id;
		
		wp_print_scripts('swfupload-all');
		wp_print_scripts('swfupload-handlers');
		?>
		
<script type="text/javascript">
//<![CDATA[

function uploadProgress(fileObj, bytesDone, bytesTotal) {
	var w = jQuery('#file-upload-progress').width() - 2, item = jQuery('#file-upload-progress');
	jQuery('.bar', item).width( w * bytesDone / bytesTotal );
	jQuery('.percent', item).html( Math.ceil(bytesDone / bytesTotal * 100) + '%' );

	if ( bytesDone == bytesTotal ) {
		jQuery('.bar', item).html('<strong class="crunching">' + '<?php _e('File %s uploaded.', WPFB) ?>'.replace(/%s/g, fileObj.name) + '</strong>');
		jQuery('.filename', '#file-upload-progress').hide();
	}
}
	
//]]>
</script>
<?php
	}
	
	function Display() {

// #8545. wmode=transparent cannot be used with SWFUpload


$upload_image_path = get_user_option( 'admin_color' );
if ( 'classic' != $upload_image_path )
	$upload_image_path = 'fresh';
$upload_image_path = admin_url( 'images/upload-' . $upload_image_path . '.png?ver=20101205' );
?>
<script type="text/javascript">
//<![CDATA[
var swfu;
SWFUpload.onload = function() {
	var settings = {
			button_text: '<span class="button"><?php _e('Select Files'); ?><\/span>',
			button_text_style: '.button { text-align: center; font-weight: bold; font-family:"Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif; font-size: 11px; text-shadow: 0 1px 0 #FFFFFF; color:#464646; }',
			button_height: "23",
			button_width: "132",
			button_text_top_padding: 3,
			button_image_url: '<?php echo $upload_image_path; ?>',
			button_placeholder_id: "flash-browse-button",
			upload_url : "<?php echo esc_attr( WPFB_PLUGIN_URI.'wpfb-async-upload.php' ); ?>",
			flash_url : "<?php echo includes_url('js/swfupload/swfupload.swf'); ?>",
			file_post_name: "async-upload",
			file_types: "<?php echo apply_filters('upload_file_glob', '*.*'); ?>",
			post_params : { <?php echo $this->GetAjaxAuthData(); ?> },
			file_size_limit : "<?php
			require_once(ABSPATH . 'wp-admin/includes/template.php');
			echo wp_max_upload_size(); ?>b",
			file_queue_limit: 1,
			
			file_dialog_start_handler : (function(){}),
			
			file_queued_handler : fileQueued,
			//upload_start_handler : uploadStart,
			upload_progress_handler : uploadProgress,
			upload_error_handler : uploadError,
			upload_success_handler : uploadSuccess,
			upload_complete_handler : uploadComplete,
			
			file_queue_error_handler : fileQueueError,
			file_dialog_complete_handler : fileDialogComplete,
			
			swfupload_pre_load_handler: swfuploadPreLoad,
			swfupload_load_failed_handler: swfuploadLoadFailed,
			
			custom_settings : {
				degraded_element_id : "html-upload-ui", // id of the element displayed when swfupload is unavailable
				swfupload_element_id : "flash-upload-ui" // id of the element displayed when swfupload is available
			},
			debug: !!<?php echo (int)WP_DEBUG; ?>
		};
		swfu = new SWFUpload(settings);
};
//]]>
</script>


<?php do_action('pre-flash-upload-ui'); ?>
	<div>
	<input type="hidden" id="file_flash_upload" name="file_flash_upload" value="0" />
	<div id="flash-browse-button"></div>
	<span><input id="cancel-upload" disabled="disabled" onclick="cancelUpload()" type="button" value="<?php esc_attr_e('Cancel Upload'); ?>" class="button" /></span>
	</div>
	<div id="media-upload-error"></div>
	<div id="file-upload-progress" class="media-item" style="width: auto;"></div>
<?php
	do_action('post-flash-upload-ui');
	}
	
	
	function ProcessUpload()
	{
	}
} 