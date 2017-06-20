<?php abstract class WPFB_AdvUploader {
	
	/**
	 * @var PLUpload
	 */
	var $uploader;
	var $form_url;
	var $id;
	var $is_edit;
	
	public static function Create($form_url, $is_edit=false)
	{
		$uploader_class = (version_compare(get_bloginfo('version'), '3.2.1') <= 0) ? 'SWFUpload' : 'PLUpload';
		wpfb_loadclass($uploader_class);
		$uploader_class = "WPFB_".$uploader_class;

		return new $uploader_class($form_url, $is_edit);
	}
	
	function GetAjaxAuthData($json=false)
	{
		$frontend = !is_admin();
		$dat = array(
			"auth_cookie" => (is_ssl() ? @$_COOKIE[SECURE_AUTH_COOKIE] : @$_COOKIE[AUTH_COOKIE]),
			"logged_in_cookie" => @$_COOKIE[LOGGED_IN_COOKIE],
			"_wpnonce" => wp_create_nonce(WPFB.'-async-upload'),
			"frontend_upload" => $frontend,
			"file_add_now" => (!$frontend && !$this->is_edit)
		);
		return $json ? trim(json_encode($dat),'{}') : $dat;
	}
	
	function __construct($form_url, $is_edit=false)
	{
		$this->form_url = $form_url;
		$this->id = uniqid();
		$this->is_edit = $is_edit;
	}
	
	function PrintScripts($prefix='', $auto_submit=false)
	{
		$this->Scripts($prefix);
		
		$minify = true;
		
		if($minify) ob_start();
		?><script type="text/javascript">
/* <![CDATA[ */



jQuery(document).ready(function () {
	jQuery('#file_display_name,#file_version' ).keyup(function() { jQuery(this).data('keyUpTriggered', true); });
});

function fileQueued(fileObj) {
	jQuery('#file-upload-progress').show().html('<div class="progress"><div class="percent">0%</div><div class="bar" style="width: 30px"></div></div><div class="filename original"> ' + fileObj.name + '</div>');

	jQuery('.progress', '#file-upload-progress').show();
	jQuery('.filename', '#file-upload-progress').show();

	jQuery('#file_thumbnail_preview').hide();

	jQuery("#media-upload-error").empty();
	jQuery('.upload-flash-bypass').hide();
	
	jQuery('#file-submit').prop('disabled', true);
	jQuery('#cancel-upload').show().prop('disabled', false);

	jQuery('#file_rename').val(fileObj.name);

	/* parse file name and fill display name and version */
		jQuery.ajax({url:wpfbConf.ajurl, data:{wpfb_action: 'parse-filename', filename:fileObj.name},
			success: (function(data){
				var d = jQuery('#file_display_name'), v = jQuery('#file_version');
				d.data('keyUpTriggered')||d.val(data.title);
				v.data('keyUpTriggered')||v.val(data.version);
			})
		});

	 /* delete already uploaded temp file */
	if(jQuery('#file_flash_upload').val() != '0') {
		jQuery.ajax({type: 'POST', async: true, url:"<?php echo esc_attr( WPFB_Core::$ajax_url_public ); ?>",
		data: {<?php echo $this->GetAjaxAuthData(true) ?> , "wpfb_action": "upload", "delupload": jQuery('#file_flash_upload').val()},
		success: (function(data){})
		});
		jQuery('#file_flash_upload').val(0);
	}
}
           
function wpFileError(fileObj, message) {
	jQuery('#media-upload-error').show().html(message);
	jQuery('.upload-flash-bypass').show();
	jQuery("#file-upload-progress").hide().empty();
	jQuery('#cancel-upload').hide().prop('disabled', true);
}


function uploadError(fileObj, errorCode, message, uploader) {
	wpFileError(fileObj, "Error "+errorCode+": "+message);
}

function uploadSuccess(fileObj, serverData) {
	/* if async-upload returned an error message, place it in the media item div and return */
	if ( serverData.match('media-upload-error') || serverData.match('error-div') ) {
		wpFileError(fileObj, serverData);
		return;
	}

	jQuery('#file_thumbnail_wrap').hide();
	jQuery('#file_thumbnail_preview').hide();
	
	var file_obj = jQuery.parseJSON(serverData);

	if(file_obj && file_obj.nonce) {
		jQuery('#wpfb-file-nonce').val(file_obj.nonce);
	}

	jQuery('#file-upload-progress').html('<strong class="crunching">' + '<?php _e('%s uploaded.','wp-filebase') ?>'.replace(/%s/g, file_obj.file_display_name ? file_obj.file_display_name : file_obj.name) + '</strong>');
	
	if(file_obj && 'undefined' != typeof(file_obj.file_id)) {		
		jQuery('#file_form_action').val("updatefile");
		jQuery('#file_id').val(file_obj.file_id);

		
		if(file_obj.file_thumbnail) {
			jQuery('#file_thumbnail_wrap').show().children('img').attr('src', file_obj.file_thumbnail_url);
			jQuery('#file_thumbnail_name').html(file_obj.file_thumbnail);			
			jQuery('#file_thumbnail_preview').show().css("background-image", 'url(\''+file_obj.file_thumbnail_url+'\')');
		}
		
		jQuery('#file_display_name').val(file_obj.file_display_name);
		jQuery('#file_version').val(file_obj.file_version);

	} else {
		jQuery('#file_flash_upload').val(serverData);
	}
	
	jQuery('#file-submit').prop('disabled', false);

	<?php if($auto_submit) { ?>
	jQuery('#file_flash_upload').closest("form").submit();
	<?php } ?>
}

function uploadComplete(fileObj) {
	jQuery('#cancel-upload').hide().prop('disabled', true);
}

	
/* ]]> */
</script>
<?php
		
		if($minify) { // todo: remove // comments!!
			echo str_replace(array(" /* <![CDATA[ */ "," /* ]]> */ "), array("\r\n/* <![CDATA[ */\r\n","\r\n/* ]]> */\r\n"),
					  str_replace(array("\r\n", "\n"), " ", ob_get_clean())
			);
		}
	}
	
	function Display()
	{
		$this->uploader->Display($this->form_url);
	}
}