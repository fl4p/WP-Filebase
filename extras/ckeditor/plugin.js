(function() {
/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.plugins.add( 'wpfilebase',
{
	requires: [ 'iframedialog' ],
	init : function( editor )
	{
		var me = this;
        CKEDITOR.dialog.add( 'WPFilebaseDialog', function (){
        	var postId = '',idEl = document.getElementById("post_ID");
        	if(idEl) postId = idEl.getAttribute('value');
			return {
				title : 'WP-Filebase',
				minWidth : 680,
				minHeight : 400,
				contents :
					[
						{
							id : 'iframe',
							label : 'WP-Filebase',
							expand : true,
							elements :
								[
									{
										type : 'html',
										id : 'pageWPFilebase',
										label : 'WP-Filebase',
										style : 'width:680px; height:400px;',
										html : '<iframe src="'+me.path+'../../editor_plugin.php?post_id='+postId+'" frameborder="0" name="iframeWPFilebase" id="iframeWPFilebase" allowtransparency="1" style="width:100%;height:400px;margin:0;padding:0;"></iframe>'
									}
								]
						}
					] /*,
				onOk : function() {
					var editor = this.getParentEditor(),
						ratingcode = document.getElementById('iframeWPFilebase').contentWindow.insertWPFilebaseCode();
					editor.insertHtml(ratingcode[0]);
				}*/
			};
		});


		// Register the toolbar buttons.
		editor.ui.addButton( 'WPFilebase',
			{
				label : 'WP-Filebase',
				icon: this.path + 'images/btn.gif',
				command : 'wpfb-dialog'
			});

		// Register the commands.
		editor.addCommand( 'wpfb-dialog', new CKEDITOR.dialogCommand( 'WPFilebaseDialog' ));
	}
});
})();