<?php class WPFB_PLUploader {
	
	var $multi;
	var $images_only;
	var $post_params;
	
	
	// (message,file)
	var $js_file_error;
	
	// (up)
	var $js_files_queued;
	
	// (up, file)
	var $js_file_queued;
	
	// (file)
	var $js_upload_progress;
	
	// (file, serverData)
	var $js_upload_success;	
	
	var $prefix;
	
	function WPFB_PLUploader($multi=false, $images_only=false)
	{
		$this->prefix = uniqid();
		$this->multi = $multi;
		$this->images_only = $images_only;
		$this->post_params = array();
	}
	
	private function GetAjaxAuthData()
	{
		return array(
			"auth_cookie" => (is_ssl() ? @$_COOKIE[SECURE_AUTH_COOKIE] : @$_COOKIE[AUTH_COOKIE]),
			"logged_in_cookie" => @$_COOKIE[LOGGED_IN_COOKIE],
			"_wpnonce" => wp_create_nonce(WPFB.'-async-upload')
		);
	}
	
public function Display()
{
	$this->Init($this->prefix.'container', $this->prefix.'pickfiles', $this->prefix.'drop', $this->prefix.'error');
	?>
<div id="<?php echo $this->prefix; ?>container">
	<div id="<?php echo $this->prefix; ?>drop">
		Drag &amp; Drop
	</div>
	<br />
   <a id="<?php echo $this->prefix; ?>pickfiles" href="#" class="button"><?php _e($this->multi ? 'Select Files' : 'Select File',WPFB); ?></a>
	<br />
	<div id="<?php echo $this->prefix; ?>error"></div>
</div>
<?php
}

public function Init($cotainer_id, $browser_btn_id, $drop_el_id, $error_el_id)
{
	$max_upload_size = WPFB_Core::GetMaxUlSize();
	
	wp_print_scripts('plupload-all'); 
	wp_print_scripts('wp-plupload');
	
	
$plupload_init = array(
	'runtimes' => 'html5,gears,silverlight,flash,html4',
	'browse_button' => $browser_btn_id,
	'container' => $cotainer_id,
	'drop_element' => $drop_el_id,
	'file_data_name' => 'async-upload',
	'multiple_queues' => $this->multi,
	'max_file_size' => $max_upload_size.'b',
	'url' => WPFB_PLUGIN_URI.'wpfb-async-upload.php',
	'flash_swf_url' => includes_url('js/plupload/plupload.flash.swf'),
	'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
	'filters' => array( array('title' => $this->images_only ? __('Images') : __( 'Allowed Files' ), 'extensions' => $this->images_only ? 'jpg,gif,png,bmp' : '*') ),
	'multipart' => true,
	'urlstream_upload' => true,
	'multipart_params' => array_merge($this->GetAjaxAuthData(), $this->post_params)
	 
	 // resize :{width : 320, height : 240, quality : 90}
);
	?>
<script type="text/javascript">
	(function() {
		var uploader = new plupload.Uploader(<?php echo json_encode($plupload_init); ?>);
		
		uploader.bind('Init', function(up) {
			//var uploaddiv = jQuery('#<?php echo $cotainer_id; ?>');
			var dropdiv = jQuery('#<?php echo $drop_el_id; ?>');

			if ( !jQuery(document.body).hasClass('mobile') ) {
				dropdiv.addClass('drag-drop');
				dropdiv.bind('dragover', function(e){
					dropdiv.addClass('drag-over');
				}).bind('dragleave, drop', function(){
					dropdiv.removeClass('drag-over');
				});
			} else {
				dropdiv.removeClass('drag-drop');
				dropdiv.hide();
			}

//			if ( up.runtime == 'html4' )
//				jQuery('.upload-flash-bypass').hide();
		});
		
		uploader.init();
		
		var mobile = <?php echo (int)wp_is_mobile(); ?>;
		var supported = <?php echo (int)(!wp_is_mobile() || (function_exists('_device_can_upload') && _device_can_upload())); ?>;
			
		var supports_dragdrop = uploader.features.dragdrop && !mobile;

		// Generate drag/drop helper classes.
		(function( dropzone, supported ) {
			var timer, active;

			if ( ! dropzone )
				return;

			dropzone.toggleClass( 'supports-drag-drop', !! supported );

			if ( ! supported )
				return dropzone.unbind('.wp-uploader');

			// 'dragenter' doesn't fire correctly,
			// simulate it with a limited 'dragover'
			dropzone.bind( 'dragover.wp-uploader', function(){
				if ( timer )
					clearTimeout( timer );

				if ( active )
					return;

				dropzone.trigger('dropzone:enter').addClass('drag-over');
				active = true;
			});

			dropzone.bind('dragleave.wp-uploader, drop.wp-uploader', function(){
				// Using an instant timer prevents the drag-over class from
				// being quickly removed and re-added when elements inside the
				// dropzone are repositioned.
				//
				// See http://core.trac.wordpress.org/ticket/21705
				timer = setTimeout( function() {
					active = false;
					dropzone.trigger('dropzone:leave').removeClass('drag-over');
				}, 0 );
			});
		}( jQuery('#<?php echo $drop_el_id; ?>'), supports_dragdrop ));
		
		uploader.bind('FilesAdded', function(up, files) {
			var hundredmb = 100 * 1024 * 1024, max = parseInt(up.settings.max_file_size, 10);

			jQuery('#<?php echo $error_el_id; ?>').html('');
			
			<?php if(!empty($this->js_files_queued)) echo $this->js_files_queued.'(up, files)'; ?>

			plupload.each(files, function(file){
				if ( max > hundredmb && file.size > hundredmb && up.runtime != 'html5' )
					wpfbPlUploadSizeError( up, file, true );
				else
					<?php echo $this->js_file_queued; ?>(up, file);
			});

			up.refresh();
			up.start();
		});

		uploader.bind('BeforeUpload', function(up, file) {
			// something
		});

		uploader.bind('UploadFile', function(up, file) {
			wpfbPlFileUploading(up, file);
		});

		uploader.bind('UploadProgress', function(up, file) {
			<?php echo $this->js_upload_progress; ?>(file);
		});

		uploader.bind('Error', function(up, err) {
			wpfbPlUploadError(err.file, err.code, err.message, up);
			up.refresh();
		});

		uploader.bind('FileUploaded', function(up, file, response) {
			// on success serverData should be numeric, fix bug in html4 runtime returning the serverData wrapped in a <pre> tag
			var serverData = response.response.replace(/^<pre>(.+)<\/pre>$/, '$1');
			
			if ( serverData.match('media-upload-error') || serverData.match('error-div') ) {
				wpfbPlFileError(file, serverData);
				return;
			}
			
			serverData = jQuery.parseJSON(serverData);
			
			<?php echo $this->js_upload_success; ?>(file, serverData);
		});

		uploader.bind('UploadComplete', function(up, files) {
			//uploadComplete();
		}); 
		
		
	})();
	
// EXTERNAL
/*
function fileQueued(fileObj) {
	
	
	// Create a progress bar containing the filename
	jQuery('#<?php echo $this->prefix; ?>files').append('<div id="<?php echo $this->prefix; ?>file-' + fileObj.id + '" class="media-item child-of-' + postid + '"><div class="progress"><div class="percent">0%</div><div class="bar"></div></div><div class="filename original"> ' + fileObj.name + '</div></div>');
	
	
	jQuery('#file-upload-progress').show().html('<div class="progress"><div class="percent">0%</div><div class="bar" style="width: 30px"></div></div><div class="filename original"> ' + fileObj.name + '</div>');

	jQuery('.progress', '#file-upload-progress').show();
	jQuery('.filename', '#file-upload-progress').show();

	jQuery("#media-upload-error").empty();
	jQuery('.upload-flash-bypass').hide();
	
	jQuery('#file-submit').prop('disabled', true);
	jQuery('#cancel-upload').show().prop('disabled', false);

	 // delete already uploaded temp file	
	if(jQuery('#file_flash_upload').val() != '0') {
		var postdata = <?php echo json_encode($this->GetAjaxAuthData()); ?>;
		postdata.delupload = jQuery('#file_flash_upload').val();
		jQuery.ajax({type: 'POST', async: true, url:"<?php echo esc_attr( WPFB_PLUGIN_URI.'wpfb-async-upload.php' ); ?>",
		data: postdata,
		success: (function(data){})
		});
		jQuery('#file_flash_upload').val(0);
	}
}
*/

function wpfbPlFileUploading(up, file) {
	var hundredmb = 100 * 1024 * 1024, max = parseInt(up.settings.max_file_size, 10);

	if ( max > hundredmb && file.size > hundredmb ) {
		setTimeout(function(){
			var done;

			if ( file.status < 3 && file.loaded == 0 ) { // not uploading
				wpfbPlFileError(file, pluploadL10n.big_upload_failed.replace('%1$s', '<a class="uploader-html" href="#">').replace('%2$s', '</a>'));
				up.stop(); // stops the whole queue
				up.removeFile(file);
				up.start(); // restart the queue
			}
		}, 10000); // wait for 10 sec. for the file to start uploading
	}
}

/*
function uploadProgress(up, file) {
	var item = jQuery('#media-item-' + file.id);

	jQuery('.bar', item).width( (200 * file.loaded) / file.size );
	jQuery('.percent', item).html( file.percent + '%' );
}*/


function wpfbPlUploadSizeError( up, file, over100mb ) {
	var message;

	if ( over100mb )
		message = pluploadL10n.big_upload_queued.replace('%s', file.name) + ' ' + pluploadL10n.big_upload_failed.replace('%1$s', '<a class="uploader-html" href="#">').replace('%2$s', '</a>');
	else
		message = pluploadL10n.file_exceeds_size_limit.replace('%s', file.name);

	<?php echo $this->js_file_error; ?> (message,file);

	up.removeFile(file);
}

function wpfbPlUploadError(fileObj, errorCode, message, uploader) {
	var hundredmb = 100 * 1024 * 1024, max;

	switch (errorCode) {
		case plupload.FAILED:
			wpfbPlFileError(fileObj, pluploadL10n.upload_failed);
			break;
		case plupload.FILE_EXTENSION_ERROR:
			wpfbPlFileError(fileObj, pluploadL10n.invalid_filetype);
			break;
		case plupload.FILE_SIZE_ERROR:
			wpfbPlUploadSizeError(uploader, fileObj);
			break;
		case plupload.IMAGE_FORMAT_ERROR:
			wpfbPlFileError(fileObj, pluploadL10n.not_an_image);
			break;
		case plupload.IMAGE_MEMORY_ERROR:
			wpfbPlFileError(fileObj, pluploadL10n.image_memory_exceeded);
			break;
		case plupload.IMAGE_DIMENSIONS_ERROR:
			wpfbPlFileError(fileObj, pluploadL10n.image_dimensions_exceeded);
			break;
		case plupload.GENERIC_ERROR:
			wpfbPlQueueError(pluploadL10n.upload_failed);
			break;
		case plupload.IO_ERROR:
			max = parseInt(uploader.settings.max_file_size, 10);

			if ( max > hundredmb && fileObj.size > hundredmb )
				wpfbPlFileError(fileObj, pluploadL10n.big_upload_failed.replace('%1$s', '<a class="uploader-html" href="#">').replace('%2$s', '</a>'));
			else
				wpfbPlQueueError(pluploadL10n.io_error);
			break;
		case plupload.HTTP_ERROR:
			wpfbPlQueueError(pluploadL10n.http_error);
			break;
		case plupload.INIT_ERROR:
			//jQuery('.media-upload-form').addClass('html-uploader');
			// TODO: on init failure
			break;
		case plupload.SECURITY_ERROR:
			wpfbPlQueueError(pluploadL10n.security_error);
			break;
/*		case plupload.UPLOAD_ERROR.UPLOAD_STOPPED:
		case plupload.UPLOAD_ERROR.FILE_CANCELLED:
			jQuery('#media-item-' + fileObj.id).remove();
			break;*/
		default:
			wpfbPlFileError(fileObj, pluploadL10n.default_error);
	}
}

/*
function uploadSuccess(fileObj, serverData) {
	var item = jQuery('#media-item-' + fileObj.id);

	// on success serverData should be numeric, fix bug in html4 runtime returning the serverData wrapped in a <pre> tag
	serverData = serverData.replace(/^<pre>(\d+)<\/pre>$/, '$1');

	// if async-upload returned an error message, place it in the media item div and return
	if ( serverData.match(/media-upload-error|error-div/) ) {
		item.html(serverData);
		return;
	} else {
		jQuery('.percent', item).html( pluploadL10n.crunching );
	}

	prepareMediaItem(fileObj, serverData);
	updateMediaForm();

	// Increment the counter.
	if ( post_id && item.hasClass('child-of-' + post_id) )
		jQuery('#attachments-count').text(1 * jQuery('#attachments-count').text() + 1);
}
*/

function wpfbPlFileError(fileObj, message) {
	<?php echo $this->js_file_error; ?> (message,fileObj);
}

// generic error message
function wpfbPlQueueError(message) {
	jQuery('#<?php echo $error_el_id; ?>').show().html( '<div class="error"><p>' + message + '</p></div>' );
}
</script>
<?php
}
}