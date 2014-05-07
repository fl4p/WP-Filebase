<?php

/**
 * Description of BatchUploader
 *
 * @author flap
 */
class WPFB_BatchUploader {
	
	var $prefix;	
	var $presets;
	
	var $hidden_vars = array();
	
	public function WPFB_BatchUploader($prefix = 'batch', $presets = array())
	{
		$this->prefix = $prefix;
		$this->presets = $presets; //TODO
	}
	
	
	public function Display()
	{		
		WPFB_Core::PrintJS();
		wp_print_scripts('utils'); // setUserSetting
		?>
		<style type="text/css" media="screen">@import url(<?php echo WPFB_PLUGIN_URI.'batch-uploader.css' ?>);</style>
		
<div id="<?php echo $this->prefix; ?>-uploader-wrap">	
	<div id="<?php echo $this->prefix; ?>-uploader-interface" class="wpfb-batch-uploader-interface">	
		<div class="form-wrap uploader-presets" id="<?php echo $this->prefix; ?>-uploader-presets">	
		<form method="POST" action="" class="validate" name="batch_presets">
			 <h2><?php _e('Upload Presets',WPFB); ?></h2> 
			<?php
									self::DisplayUploadPresets($this->prefix);				
			?>
		</form>
		</div>

		<div id="<?php echo $this->prefix; ?>-drag-drop-uploader" class="drag-drop-uploader">
			 <h2>Drag &amp; Drop</h2> 
			<div id="<?php echo $this->prefix; ?>-drag-drop-area" class="drag-drop-area">
				<div style="margin: 70px auto 0;">
					<p class="drag-drop-info"><?php _e('Drop files here'); ?></p>
					<p><?php _ex('or', 'Uploader: Drop files here - or - Select Files'); ?></p>
					<p class="drag-drop-buttons"><input id="<?php echo $this->prefix; ?>-browse-button" type="button" value="<?php esc_attr_e('Select Files'); ?>" class="button" /></p> 			
				</div>
			</div>
			<div id="<?php echo $this->prefix; ?>-uploader-errors"></div>
		</div>

		<div style="clear: both;"></div>
	</div>

	<div id="<?php echo $this->prefix; ?>-uploader-files" style="position:relative;"></div>
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
	var form = jQuery('#<?php echo $this->prefix; ?>-uploader-presets').find('form');
		
	jQuery('#<?php echo $this->prefix; ?>-drag-drop-area').bind('dragover', function(e){
		mouseDragPos = [e.originalEvent.pageX, e.originalEvent.pageY];
	});	
	
<?php  { ?>
	wpfb_setupFormAutoSave(form,'batch_presets');
<?php } ?>

	// "more" toggle init
	form.find('tr.more').hide();
	form.find('tr.more-more').hide();
	morePresets = 0;
	jQuery('#<?php echo $this->prefix; ?>-uploader-presets-more-toggle').click(function() {
		var pm = morePresets; pm++; pm %= 3;
		batchUploaderSetPresetsMore(pm);
	});
	batchUploaderSetPresetsMore(getUserSetting('wpfb_batch_presets_more') ? parseInt(getUserSetting('wpfb_batch_presets_more')) : 0);
});

function batchUploaderSetPresetsMore(m)
{
	if(isNaN(m)) m = 0;
	var form = jQuery('#<?php echo $this->prefix; ?>-uploader-presets').find('form');
	var s = (m+morePresets);
	
	if( s==1||s==2 ) form.find('tr.more').toggle(400);
	if( m==2||morePresets==2) form.find('tr.more-more').toggle(400);
	morePresets = m;
	
	// TODO show any field with non-default value!!
	
	//form.find('tr.more').toggle(morePresets > 0);
	//form.find('tr.more-more').toggle(morePresets > 1);
	setUserSetting('wpfb_batch_presets_more',''+morePresets);
	jQuery('#<?php echo $this->prefix; ?>-uploader-presets-more-toggle td span').html(m==2?'<?php _e('less'); ?>':'<?php _e('more'); ?>');
}

function batchUploaderFileError(message,file)
{
	var item = jQuery('#<?php echo $this->prefix; ?>-uploader-file-' + file.id);
	
	jQuery('.error', item).show().html(message);
}

function batchUploaderFilesQueued(up, files)
{
	var form = jQuery('#<?php echo $this->prefix; ?>-uploader-presets').find('form');
	up.settings.multipart_params["presets"] = form.serialize();
	
	var hidden_params = form.find('input[type=hidden]').serializeArray();
	for (var i = 0; i < hidden_params.length; ++i) {
		up.settings.multipart_params[hidden_params[i].name] = hidden_params[i].value;
	}
	
	form
		.css({ background:			 "rgba(255,255,0,0.0)" })
		.animate({ backgroundColor: "rgba(255,255,0,0.5)"}, 100)
		.animate({ backgroundColor: "rgba(255,255,0,0.0)"}, 400);
		
	form.find('input,textarea,select')
		.animate({ opacity: 0.2}, 100)
		.animate({ opacity: 1.0}, 400);
		
	form.find("input[name='file_display_name']").val('');
}

function batchUploaderFileQueued(up, file)
{
	//file.name, file.size

	jQuery('#<?php echo $this->prefix; ?>-uploader-files').prepend('<div id="<?php echo $this->prefix; ?>-uploader-file-'+file.id+'-spacer" class="batch-uploader-file-spacer"></div>');

	jQuery('#<?php echo $this->prefix; ?>-uploader-files').prepend('<div id="<?php echo $this->prefix; ?>-uploader-file-'+file.id+'" class="media-item batch-uploader-file">'+
	'<div class="progress"><div class="percent">0%</div><div class="bar"></div></div>'+
	'<img src="<?php echo site_url(WPINC . '/images/crystal/default.png'); ?>" alt="Loading..." style="background-image:url(<?php echo admin_url('images/loading.gif');?>);"/><span class="filename">'+file.name+'</span><span class="error"></span></div>');
	
	
	var fileEl = jQuery('#<?php echo $this->prefix; ?>-uploader-file-'+file.id);
	var spacerEl = jQuery('#<?php echo $this->prefix; ?>-uploader-file-'+file.id+'-spacer');
	var dest = fileEl.offset();
	var ppos = fileEl.parent().offset();
	var destWidth = fileEl.width();
	
	fileEl.css({position:'absolute', zIndex:100, top:mouseDragPos[1]-ppos.top, left:mouseDragPos[0]-ppos.left-15});
	
	fileEl.animate({
		 //opacity: 0.25,
		 left: dest.left-ppos.left,
		 top: dest.top-ppos.top
	  }, 400, function() {
		 spacerEl.remove();
		 var startWidth = jQuery(this).width();
		 jQuery(this)
			.css({position:'',top:0,left:0,width:startWidth})
			.animate({width: destWidth}, 200);
	  });
	  
	spacerEl.animate({height: fileEl.outerHeight(true)}, 400);
	  
	jQuery('.error', fileEl).hide();
}

function batchUploaderProgress(file)
{
	var item = jQuery('#<?php echo $this->prefix; ?>-uploader-file-' + file.id);

	jQuery('.bar', item).width( (200 * file.loaded) / file.size );
	jQuery('.percent', item).html( file.percent + '%' );	
}

function batchUploaderSuccess(file, serverData)
{
	var item = jQuery('#<?php echo $this->prefix; ?>-uploader-file-' + file.id);	
	var url = serverData.file_cur_user_can_edit ? serverData.file_edit_url : serverData.file_download_url;
	jQuery('.filename', item).html('<a href="'+url+'" target="_blank">'+serverData.file_display_name+'</a>');
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
			
			if(!empty($this->hidden_vars))
				$uploader->post_params = array_merge($uploader->post_params, $this->hidden_vars);
			
			$uploader->Init($this->prefix.'-drag-drop-area', $this->prefix.'-browse-button', $this->prefix.'-drag-drop-area', $this->prefix.'-uploader-errors');
	}
	
	static function DisplayUploadPresets($prefix, $cat_select=true)
	{		
		$defaults = array(
			 'display_name' => '',
			 'category' => 0,
			 'tags' => '',
			 'description' => '',
			 'version' => '',
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
?>	
<table class="form-table">
	
<?php if($cat_select) { ?>
<tr class="form-field">
	<th scope="row"><label for="batch_category"><?php _e('Category') ?></label></th>
	<td><select name="file_category" id="<?php echo $prefix; ?>_category" onchange="WPFB_formCategoryChanged()">
		<?php	echo WPFB_Output::CatSelTree(array('selected' => $defaults['category'] )) ?>
	</select>
	</td>
</tr>
<?php } ?>

<tr class="form-field">
	<th scope="row"><label for="batch_tags"><?php _e('Tags') ?></label></th>
	<td><input name="file_tags" id="<?php echo $prefix; ?>_tags" type="text" value="<?php echo esc_attr(trim($defaults['tags'],',')); ?>" maxlength="250" autocomplete="off" /></td>
</tr>

<tr class="form-field">
	<th scope="row"><label for="batch_description"><?php _e('Description') ?></label></th>
	<td><textarea name="file_description" id="<?php echo $prefix; ?>_description" rows="2"><?php echo esc_html($defaults['description']); ?></textarea></td>
</tr>

<tr class="form-field">
	<th scope="row"><label for="batch_author"><?php _e('Author') ?></label></th>
	<td><input name="file_author" id="<?php echo $prefix; ?>_author" type="text" value="<?php echo esc_attr($defaults['author']); ?>" /></td>
</tr>
	
<?php if(WPFB_Core::$settings->licenses) { ?>
<tr class="form-field">
	<th scope="row"><label for="batch_license"><?php _e('License',WPFB) ?></label></th>
	<td><select id="<?php echo $prefix; ?>_license" name="file_license"><?php echo WPFB_Admin::MakeFormOptsList('licenses', $defaults['license'], true) ?></select></td>
</tr>
<?php } ?>

<tr class="form-field">
	<th scope="row"><label for="<?php echo $prefix; ?>_post_id"><?php _e('Attach to Post',WPFB) ?></label></th>
	<td>ID: <input type="text" name="file_post_id" class="num" style="width:60px; text-align:right;" id="<?php echo $prefix; ?>_post_id" value="<?php echo esc_attr($defaults['post_id']); ?>" />
	<span id="<?php echo $prefix; ?>_post_title" style="font-style:italic;"><?php if($defaults['post_id'] > 0) echo get_the_title($defaults['post_id']); ?></span>
	<a href="javascript:;" class="button" onclick="WPFB_PostBrowser('batch_post_id', 'batch_post_title');"><?php _e('Select') ?></a></td>
</tr>

<tr>
	<td></td>
	<td><input type="checkbox" name="file_offline" id="<?php echo $prefix; ?>_offline" value="1" <?php checked('1', $defaults['offline']); ?> />
	<label for="<?php echo $prefix; ?>_offline" style="display: inline;"><?php _e('Don\'t publish uploaded files (set offline)', WPFB) ?></label></td>
</tr>

<?php  /*ADV_BATCH_UPLOADER*/?>

</table>
	<?php
	}
}