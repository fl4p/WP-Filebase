<?php
require_once(dirname(__FILE__).'/../../../wp-load.php');
if(!defined('WPFB') || !current_user_can('edit_posts'))
	wp_die(__('Cheatin&#8217; uh?'));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
<title><?php _e('Posts'); ?></title>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<?php
wp_enqueue_script('jquery');
wp_enqueue_script('jquery-treeview-async');

wp_enqueue_style( 'global' );
wp_enqueue_style( 'wp-admin' );
wp_enqueue_style( 'media' );
wp_enqueue_style( 'ie' );
wp_enqueue_style('jquery-treeview');

do_action('admin_print_styles');
do_action('admin_print_scripts');
do_action('admin_head');
?>

<script type="text/javascript">
//<![CDATA[

jQuery(document).ready(function(){
	jQuery("#wpfilebase-post-browser").treeview({
		url: "<?php echo WPFB_PLUGIN_URI."wpfb-ajax.php" ?>",
		ajax: {
			data: { action: "postbrowser", onclick: "selectPost(%d,'%s')" },
			type: "post", complete: browserAjaxComplete
		},
		animated: "medium"
	});
});

function selectPost(postId, postTitle)
{
	var el;	
	<?php if(!empty($_GET['inp_id'])) : ?>
	el = opener.document.getElementById('<?php echo $_GET['inp_id']; ?>');
	if(el != null) el.value = postId;	
	<?php endif; if(!empty($_GET['tit_id'])) : ?>
	el = opener.document.getElementById('<?php echo $_GET['tit_id']; ?>');		
	if(el != null) el.innerHTML = postTitle;
	<?php endif; ?>	
	window.close();
	return true;
}

function browserAjaxComplete(jqXHR, textStatus)
{
	if(textStatus != "success")
	{
		alert("AJAX Request error: " + textStatus);
	}
}
//]]>
</script>
</head>
<body>
<div style="margin: 10px">
<div id="wphead"><h1><?php _e('Posts'); ?></h1></div>
	<ul id="wpfilebase-post-browser" class="filetree">		
	</ul>
</div>
</body>
</html>