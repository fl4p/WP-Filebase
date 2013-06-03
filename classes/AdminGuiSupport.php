<?php
class WPFB_AdminGuiSupport {
static function Display()
{
	global $wpdb, $user_ID;
	
	wpfb_loadclass('Admin', 'Output');
	
	$_POST = stripslashes_deep($_POST);
	$_GET = stripslashes_deep($_GET);	
	$action = (!empty($_POST['action']) ? $_POST['action'] : (!empty($_GET['action']) ? $_GET['action'] : ''));
	$clean_uri = remove_query_arg(array('message', 'action', 'file_id', 'cat_id', 'deltpl', 'hash_sync' /* , 's'*/)); // keep search keyword
	
	WPFB_Admin::PrintFlattrHead();
	
	?><div class="wrap"><?php
	
	switch($action)
	{
	default:		

?>
<div id="wpfilebase-donate">
<p><?php _e('If you like WP-Filebase I would appreciate a small donation to support my work. You can additionally add an idea to make WP-Filebase even better. Just click the button below. Thank you!', WPFB) ?></p>
<?php WPFB_Admin::PrintPayPalButton() ?>
<?php WPFB_Admin::PrintFlattrButton() ?>
</div>
<?php  
		break;
	}	
	?>
</div> <!-- wrap -->
<?php
}
}
?>