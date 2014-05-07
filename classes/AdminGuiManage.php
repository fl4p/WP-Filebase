<?php
class WPFB_AdminGuiManage {
static function Display()
{
	global $wpdb, $user_ID;
	
	//register_shutdown_function( create_function('','$error = error_get_last(); if( $error && $error[\'type\'] != E_STRICT ){print_r( $error );}else{return true;}') );
	
	wpfb_loadclass('File', 'Category', 'Admin', 'Output');
	
	$_POST = stripslashes_deep($_POST);
	$_GET = stripslashes_deep($_GET);	
	$action = (!empty($_POST['action']) ? $_POST['action'] : (!empty($_GET['action']) ? $_GET['action'] : ''));
	$clean_uri = remove_query_arg(array('message', 'action', 'file_id', 'cat_id', 'deltpl', 'hash_sync', 'doit', 'ids', 'files', 'cats', 'batch_sync' /* , 's'*/)); // keep search keyword	
	

	// switch simple/extended form
	if(isset($_GET['exform'])) {
		$exform = (!empty($_GET['exform']) && $_GET['exform'] == 1);
		update_user_option($user_ID, WPFB_OPT_NAME . '_exform', $exform); 
	} else
		$exform = (bool)get_user_option(WPFB_OPT_NAME . '_exform');
		
	if(!empty($_GET['wpfb-hide-how-start']))
		update_user_option($user_ID, WPFB_OPT_NAME . '_hide_how_start', 1);		
	$show_how_start = !(bool)get_user_option(WPFB_OPT_NAME . '_hide_how_start');	

	WPFB_Admin::PrintFlattrHead();
	?>
	<script type="text/javascript">	
	/* Liking/Donate Bar */
	if(typeof(jQuery) != 'undefined') {
		jQuery(document).ready(function(){
			if(getUserSetting("wpfilebase_hidesuprow",false) == 1) {
				jQuery('#wpfb-liking').hide();
				jQuery('#wpfb-liking-toggle').addClass('closed');	
			}	
			jQuery('#wpfb-liking-toggle').click(function(){
				jQuery('#wpfb-liking').slideToggle();
				jQuery(this).toggleClass('closed');
				setUserSetting("wpfilebase_hidesuprow", 1-getUserSetting("wpfilebase_hidesuprow",false), 0);
			});	
		});
	}
	</script>
	

	<div class="wrap">
	<div id="icon-wpfilebase" class="icon32"><br /></div>
	<h2><?php echo WPFB_PLUGIN_NAME; ?></h2>
	
	<?php

		
	if($show_how_start)
		wpfb_call('AdminHowToStart', 'Display');
		
	if(!empty($_GET['action']))
			echo '<p><a href="' . $clean_uri . '" class="button">' . __('Go back'/*def*/) . '</a></p>';
	
	switch($action)
	{
		default:
			$clean_uri = remove_query_arg('pagenum', $clean_uri);
			
				$upload_dir = WPFB_Core::UploadDir();
				$upload_dir_rel = str_replace(ABSPATH, '', $upload_dir);
				$chmod_cmd = "CHMOD ".WPFB_PERM_DIR." ".$upload_dir_rel;
				if(!is_dir($upload_dir)) {
					$result = WPFB_Admin::Mkdir($upload_dir);
					if($result['error'])
						$error_msg = sprintf(__('The upload directory <code>%s</code> does not exists. It could not be created automatically because the directory <code>%s</code> is not writable. Please create <code>%s</code> and make it writable for the webserver by executing the following FTP command: <code>%s</code>', WPFB), $upload_dir_rel, str_replace(ABSPATH, '', $result['parent']), $upload_dir_rel, $chmod_cmd);
					else
						wpfb_call('Setup','ProtectUploadPath');
				} elseif(!is_writable($upload_dir)) {
					$error_msg = sprintf(__('The upload directory <code>%s</code> is not writable. Please make it writable for PHP by executing the follwing FTP command: <code>%s</code>', WPFB), $upload_dir_rel, $chmod_cmd);
				}
				
				if(!empty($error_msg)) echo '<div class="error default-password-nag"><p>'.$error_msg.'</p></div>';				
				
					if(WPFB_Core::$settings->tag_conv_req) {
					echo '<div class="updated"><p><a href="'.add_query_arg('action', 'convert-tags').'">';
					_e('WP-Filebase content tags must be converted',WPFB);
					echo '</a></p></div><div style="clear:both;"></div>';
				}
				
				if(!get_post(WPFB_Core::$settings->file_browser_post_id)) {
					echo '<div class="updated"><p>';
					printf(__('File Browser post or page not set! Some features like search will not work. <a href="%s">Click here to set the File Browser Post ID.</a>',WPFB), esc_attr(admin_url('admin.php?page=wpfilebase_sets#'.sanitize_title(__('File Browser',WPFB)))));
					echo '</p></div><div style="clear:both;"></div>';
				}
				
				/*
				wpfb_loadclass('Config');
				if(!WPFB_Config::IsWritable()) {
					echo '<div class="updated"><p>';
					printf(__('The config file %s is not writable or could not be created. Please create the file and make it writable for the webserver.',WPFB), WPFB_Config::$file);
					echo '</p></div><div style="clear:both;"></div>';
				}
				*/
		?>
	<?php
	if(self::PluginHasBeenUsedAWhile()) { ?>		
<div id="wpfb-support-col">
<div id="wpfb-liking-toggle"></div>
<h3><?php _e('Like WP-Filebase?',WPFB) ?></h3>
<div id="wpfb-liking">
	<div style="text-align: center;"><iframe src="http://www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwordpress.org%2Fextend%2Fplugins%2Fwp-filebase%2F&amp;send=false&amp;layout=button_count&amp;width=150&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font&amp;height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:140px; height:21px; display:inline-block; text-align:center;" <?php echo ' allowTransparency="true"'; ?>></iframe></div>
	
	<div style="text-align: center;" ><a href="https://twitter.com/wpfilebase" class="twitter-follow-button" data-show-count="false">Follow @wpfilebase</a>
			<script type="text/javascript">!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></div>
	
	<p>Please <a href="http://wordpress.org/support/view/plugin-reviews/wp-filebase">give it a good rating</a>, or even consider a donation using PayPal or Flattr to support development of WP-Filebase.<br /><span style="text-align:right;float:right;font-style:italic;">Thanks, Fabian</p> 
	<div style="text-align: center;">	
	<?php WPFB_Admin::PrintPayPalButton() ?>
	<?php WPFB_Admin::PrintFlattrButton() ?>
	</div>
</div>
</div>
<?php }

?>

<div id="wpfb-stats-wrap" style="float:right; border-left: 1px solid #eee; margin-left: 5px;">
<div id="col-container">
	<div id="col-right">
		<div class="col-wrap">
			<h3><?php _e('Traffic', WPFB); ?></h3>
			<table class="wpfb-stats-table">
			<?php
				$traffic_stats = wpfb_call('Misc','GetTraffic');					
				$limit_day = (WPFB_Core::$settings->traffic_day * 1048576);
				$limit_month = (WPFB_Core::$settings->traffic_month * 1073741824);
			?>
			<tr>
				<td><?php
					if($limit_day > 0)
						self::ProgressBar($traffic_stats['today'] / $limit_day, WPFB_Output::FormatFilesize($traffic_stats['today']) . '/' . WPFB_Output::FormatFilesize($limit_day));
					else
						echo WPFB_Output::FormatFilesize($traffic_stats['today']);
				?></td>
				<th scope="row"><?php _e('Today', WPFB); ?></th>
			</tr>
			<tr>
				<td><?php
					if($limit_month > 0)
						self::ProgressBar($traffic_stats['month'] / $limit_month, WPFB_Output::FormatFilesize($traffic_stats['month']) . '/' . WPFB_Output::FormatFilesize($limit_month));
					else
						echo WPFB_Output::FormatFilesize($traffic_stats['month']);
				?></td>
				<th scope="row"><?php _e('This Month', WPFB); ?></th>
			</tr>
			<tr>
				<td><?php echo WPFB_Output::FormatFilesize($wpdb->get_var("SELECT SUM(file_size) FROM $wpdb->wpfilebase_files")) ?></td>
				<th scope="row"><?php _e('Total File Size', WPFB); ?></th>
			</tr>	
			</table>
</div>
</div><!-- /col-right -->
			
<div id="col-left">
<div class="col-wrap">

			<h3><?php _e('Statistics', WPFB); ?></h3>
			<table class="wpfb-stats-table">
			<tr>
				<td><?php echo WPFB_File::GetNumFiles() ?></td>
				<th scope="row"><?php _e('Files', WPFB); ?></th>				
			</tr>
			<tr>
				<td><?php echo WPFB_Category::GetNumCats() ?></td>
				<th scope="row"><?php _e('Categories', WPFB); ?></th>
			</tr>
			<tr>
				<td><?php echo "".(int)$wpdb->get_var("SELECT SUM(file_hits) FROM $wpdb->wpfilebase_files") ?></td>
				<th scope="row"><?php _e('Downloads', WPFB); ?></th>
			</tr>
			</table>
</div>
</div><!-- /col-left -->

</div><!-- /col-container -->
</div>


<div>
<!-- <h2><?php _e('Tools'); ?></h2> -->
<?php

$cron_sync_desc = '';
if(WPFB_Core::$settings->cron_sync) {
	$cron_sync_desc .= __('Automatic sync is enabled. Cronjob scheduled hourly.');
	$last_sync_time	= intval(get_option(WPFB_OPT_NAME.'_cron_sync_time'));
	$cron_sync_desc .=  ($last_sync_time > 0) ? (" (".sprintf( __('Last cron sync on %1$s at %2$s.',WPFB), date_i18n( get_option( 'date_format'), $last_sync_time ), date_i18n( get_option( 'time_format'), $last_sync_time ) ).")") : '';
} else {
	$cron_sync_desc .= __('Cron sync is disabled.',WPFB);
}

$tools = array(
	 array(
		  'url' => add_query_arg(array('action' => 'sync', )),
		  'icon' => 'activity',
		  'label' => __('Sync Filebase',WPFB),
		  'desc' => __('Synchronises the database with the file system. Use this to add FTP-uploaded files.',WPFB).'<br />'.$cron_sync_desc		  
	)
);

?>
<div id="wpfb-tools">
<ul>
<?php foreach($tools as $id => $tool) {
	?>
	<li id="wpfb-tool-<?php echo $id; ?>"><a href="<?php echo $tool['url']; ?>" <?php if(!empty($tool['confirm'])) { ?> onclick="return confirm('<?php echo $tool['confirm']; ?>')" <?php } ?> class="button"><span style="background-image:url(<?php echo esc_attr(WPFB_PLUGIN_URI); ?>images/<?php echo $tool['icon']; ?>.png)"></span><?php echo $tool['label']; ?></a></li>
<?php } ?>
</ul>
<?php foreach($tools as $id => $tool) { ?>	
<div id="wpfb-tool-desc-<?php echo $id; ?>" class="tool-desc">
	<?php echo $tool['desc']; ?>
</div>
<?php } ?>
<script>
jQuery('#wpfb-tools li').mouseenter(function(e) {
	jQuery('#wpfb-tools .tool-desc').hide();
	jQuery('#wpfb-tool-desc-'+this.id.substr(10)).show();
});
</script>


				
<?php if(WPFB_Core::$settings->tag_conv_req) { ?><p><a href="<?php echo add_query_arg('action', 'convert-tags') ?>" class="button"><?php _e('Convert old Tags',WPFB)?></a> &nbsp; <?php printf(__('Convert tags from versions earlier than %s.',WPFB), '0.2.0') ?></p> <?php } ?>
<!--  <p><a href="<?php echo add_query_arg('action', 'add-urls') ?>" class="button"><?php _e('Add multiple URLs',WPFB)?></a> &nbsp; <?php _e('Add multiple remote files at once.', WPFB); ?></p>
-->
</div>

<?php
	if(WPFB_admin::CurUserCanUpload()) {		
		WPFB_Admin::PrintForm('file', null, array('exform' => $exform));	

		
	}
?>
			
		<?php
			if(!$show_how_start) // display how start here if its hidden
				wpfb_call('AdminHowToStart', 'Display');
		?>
			
			<h2><?php _e('About'); ?></h2>
			<p>
			<?php echo WPFB_PLUGIN_NAME . ' ' . WPFB_VERSION ?> by Fabian Schlieper <a href="http://fabi.me/">
			<?php if(strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') === false) { ?><img src="http://fabi.me/misc/wpfb_icon.gif?lang=<?php if(defined('WPLANG')) {echo WPLANG;} ?>" alt="" /><?php } ?> fabi.me</a><br/>
			Includes the great file analyzer <a href="http://www.getid3.org/">getID3()</a> by James Heinrich.<br />
			Tools Icons by <a href="http://www.icondeposit.com/">Matt Gentile</a>.
			</p>
			<?php if(current_user_can('edit_files')) { ?>
			<p><a href="<?php echo admin_url('plugins.php?wpfb-uninstall=1') ?>" class="button"><?php _e('Completely Uninstall WP-Filebase') ?></a></p>
				<?php
			}
			break;
			
	case 'convert-tags':
		?><h2><?php _e('Tag Conversion'); ?></h2><?php
		if(empty($_REQUEST['doit'])) {
			echo '<div class="updated"><p>';
			_e('<strong>Important:</strong> before updating, please <a href="http://codex.wordpress.org/WordPress_Backups">backup your database and files</a>. For help with updates, visit the <a href="http://codex.wordpress.org/Updating_WordPress">Updating WordPress</a> Codex page.');
			echo '</p></div>';
			echo '<p><a href="' . add_query_arg('doit',1) . '" class="button">' . __('Continue') . '</a></p>';
			break;
		}
		$result = wpfb_call('Setup', 'ConvertOldTags');
		?>
		<p><?php printf(__('%d Tags in %d Posts has been converted.'), $result['n_tags'], count($result['tags'])) ?></p>
		<ul>
		<?php
		if(!empty($result['tags'])) foreach($result['tags'] as $post_title => $tags) {
			echo "<li><strong>".esc_html($post_title)."</strong><ul>";
			foreach($tags as $old => $new) {
				echo "<li>$old =&gt; $new</li>";
			}
			echo "</ul></li>";
		}		
		?>
		</ul>
		<?php
		if(!empty($result['errors'])) { ?>	
		<h2><?php _e('Errors'); ?></h2>
		<ul><?php foreach($result['errors'] as $post_title => $err) echo "<li><strong>".esc_html($post_title).": </strong> ".esc_html($err)."<ul>"; ?></ul>		
		<?php
		}
		$opts = WPFB_Core::GetOpt();
		unset($opts['tag_conv_req']);
		update_option(WPFB_OPT_NAME, $opts);
		WPFB_Core::$settings = (object)$opts;
		
		break; // convert-tags
		
		
		case 'del':
				if(!empty($_REQUEST['files']) && WPFB_Admin::CurUserCanUpload()) {
				$ids = explode(',', $_REQUEST['files']);
				$nd = 0;
				foreach($ids as $id) {
					$id = intval($id);					
					if(($file=WPFB_File::GetFile($id))!=null && $file->CurUserCanEdit()) {
						$file->Remove(true);
						$nd++;
					}
				}
				WPFB_File::UpdateTags();		
				
				echo '<div id="message" class="updated fade"><p>'.sprintf(__('%d Files removed'), $nd).'</p></div>';
			}
			if(!empty($_REQUEST['cats']) && WPFB_Admin::CurUserCanCreateCat()) {
				$ids = explode(',', $_REQUEST['cats']);
				$nd = 0;
				foreach($ids as $id) {
					$id = intval($id);					
					if(($cat=WPFB_Category::GetCat($id))!=null) {
						$cat->Delete();
						$nd++;
					}
				}		
				
				echo '<div id="message" class="updated fade"><p>'.sprintf(__('%d Categories removed'), $nd).'</p></div>';
			}
	
		case 'sync':
			echo '<h2>'.__('Synchronisation').'</h2>';
			wpfb_loadclass('Sync');			
			$result = WPFB_Sync::Sync(!empty($_GET['hash_sync']), true);
			if(!is_null($result))
				WPFB_Sync::PrintResult($result);

		
			if(empty($_GET['hash_sync']))
				echo '<p><a href="' . add_query_arg('hash_sync',1) . '" class="button">' . __('Complete file sync', WPFB) . '</a> ' . __('Checks files for changes, so more reliable but might take much longer. Do this if you uploaded/changed files with FTP.', WPFB) . '</p>';			
			
		break; // sync
		
		
		
			
			
		case 'batch-upload':
			wpfb_loadclass('BatchUploader');
			$batch_uploader = new WPFB_BatchUploader();
			$batch_uploader->Display();
			break;
		
	case 'reset-hits':
		global $wpdb;
		$n = 0;
		if(current_user_can('manage_options'))
			$n = $wpdb->query("UPDATE `$wpdb->wpfilebase_files` SET file_hits = 0 WHERE 1=1");
		echo "<p>";
		printf(__('Done. %d Files affected.'), $n);
		echo "</p>";
		break;
		
		
	} // switch	
	?>
</div> <!-- wrap -->
<?php
}

static function ProgressBar($progress, $label)
{
	$progress = round(100 * $progress);
	echo "<div class='wpfilebase-progress'><div class='progress'><div class='bar' style='width: $progress%'></div></div><div class='label'><strong>$progress %</strong> ($label)</div></div>";
}

static function PluginHasBeenUsedAWhile()
{
	global $wpdb;
	if(WPFB_File::GetNumFiles() < 5) return false;
	$first_file_time = mysql2date('U',$wpdb->get_var("SELECT file_date FROM $wpdb->wpfilebase_files ORDER BY file_date ASC LIMIT 1"));
	return ($first_file_time > 1 && (time()-$first_file_time) > (86400 * 4)); // 4 days	
}
}
