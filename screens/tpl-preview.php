<?php

// dont allow direct access and access from outside wp-admin context
if(!defined('ABSPATH') || !is_admin())
    exit;

if(empty($_REQUEST['type']) || empty( $_REQUEST['tag']))
	exit;

$type = $_REQUEST['type'];
$tag = $_REQUEST['tag'];

$list = ($type == 'list');

$style_no_top_space = ' style="padding-top: 0 !important; margin-top: 0 !important;" ';

wpfb_loadclass('Output','TplLib','ListTpl','AdminGuiTpls');

wp_enqueue_script(WPFB);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?> style="margin-top: 0 !important;">
<head>
<title><?php _e('Posts'); ?></title>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<?php wp_head(); ?>
</head>
<body class="single single-post" <?php echo $style_no_top_space; ?>>
<div id="page" class="site" <?php echo $style_no_top_space; ?>>
	<div id="main" class="wrapper" <?php echo $style_no_top_space; ?>>
		<div id="primary" class="site-content" <?php echo $style_no_top_space; ?>>
			<div id="content" role="main" <?php echo $style_no_top_space; ?>>
				<div class="entry-content" <?php echo $style_no_top_space; ?>>
<p>

	Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.

<?php
if($list) {
	$tpl = WPFB_ListTpl::Get($tag);
	if(is_null($tpl))
		die('no such template');
	
	echo do_shortcode($tpl->Sample(WPFB_AdminGuiTpls::$sample_cat, WPFB_AdminGuiTpls::$sample_file));
}
else
{

	$tpl_src = WPFB_Core::GetTpls($type, $tag);
	if(!is_string($tpl_src) || empty($tpl_src))
		die('no such template');

	$table_found = (strpos($tpl_src, '<table') !== false);
	if(!$list && !$table_found && strpos($tpl_src, '<tr') !== false) {
		$tpl_src = "<table>$tpl_src</table>";
	}

	WPFB_AdminGuiTpls::initSamples();
	$item = ($type == 'cat') ? WPFB_AdminGuiTpls::$sample_cat : WPFB_AdminGuiTpls::$sample_file;
	echo do_shortcode($item->GenTpl(WPFB_TplLib::Parse($tpl_src), 'sample'));
}
?>

	Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.


				</p>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>