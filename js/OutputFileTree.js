jQuery(function($) {

	$("ul[id^='wpfb-filebrowser']").each(function(i, obj) {

		var base_id = $(obj).data('base');
		var wpfb_id = $(obj).data('wpfb');
		var wpfb_element_id = $(obj).attr('id');
		
	    $(obj).treeview(wpfb_element_id={
	      url: params.url,
	      ajax: {
	        data:{
	        	"wpfb_action":"tree",
	        	"type":"browser",
	        	"base": base_id
	        },
	        type:'post',
	        error:function(x,status,error) {
	          if(error) alert(error);
	        },
	        complete: function(x,status) {
	          if (typeof(wpfb_setupLinks)=='function') {
	            wpfb_setupLinks();
	          }
	        }
	      },
	      animated: 'medium'
	      }
	    )
	    .data('settings', wpfb_element_id);
	});
	
});
