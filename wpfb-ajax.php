<?php

define('DOING_AJAX', true);

require_once('wpfb-load.php');

function wpfb_print_json($obj) {
	//if(!WP_DEBUG)
	@header('Content-Type: application/json; charset=' . get_option('blog_charset'));
	$json = json_encode($obj);
	@header('Content-Length: '.strlen($json));
	echo $json;
	exit;
}

if(!isset($_REQUEST['action']))
	die('-1'); 

@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
if(!WP_DEBUG) {
	send_nosniff_header();
	error_reporting(0);
}

$_REQUEST = stripslashes_deep($_REQUEST);
$_POST = stripslashes_deep($_POST);
$_GET = stripslashes_deep($_GET);

switch ( $action = $_REQUEST['action'] ) {
	
	case 'tree':
		$type = $_REQUEST['type'];
		
		wpfb_loadclass('Core','File','Category','Output');
		
		// fixed exploit, thanks to Miroslav Stampar http://unconciousmind.blogspot.com/
		$root_id = (empty($_REQUEST['root']) || $_REQUEST['root'] == 'source') ? 0 : (is_numeric($_REQUEST['root']) ? intval($_REQUEST['root']) : intval(substr(strrchr($_REQUEST['root'],'-'),1)));
		$parent_id = ($root_id == 0) ? intval($_REQUEST['base']) : $root_id;
		
		wpfb_print_json(WPFB_Output::GetTreeItems($parent_id, $type, array(
			 'cats_only'	=> (!empty($_REQUEST['cats_only']) && $_REQUEST['cats_only'] != 'false'),
			 'exclude_attached' => (!empty($_REQUEST['exclude_attached']) && $_REQUEST['exclude_attached'] != 'false'),
			 
			 'onselect'		=> (!empty($_REQUEST['onselect'])) ? $_REQUEST['onselect'] : null,
			 'cat_id_fmt'	=> empty($_REQUEST['cat_id_fmt']) ? null : wp_strip_all_tags($_REQUEST['cat_id_fmt']),
			 'file_id_fmt' => empty($_REQUEST['file_id_fmt']) ? null : wp_strip_all_tags($_REQUEST['file_id_fmt']),		 
		)));		
		exit;

	
	case 'delete':
		wpfb_loadclass('File','Category');
		$file_id = intval($_REQUEST['file_id']);		
		if(!current_user_can('upload_files') || $file_id <= 0 || ($file = WPFB_File::GetFile($file_id)) == null)
			die('-1');

		$file->Remove();
		die('1');
		
	case 'tpl-sample':
		global $current_user;
		if(!current_user_can('edit_posts')) die('-1');
		
		wpfb_loadclass('File','Category', 'TplLib', 'Output');
		
		if(isset($_POST['tpl']) && empty($_POST['tpl'])) exit;
		
		$cat = new WPFB_Category(array(
			'cat_id' => 0,
			'cat_name' => 'Example Category',
			'cat_description' => 'This is a sample description.',
			'cat_folder' => 'example',
			'cat_num_files' => 0, 'cat_num_files_total' => 0
		));
		$cat->Lock();
		
		$file = new WPFB_File(array(
			'file_name' => 'example.pdf',
			'file_display_name' => 'Example Document',
			'file_size' => 1024*1024*1.5,
			'file_date' => gmdate('Y-m-d H:i:s', time()),
			'file_hash' => md5(''),
			'file_thumbnail' => 'thumb.png',
			'file_description' => 'This is a sample description.',
			'file_version' => WPFB_VERSION,
			'file_author' => $user_identity,
			'file_hits' => 3,
			'file_added_by' => $current_user->ID
		));
		$file->Lock();
		
		if(!empty($_POST['type']) && $_POST['type'] == 'cat')
			$item = $cat;
		elseif(!empty($_POST['type']) && $_POST['type'] == 'list')
		{
			wpfb_loadclass('ListTpl');
			$tpl = new WPFB_ListTpl('sample', $_REQUEST['tpl']);
			echo $tpl->Sample($cat, $file);
			exit;
		}
		elseif(empty($_POST['file_id']) || ($item = WPFB_File::GetFile($_POST['file_id'])) == null || !$file->CurUserCanAccess(true))
			$item = $file;
		else
			die('-1');
		
		$tpl = empty($_POST['tpl']) ? null : WPFB_TplLib::Parse($_POST['tpl']);
		echo do_shortcode($item->GenTpl($tpl, 'ajax'));
		exit;
		
	case 'fileinfo':
		wpfb_loadclass('File','Category');
		if(empty($_REQUEST['url']) && (empty($_REQUEST['id']) || !is_numeric($_REQUEST['id']))) die('-1');
		$file = null;
		
		if(!empty($_REQUEST['url'])) {
			$url = $_REQUEST['url'];		
			$matches = array();	
			if(preg_match('/\?wpfb_dl=([0-9]+)$/', $url, $matches) || preg_match('/#wpfb-file-([0-9]+)$/', $url, $matches))
				$file = WPFB_File::GetFile($matches[1]);
			else {
				$base = trailingslashit(get_option('home')).trailingslashit(WPFB_Core::$settings->download_base);
				$path = substr($url, strlen($base));
				$path_u = substr(urldecode($url), strlen($base));			
				$file = WPFB_File::GetByPath($path);
				if($file == null) $file = WPFB_File::GetByPath($path_u);
			}
		} else {
			$file = WPFB_File::GetFile((int)$_REQUEST['id']);
		}
		
		if($file != null && $file->CurUserCanAccess(true)) {
			wpfb_print_json(array(
				'id' => $file->GetId(),
				'url' => $file->GetUrl(),
				'path' => $file->GetLocalPathRel()
			));			
		} else {
			echo '-1';
		}
		exit;
		
	case 'catinfo':
			wpfb_loadclass('Category','Output');
			if(/*empty($_REQUEST['url']) && */(empty($_REQUEST['id']) || !is_numeric($_REQUEST['id']))) die('-1');
			$cat = WPFB_Category::GetCat((int)$_REQUEST['id']);
		
			if($cat != null && $cat->CurUserCanAccess(true)) {
				wpfb_print_json(array(
						'id' => $cat->GetId(),
						'url' => $cat->GetUrl(),
						'path' => $cat->GetLocalPathRel(),
						'roles' => $cat->GetReadPermissions(),
						'roles_str' => WPFB_Output::RoleNames($cat->GetReadPermissions(), true)
				));
			} else {
				echo '-1';
			}
			exit;
		
	case 'postbrowser':
		if(!current_user_can('edit_posts')) {
			wpfb_print_json(array(array('id'=>'0','text'=>__('Cheatin&#8217; uh?'), 'classes' => '','hasChildren'=>false)));
			exit;
		}
		
		$id = (empty($_REQUEST['root']) || $_REQUEST['root'] == 'source') ? 0 : intval($_REQUEST['root']);
		$onclick = empty($_REQUEST['onclick']) ? '' : $_REQUEST['onclick'];
			
		$args = array('hide_empty' => 0, 'hierarchical' => 1, 'orderby' => 'name', 'parent' => $id);
		$terms = get_terms('category', $args );
		
		$items = array();	
		foreach($terms as &$t) {
			$items[] = array(
				'id' => $t->term_id, 'text'=> esc_html($t->name), 'classes' => 'folder',
				'hasChildren' => ($t->count > 0)
			);
		}
		
		$terms = get_posts(array(
			'numberposts' => 0, 'nopaging' => true,
			//'category' => $id,
			'category__in' => array($id), // undoc: dont list posts of child cats!
			'orderby' => 'title', 'order' => 'ASC',
			'post_status' => 'any' // undoc: get private posts aswell
		));
		
		if($id == 0)
			$terms = array_merge($terms, get_pages(/*array('parent' => $id)*/));
			
		foreach($terms as $t) {
			$post_title = stripslashes(get_the_title($t->ID));
			if(empty($post_title)) $post_title = $t->ID;
			$items[] = array('id' => $t->ID, 'classes' => 'file',
			'text'=> ('<a href="javascript:'.sprintf($onclick,$t->ID, str_replace('\'','\\\'',/*htmlspecialchars*/$post_title)).'">'.$post_title.'</a>'));
		}

		wpfb_print_json($items);
		exit;
	case 'toggle-context-menu':
		if(!current_user_can('upload_files')) die('-1');
		WPFB_Core::UpdateOption('file_context_menu', !WPFB_Core::$settings->file_context_menu);
		die('1');
		
	case 'set-user-setting':
		if(!current_user_can('manage_categories') || empty($_REQUEST['name'])) die('0');
		update_user_option(get_current_user_id(), 'wpfb_set_'.$_REQUEST['name'], stripslashes($_REQUEST['value']));
		echo '1';
		exit;
		
	case 'get-user-setting':
		if(!current_user_can('manage_categories') || empty($_REQUEST['name'])) die('-1');
		wpfb_print_json(get_user_option('wpfb_set_'.$_REQUEST['name']));
		exit;
		
	case 'attach-file':
		wpfb_loadclass('File');
		if(!current_user_can('upload_files') || empty($_REQUEST['post_id']) || empty($_REQUEST['file_id']) || !($file = WPFB_File::GetFile($_REQUEST['file_id'])))
			die('-1');
		$file->SetPostId($_REQUEST['post_id']);
		die('1');
		
	case 'ftag_proposal':
		$tag = @$_REQUEST['tag'];
		$tags = (array)get_option(WPFB_OPT_NAME.'_ftags'); // sorted!
		$props = array();
		if(($n = count($tags)) > 0) {
			$ks = array_keys($tags);		
			for($i = 0; $i < $n; $i++) {
				if(stripos($ks[$i], $tag) === 0) {
					while($i < $n && stripos($ks[$i], $tag) === 0) {
						$props[] = array('t' => $ks[$i], 'n' => $tags[$ks[$i]]);
						$i++;
					}
					//break;
				}
			}
		}
		wpfb_print_json($props);
		exit;
		
		
}