<?php

class WPFB_AdmInstallExt {
	
	static function PluginsApiFilter($res, $action, $args)
	{
		global $user_ID;
		$res = wpfb_call('ExtensionLib','QueryAvailableExtensions');
		if($user_ID && !empty($res->info['tag_time']))
			update_user_option($user_ID, 'wpfb_ext_tagtime', $res->info['tag_time']);	
		return $res;
	}
	
	static function PluginActionLinksFilter($action_links, $plugin) {
		$plugin = (object)$plugin;
		if(strpos($action_links[0],'button-disabled') === false) {
			if(!empty($plugin->dependencies_unmet)) {
				$action_links[0] = '<a class="button" onclick="return confirm(\'This extension requires WP-Filebase Pro. Do you want to learn more about an upgrade?\');" href="' . esc_attr ($plugin->dependencies_url) . '" target="_blank" aria-label="' . esc_attr( sprintf( __( 'Install extension %s' ), $plugin->name ) ) . '">'. __( 'Install' )  . '</a>';
			} elseif(!empty($plugin->need_to_buy)) {
				$action_links[0] = '<a class="buy-now button button-primary" href="' . esc_attr ($plugin->buy_url) . '" target="_blank" aria-label="' . esc_attr( sprintf( __( 'Buy extension %s' ), $plugin->name ) ) . '">'. sprintf( __( 'Buy now (%s)' ), $plugin->license_price ) . '</a>';
			} elseif(!empty($plugin->license_required)) {
				$action_links[0] = '<a class="buy-now button thickbox" href="' . esc_attr ($plugin->add_url) . '" data-title="' . esc_attr( sprintf( __( 'Add extension %s' ), $plugin->name ) ) . '">'. __( 'Add License' ) . '</a>';
			}
		}
		if(!empty($plugin->need_to_buy))
			$action_links[1] = '<a href="' . esc_attr ($plugin->homepage) . '" class="no_thickbox" target="_blank">'.__('More Details').'</a>';
		
		if(!empty($plugin->requires_pro)) {
			$action_links[] = '<span class="wp-ui-notification wpfb-pro" title="This extension requires WP-Filebase Pro">pro</span>';
		}
		return $action_links;
	}
	
	static function Display()
	{
		add_filter( 'plugins_api', array(__CLASS__, 'PluginsApiFilter'), 10, 3 ); 
		add_filter( 'plugin_install_action_links', array(__CLASS__, 'PluginActionLinksFilter'), 10, 2 );
		add_filter( 'install_plugins_nonmenu_tabs', create_function('$tabs', '$tabs[]="new";return $tabs;'));
		self::DisplayInstallPlugins();
	}
	
	static function DisplayInstallPlugins() {
if ( ! current_user_can('install_plugins') )
	wp_die(__('You do not have sufficient permissions to install plugins on this site.'));



global $tab;
$tab = $_GET['tab'] = 'new'; // required for list table (in 3.5.1)

$wp_list_table = _get_list_table('WP_Plugin_Install_List_Table');
$pagenum = $wp_list_table->get_pagenum();
$wp_list_table->orderby = 'order';
$wp_list_table->prepare_items();
$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );

if ( $pagenum > $total_pages && $total_pages > 0 ) {
	WPFB_AdminLite::JsRedirect( add_query_arg( 'paged', $total_pages ) );
	exit;
}

$title = __( 'Add Extensions' );

wp_print_scripts( 'plugin-install' );

?>
<style type="text/css" media="screen">
	.vers.column-rating, .column-downloaded { display: none; }
	#TB_ajaxWindowTitle { display: none; }
</style>

<div class="wrap">
<h2><?php	echo esc_html( $title );	?></h2>

<?php
//$wp_list_table->views();
//echo '<br class="clear" />';
?>
	<form id="plugin-filter" action="" method="post">
		<?php $wp_list_table->display(); ?>
	</form>
</div>
<script>
	jQuery('a.buy-now').click(function(e) {
		if(jQuery(this).text() === 'Refresh') {
			window.location.reload();
			return false;
		}
		jQuery(this).text('Refresh');
		return true;
	});
	</script>
<?php
	}
}
