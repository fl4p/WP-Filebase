<?php

if(empty($_REQUEST['type']) || empty( $_REQUEST['tag']))
	exit;

require_once('wpfb-load.php');
wpfb_loadclass('Core');

$type = $_REQUEST['type'];
$tag = $_REQUEST['tag'];

$list = ($type == 'list');


wpfb_loadclass('Output','TplLib','ListTpl','AdminGuiTpls');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
<title><?php _e('Posts'); ?></title>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<?php wp_head(); ?>
</head>
<body class="single single-post">
<div id="page" class="site">
	<div id="main" class="wrapper">
		<div id="primary" class="site-content">
			<div id="content" role="main">
				<div class="entry-content">
				
<?php
if($list) {
	$tpl = WPFB_ListTpl::Get($tag);
	if(is_null($tpl))
		exit;
	
	echo do_shortcode($tpl->Sample(WPFB_AdminGuiTpls::$sample_cat, WPFB_AdminGuiTpls::$sample_file));
}
else
{
	$tpl_src = WPFB_Core::GetTpls($type, $tag);
	if(!is_string($tpl_src) || empty($tpl_src))
		exit;
	
	$table_found = (strpos($tpl_src, '<table') !== false);
	if(!$list && !$table_found && strpos($tpl_src, '<tr') !== false) {
		$tpl_src = "<table>$tpl_src</table>";
	}
	$item = ($type == 'cat') ? WPFB_AdminGuiTpls::$sample_cat : WPFB_AdminGuiTpls::$sample_file;
	echo do_shortcode($item->GenTpl(WPFB_TplLib::Parse($tpl_src), 'sample'));
}
?>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>