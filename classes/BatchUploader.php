<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BatchUploader
 *
 * @author flap
 */
class WPFB_BatchUploader {
	public function WPFB_BatchUploader()
	{
	}
	
	public function Display()
	{		
		$presets = array(
			 'category' => 0,
			 'tags' => '',
			 'author' => '',
			 'license' => '',
			 'post_id' => 0,
			 'languages' => '',
			 'offline' => 0,
			 'user_roles' => '',
			 'direct_linking' => 1,
			 'platforms' => '',
			 'requirements' => '',
		);
		
		WPFB_Core::PrintJS();
?>
<div id="batch-uploader-wrap">
	
<div id="batch-uploader-interface">
<div class="form-wrap" id="wpfb-batch-uploader-presets">			
<h2><?php _e('Upload Presets',WPFB); ?></h2>
<form method="POST" action="" class="validate">
<table class="form-table">
	
<tr class="form-field">
	<th scope="row"><label for="batch_category"><?php _e('Category') ?></label></th>
	<td><select name="file_category" id="batch_category" onchange="WPFB_formCategoryChanged()">
		<?php	echo WPFB_Output::CatSelTree(array('selected' => $presets['category'] )) ?>
	</select>
	</td>
</tr>


<tr class="form-field">
	<th scope="row"><label for="batch_tags"><?php _e('Tags') ?></label></th>
	<td><input name="file_tags" id="batch_tags" type="text" value="<?php echo esc_attr(trim($presets['tags'],',')); ?>" maxlength="250" autocomplete="off" /></td>
</tr>

<tr class="form-field">
	<th scope="row"><label for="batch_author"><?php _e('Author') ?></label></th>
	<td><input name="file_author" id="batch_author" type="text" value="<?php echo esc_attr($presets['author']); ?>" /></td>
</tr>
	
<?php if(WPFB_Core::$settings->licenses) { ?>
<tr class="form-field">
	<th scope="row"><label for="batch_license"><?php _e('License',WPFB) ?></label></th>
	<td><select id="batch_license" name="file_license"><?php echo WPFB_Admin::MakeFormOptsList('licenses', $presets['license'], true) ?></select></td>
</tr>
<?php } ?>
	
<tr class="form-field">
	<th scope="row"><label for="batch_post_id"><?php _e('Attach to Post',WPFB) ?></label></th>
	<td>ID: <input type="text" name="file_post_id" class="num" style="width:60px; text-align:right;" id="batch_post_id" value="<?php echo esc_attr($presets['post_id']); ?>" />
	<span id="batch_post_title" style="font-style:italic;"><?php if($presets['post_id'] > 0) echo get_the_title($presets['post_id']); ?></span>
	<a href="javascript:;" class="button" onclick="WPFB_PostBrowser('batch_post_id', 'batch_post_title');"><?php _e('Select') ?></a></td>
</tr>

<tr>
	<td></td>
	<td><input type="checkbox" name="file_offline" id="batch_offline" value="1" <?php checked('1', $presets['offline']); ?> />
	<label for="batch_offline" style="display: inline;"><?php _e('Don\'t publish uploaded files (set offline)', WPFB) ?></label></td>
</tr>

<?php  /*ADV_BATCH_UPLOADER*/?>

</table>
</form>
</div>
	
	<div id="batch-drag-drop-uploader">
		<h2>Drag &amp; Drop</h2>
		<div id="batch-drag-drop-area">
			<div style="margin: 70px auto 0;">
				<p class="drag-drop-info"><?php _e('Drop files here'); ?></p>
				<p><?php _ex('or', 'Uploader: Drop files here - or - Select Files'); ?></p>
				<p class="drag-drop-buttons"><input id="batch-browse-button" type="button" value="<?php esc_attr_e('Select Files'); ?>" class="button" /></p> 			
			</div>
		</div>
		<div id="batch-uploader-errors"></div>
	</div>
	
	<div style="clear: both;"></div>
</div>

<div id="batch-uploader-files"></div>

</div>

<?php
	wp_print_scripts('jquery-color');
	wp_print_scripts('jquery-deserialize');
?>

<script type="text/javascript">
	
var mouseDragPos = [];
var presetData = '';
var morePresets = 0;

jQuery(document).ready( function() {
	var form = jQuery('#wpfb-batch-uploader-presets').find('form');	
	form.find('tr.more').hide();
	form.find('tr.more-more').hide();
	morePresets = 0;
	
	jQuery('#batch-drag-drop-area').bind('dragover', function(e){
		mouseDragPos = [e.originalEvent.pageX, e.originalEvent.pageY];
	});
	
	
	form.find('*[name^="file_"]').change(function(){
		var formData = form.serialize().replace(/file_user_roles%5B%5D=.+?&/gi,''); // fix: remove user roles, serialization does not work properly!
		jQuery.ajax({url: wpfbConf.ajurl, type:"POST", data:{action:'set-user-setting',name:'batch_presets',value: formData }});
	});
	
	jQuery.ajax({url: wpfbConf.ajurl, data:{action:'get-user-setting',name:'batch_presets'}, dataType:'json', success: (function(data){
		if(data)
			jQuery('#wpfb-batch-uploader-presets').find('form').deserialize(data);
	})});

	jQuery('#batch-uploader-presets-more-toggle').click(function() {
		var pm = morePresets;
		pm++;	pm %= 3;
		batchUploaderSetPresetsMore(pm);
	});
	batchUploaderSetPresetsMore(getUserSetting('wpfb_batch_presets_more') ? parseInt(getUserSetting('wpfb_batch_presets_more')) : 0);
});

function batchUploaderSetPresetsMore(m)
{
	if(isNaN(m)) m = 0;
	var form = jQuery('#wpfb-batch-uploader-presets').find('form');
	var s = (m+morePresets);
	
	if( s==1||s==2 ) form.find('tr.more').toggle(400);
	if( m==2||morePresets==2) form.find('tr.more-more').toggle(400);
	morePresets = m;
	//form.find('tr.more').toggle(morePresets > 0);
	//form.find('tr.more-more').toggle(morePresets > 1);
	setUserSetting('wpfb_batch_presets_more',''+morePresets);
	jQuery('#batch-uploader-presets-more-toggle td span').html(m==2?'<?php _e('less'); ?>':'<?php _e('more'); ?>');
}

function batchUploaderFileError(message,file)
{
	var item = jQuery('#batch-uploader-file-' + file.id);
	
	jQuery('.error', item).show().html(message);
}

function batchUploaderFilesQueued(up, files)
{
	var form = jQuery('#wpfb-batch-uploader-presets').find('form');
	up.settings.multipart_params["presets"] = form.serialize();
	form.css({ background: "#efefef" });
	form.animate({ backgroundColor: "#ffd"}, 100);
	form.animate({ backgroundColor: "#efefef"}, 300);
}

function batchUploaderFileQueued(up, file)
{
	//file.name
	//file.size

	jQuery('#batch-uploader-files').prepend('<div id="batch-uploader-file-'+file.id+'" class="media-item batch-uploader-file">'+
	'<div class="progress"><div class="percent">0%</div><div class="bar"></div></div>'+
	'<img src="<?php echo site_url(WPINC . '/images/crystal/default.png'); ?>" /><span class="filename">'+file.name+'</span><span class="error"></span></div>');
	
	var fileEl = jQuery('#batch-uploader-file-'+file.id);
	var dest = fileEl.offset();
	
	fileEl.css({position:'absolute', zIndex:100, top:mouseDragPos[1], left:mouseDragPos[0]-dest.left-15});
	
	fileEl.animate({
		 //opacity: 0.25,
		 left: 0,
		 top: dest.top-fileEl.height()-20
	  }, 400, function() {
		 jQuery(this).css({position:'',top:0,left:0});
	  });
	  
	jQuery('.error', fileEl).hide();
}

function batchUploaderProgress(file)
{
	var item = jQuery('#batch-uploader-file-' + file.id);

	jQuery('.bar', item).width( (200 * file.loaded) / file.size );
	jQuery('.percent', item).html( file.percent + '%' );	
}

function batchUploaderSuccess(file, serverData)
{
	var item = jQuery('#batch-uploader-file-' + file.id);	
	
	jQuery('.filename', item).html('<a href="'+serverData.file_edit_url+'" target="_blank">'+serverData.file_display_name+'</a>');
	jQuery('img', item).attr('src', serverData.file_thumbnail_url);
}
</script>
<?php
	wpfb_loadclass('PLUploader');
			$uploader = new WPFB_PLUploader(true);			
			$uploader->js_file_error = 'batchUploaderFileError';
			$uploader->js_file_queued = 'batchUploaderFileQueued';
			$uploader->js_files_queued = 'batchUploaderFilesQueued';
			$uploader->js_upload_progress = 'batchUploaderProgress';
			$uploader->js_upload_success = 'batchUploaderSuccess';
			
			$uploader->post_params['file_add_now'] = true;
			
			$uploader->Init('batch-drag-drop-area', 'batch-browse-button', 'batch-drag-drop-area', 'batch-uploader-errors');
	}
}