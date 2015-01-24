<?php class WPFB_TreeviewAdmin {	
	public static function ReturnHTML($id, $drag_drop=false, $tpl_tag=null) {
		ob_start(); self::RenderHTML($id, $drag_drop, $tpl_tag); return ob_get_clean();
	}
	public static function RenderHTML($id, $drag_drop=false, $tpl_tag=null)
	{		
		$jss = md5($id);
		?>
<script type="text/javascript">
//<![CDATA[
var wpfb_fbDOMModTimeout<?php echo $jss ?> = -1;

<?php if($drag_drop) { ?>
function wpfb_dtContains(dt,t) {
	if('undefined' !== typeof dt.types.indexOf) return dt.types.indexOf(t) !== -1;
	if('undefined' !== typeof dt.types.contains) return dt.types.contains(t);
	for(var s in dt.types) {
		if(s === t) return true;
	}
	return false;
}
<?php } ?>

function wpfb_fbDOMModHandle<?php echo $jss ?>() {
	wpfb_fbDOMModTimeout<?php echo $jss ?> = -1;
	
<?php if($drag_drop) { ?>
	jQuery("#<?php echo $id ?> li:not([draggable]):not([id$='-0'])")
		.attr('draggable','true')
		.bind('dragstart', function(e) {
			var li = jQuery(e.currentTarget), t = 'file', id = wpfb_fileBrowserTargetId(e,t)||((t='cat')&&wpfb_fileBrowserTargetId(e,t));
			if(id > 0) {
				var dt = e.originalEvent.dataTransfer;
				dt.effectAllowed = (t==='cat')?'move':'linkMove';
				dt.clearData();
				dt.setData("application/x-wpfilebase-item", t+"-"+id);
				dt.setData("application/x-wpfilebase-"+t+"-"+id, ''+id);
				try { dt.setDragImage(li.find('img')[0],10,10); }
				catch(e) {}
			}
		}).bind('dragover', function(e){
			var li = jQuery(e.currentTarget), id = wpfb_fileBrowserTargetId(e,'cat'), dt = e.originalEvent.dataTransfer;
			var hasFiles = wpfb_dtContains(dt,"Files");
			var hasWpfbItem = wpfb_dtContains(dt,"application/x-wpfilebase-item");			
			if(!hasFiles && !hasWpfbItem)
				return true;
			
			var ok = hasFiles || (id > 0 && !wpfb_dtContains(dt,"application/x-wpfilebase-cat-"+id));
			var idp = wpfb_getFileBrowserIDP('<?php echo $id ?>');
			var cat_id = wpfb_fileBrowserTargetId(e,'cat'), cur_id = wpfb_fbDragCat<?php echo $jss ?>;
			if(cur_id !== cat_id && cat_id > 0) {			
				jQuery('#'+idp+'cat-'+cur_id).css({backgroundColor: ''});
				if(ok) li.css({backgroundColor: 'yellow'});
				wpfb_fbDragCat<?php echo $jss ?> = ok?cat_id:0;
			}
			
			if(hasFiles)
				return true;
			
			if(hasWpfbItem)
				e.stopPropagation();
			
			if(hasWpfbItem && ok) { // make dropk OK effect
				e.preventDefault(); 
				e.originalEvent.dataTransfer.dropEffect = 'move';
			}
		}).bind('dragleave', function(e){
			var li = jQuery(e.currentTarget);
			li.css({backgroundColor: ''});
			wpfb_fbDragCat<?php echo $jss ?> = 0;			
		}).bind('drop', function(e){		
			var li = jQuery(e.currentTarget), id = wpfb_fileBrowserTargetId(e,'cat'), dt = e.originalEvent.dataTransfer;
			if(!wpfb_dtContains(dt,"application/x-wpfilebase-item"))
				return true;
			
			e.stopPropagation();
			
			var tid = dt.getData("application/x-wpfilebase-item").split('-');		
			if(!tid || tid.length !== 2)
				return false;
			
			li.css({backgroundColor: ''});
			wpfb_fbDragCat<?php echo $jss ?> = 0;
			
			li.css({cursor:'wait'});
			
			jQuery.ajax({url: wpfbConf.ajurl, type: "POST", dataType: "json",
				data: {action:"change-category",new_cat_id:id,id:tid[1],type:tid[0]},				
				success: (function(data){
					console.log(data);
					if(data.error == false) {
						var idp = wpfb_getFileBrowserIDP('<?php echo $id ?>');
						var dLi = jQuery('#'+idp+tid.join('-')); // the dragged
						if(li.hasClass('expandable')) {
							dLi.remove();
							jQuery('.hitarea',li).click();
						} else {
							dLi.appendTo(li.children('ul').first());
						}
					} else {
						alert(data.error);
					}
				}),
				complete: (function() { li.css({cursor:''}); })
			});			
		});
<?php } /* drag_drop */ ?>
	jQuery("#<?php echo $id ?> a.add-file:not(.file-input)").each(function(i,el) {	
		var fileInput = new moxie.file.FileInput({
			multiple: true,
			//container: '<?php echo $id ?>',
			browse_button: el
		});
		
		jQuery(el).addClass('file-input');
		
		fileInput.onchange = function( event ) {
			var up = jQuery("#<?php echo $id ?>").data('uploader');
			var cat_id = wpfb_fileBrowserTargetId(jQuery(el).parent(),'cat');
			up.settings.multipart_params["btn_cat_id"] = cat_id;
			up.addFile( fileInput.files );
		};
		fileInput.init();
	});
}

jQuery(document).ready(function(){	
	wpfb_fbDragCat<?php echo $jss ?> = 0;
	jQuery("#<?php echo $id ?>")
		.bind("DOMSubtreeModified", function(e) {
			if(wpfb_fbDOMModTimeout<?php echo $jss ?> >= 0)
				window.clearTimeout(wpfb_fbDOMModTimeout<?php echo $jss ?>)
			wpfb_fbDOMModTimeout<?php echo $jss ?> = window.setTimeout(wpfb_fbDOMModHandle<?php echo $jss ?>,100);		
		})
<?php if($drag_drop) { ?>
		.bind('dragleave', function(e){
			var idp = wpfb_getFileBrowserIDP('<?php echo $id ?>');
			jQuery('#'+idp+'cat-'+wpfb_fbDragCat<?php echo $jss ?>).css({backgroundColor: ''});
			wpfb_fbDragCat<?php echo $jss ?> = 0;
		})
		.before('<div class="wpfb-drag-drop-hint">+ DRAG &amp; DROP enabled</div>')		
<?php } /* drag_drop */ ?>
	;
		
		wpfb_fbDOMModHandle<?php echo $jss ?>();
});

var callbacks<?php echo $jss ?> = {
	filesQueued: function(up, files) 	{
		var cat_id = wpfb_fbDragCat<?php echo $jss ?>;		
		if(up.settings.multipart_params["btn_cat_id"]) {
			cat_id = up.settings.multipart_params["btn_cat_id"];
			up.settings.multipart_params["btn_cat_id"] = null;
		}
		
		up.settings.multipart_params["presets"] = "file_category="+cat_id;
		up.settings.multipart_params["cat_id"] = cat_id;
		up.settings.multipart_params["tpl_tag"] = '<?php echo $tpl_tag; ?>';
		
		var idp = wpfb_getFileBrowserIDP('<?php echo $id ?>');
		var li = jQuery('#'+idp+'cat-'+cat_id);
		if(li.hasClass('expandable'))
			jQuery('.hitarea',li).click();
		
		jQuery('#'+idp+'cat-'+cat_id).css({backgroundColor: ''});
		wpfb_fbDragCat<?php echo $jss ?> = 0;
	},
	fileQueued: function(up, file) {
		var idp = wpfb_getFileBrowserIDP('<?php echo $id ?>');
		var cat_id = up.settings.multipart_params["cat_id"];
		var el = (cat_id===0) ? jQuery('#<?php echo $id ?>') : jQuery('#'+idp+'cat-'+cat_id).children('ul').first();
		el.after(
			'<div id="'+file.dom_id+'" class="wpfb-treeview-upload">'+
				'<img src="<?php echo site_url(WPINC . '/images/crystal/default.png'); ?>" alt="Loading..." style="height:1.2em;margin-right:0.3em;"/>'+
				'<span class="filename">'+file.name+'</span><span class="error"></span> '+
				'<div class="loading" style="background-image:url(<?php echo admin_url('images/loading.gif');?>);width:1.2em;height:1.2em;background-size:contain;display:inline-block;vertical-align:sub;"></div>'+
				'<span class="percent">0%</span>'+
			'</div>');
	},
	success: function(file, serverData) {
		var item = jQuery('#'+file.dom_id);
		if(serverData.tpl) {
			item.html(serverData.tpl);
		} else {
			var url = serverData.file_cur_user_can_edit ? serverData.file_edit_url : serverData.file_download_url;
			jQuery('.filename', item).html('<a href="'+url+'" target="_blank">'+serverData.file_display_name+'</a>');
			jQuery('img', item).attr('src', serverData.file_thumbnail_url);
			jQuery('.loading,.percent',item).hide();
		}
	}
};
//]]>
</script>
<?php		
		wpfb_loadclass('PLUploader');
		$uploader = new WPFB_PLUploader();	
		$cb_prefix = 'callbacks'.$jss.'.';
		$uploader->js_files_queued = $cb_prefix.'filesQueued';
		$uploader->js_file_queued = $cb_prefix.'fileQueued';
		$uploader->js_upload_success =  $cb_prefix.'success';			
		$uploader->post_params['file_add_now'] = true;			
		$uploader->Init($id);
	}
}