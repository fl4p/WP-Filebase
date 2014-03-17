<?php

class WPFB_PLUpload extends WPFB_AdvUploader
{	
	function Scripts()
	{
		$id = $this->id;
		
		wp_print_scripts('plupload-handlers');
		?>
		
<script type="text/javascript">
//<![CDATA[

function uploadProgress(up, file) {
	var item = jQuery('#file-upload-progress');
	jQuery('.bar', item).width( (200 * file.loaded) / file.size );
	jQuery('.percent', item).html( file.percent + '%' );

	if ( file.percent == 100 ) {
		item.html('<strong class="crunching">' + '<?php _e('File %s uploaded.', WPFB) ?>'.replace(/%s/g, file.name) + '</strong>');
	}
}
	
//]]>
</script>
<?php
	}
	
function Display() {

global $is_IE, $is_opera;

$id = $this->id;

$upload_size_unit = $max_upload_size = WPFB_Core::GetMaxUlSize();
$sizes = array( 'KB', 'MB', 'GB' );

for ( $u = -1; $upload_size_unit > 1024 && $u < count( $sizes ) - 1; $u++ ) {
	$upload_size_unit /= 1024;
}

if ( $u < 0 ) {
	$upload_size_unit = 0;
	$u = 0;
} else {
	$upload_size_unit = (int) $upload_size_unit;
}
		
do_action('pre-upload-ui');

$plupload_init = array(
	'runtimes' => 'html5,silverlight,flash,html4',
	'browse_button' => 'plupload-browse-button',
	'container' => 'plupload-upload-ui',
	'drop_element' => 'drag-drop-area',
	'file_data_name' => 'async-upload',
	'multiple_queues' => false,
	'max_file_size' => $max_upload_size.'b',
	'url' => WPFB_PLUGIN_URI.'wpfb-async-upload.php',
	'flash_swf_url' => includes_url('js/plupload/plupload.flash.swf'),
	'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
	'filters' => array( array('title' => __( 'Allowed Files' ), 'extensions' => '*') ),
	'multipart' => true,
	'urlstream_upload' => true,
	'multipart_params' => $this->GetAjaxAuthData()
);

$plupload_init = apply_filters( 'plupload_init', $plupload_init );

?>

<script type="text/javascript">
var resize_height = 1024, resize_width = 1024, // this is for img resizing (not used here!)
wpUploaderInit = <?php echo json_encode($plupload_init); ?>;
</script>

<input type="hidden" id="file_flash_upload" name="file_flash_upload" value="0" />

<div id="plupload-upload-ui" class="hide-if-no-js">
<?php do_action('pre-plupload-upload-ui'); // hook change, old name: 'pre-flash-upload-ui' ?>
<div id="drag-drop-area">
	<div class="drag-drop-inside">
	<p class="drag-drop-info">
		<?php _e('Drop files here - or -',WPFB); ?>
		<span class="drag-drop-buttons"><input id="plupload-browse-button" type="button" value="<?php esc_attr_e('Select Files'); ?>" class="button" /></span>
		<span class="drag-drop-info-spacer"></span>
	</p>
	</div>
</div>
	<p class="upload-flash-bypass">
	<?php printf( __( 'You are using the multi-file uploader. Problems? Try the <a href="%1$s">browser uploader</a> instead.' ), esc_url(add_query_arg('flash', 0)) ); ?>
	</p>
	
</div>

<?php
if ( ($is_IE || $is_opera) && $max_upload_size > 100 * 1024 * 1024 ) { ?>
	<span class="big-file-warning"><?php _e('Your browser has some limitations uploading large files with the multi-file uploader. Please use the browser uploader for files over 100MB.'); ?></span>
<?php }
?>
	<div id="media-upload-error"></div>
	<div id="file-upload-progress" class="media-item" style="width: auto;"></div>
<?php
//do_action('post-upload-ui');
}
} 