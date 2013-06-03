// gets the file id of the a-element linking to the file
function wpfb_getLinkFileId(el) {
	el = jQuery(el);
	var fid = el.attr('wpfbfid');
	if(fid && fid > 0) return fid;
	var fi = wpfb_getFileInfo(el.attr('href'));
	if(fi != null) return fi.id;
	return 0;
}

function wpfb_menuEdit(menuItem,menu) {
	var fid = wpfb_getLinkFileId(menu.target);
	if(fid > 0)
		window.location = wpfbConf.fileEditUrl + fid + '&redirect_to='+escape(window.location.href);
}

function wpfb_menuDel(menuItem,menu) {
	
	var fid = wpfb_getLinkFileId(menu.target);
	if(fid > 0 && confirm('Do you really want to delete this file?'))
	{		
		jQuery('body').css('cursor', 'wait');
		
		jQuery.ajax({
			type: 'POST',
			url: wpfbConf.ajurl,
			data: {action:'delete',file_id:fid},
			async: false,
			success: (function(data){
				if(data != '-1') {
					var el = jQuery(menu.target);
					el.css("textDecoration", "line-through");
					el.unbind('click').click((function(){return false;}));
					el.fadeTo('slow', 0.3);
				}
			})
		});
		
		jQuery('body').css('cursor', 'default');
	}
}

function wpfb_addContextMenu(el, url) {
	if(typeof(wpfbContextMenu) != 'undefined')
		el.contextMenu(wpfbContextMenu,{theme:'osx',shadow:false,showTransition:'fadeIn',hideTransition:'fadeOut',file_url:url});
}

function wpfb_manageAttachments(url,postId)
{
	var browserWindow = window.open("../wp-content/plugins/wp-filebase/wpfb-postbrowser.php?post=" + postId + "&inp_id=" + inputId + "&tit_id=" + titleId, "PostBrowser", "width=300,height=400,menubar=no,location=no,resizable=no,status=no,toolbar=no");
	browserWindow.focus();
}

function wpfb_toggleContextMenu() {
	wpfbConf.cm = !wpfbConf.cm;
	jQuery.ajax({url: wpfbConf.ajurl, data:'action=toggle-context-menu', async: false});
	return true;
}

function wpfb_print(obj,ret) {
	var str = ' '+obj+':',t;
	for(var k in obj) {
		t = typeof(obj[k]);
		str += ' ['+k+':'+t+'] = '+((t=='string'||t=='array')?obj[k]:wpfb_print(obj[k],true))+'\n';
	}
	if(typeof(ret) == 'undefined' || !ret)
		alert(str);
	return str;
}
