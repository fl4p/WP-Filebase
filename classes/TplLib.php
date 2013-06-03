<?php
class WPFB_TplLib {
static function Parse($tpl)
{
	if(is_array($tpl))
	{
		foreach(array_keys($tpl) as $i)
			$tpl[$i] = self::Parse($tpl[$i]);
		return $tpl;
	}
	
	
	
	// remove existing onclicks
	$tpl = preg_replace(array('/<a\s+([^>]*)onclick=".+?"\s+([^>]*)href="%file_url%"/i', '/<a\s+([^>]*)href="%file_url%"\s+([^>]*)onclick=".+?"/i'), '<a href="%file_url%" $1$2', $tpl);
	
	// remove cat anchors
	$tpl = str_replace('%cat_url%#wpfb-cat-%cat_id%','%cat_url%',$tpl);
	
	// remote slash after wpfb_url
	$tpl = str_replace("%wpfb_url%/", "%wpfb_url%", $tpl);
	
	// since 0.2.0 the onclick is set via jQuery!
	//add dl js
	//$tpl = preg_replace('/<a ([^>]*)href="%file_url%"/i', '<a $1href="%file_url%" onclick="wpfilebase_dlclick(%file_id%, \'%file_url_rel%\')"', $tpl);

	//escape
	$tpl = str_replace("'", "\\'", $tpl);
	
	// parse if's
	$tpl = preg_replace(
	'/<!-- IF (.+?) -->([\s\S]+?)<!-- ENDIF -->/e',
	"'\\'.(('.". __CLASS__ ."::ParseTplExp('$1').')?(\\''.". __CLASS__ ."::ParseTplIfBlock('$2').'\\')).\\''", $tpl);
	
	// parse translation texts
	$tpl = preg_replace('/([^\w])%\\\\\'(.+?)\\\\\'%([^\w])/', '$1\'.__(__(\'$2\', WPFB)).\'$3', $tpl);

	// parse special vars
	$tpl = str_replace('%post_id%', '\'.get_the_ID().\'', $tpl);
	$tpl = str_replace('%wpfb_url%', '\'.(WPFB_PLUGIN_URI).\'', $tpl);
	
	// parse variables
	$tpl = preg_replace('/%([a-z0-9_\/:]+?)%/i', '\'.$f->get_tpl_var(\'$1\').\'', $tpl);
	
	// remove html comments (no multiline comments!)
	$tpl = preg_replace('/\s<\!\-\-[^\n]+?\-\->\s/', ' ', $tpl);
	
	
	$tpl = "'$tpl'";
	
	// cleanup
	$tpl = str_replace('.\s*\'\'', '', $tpl);
	
	return $tpl;
}

static function ParseTplExp($exp)
{
	// parse special vars
	$exp = str_replace('%post_id%', 'get_the_ID()', $exp);
	
	// remove critical functions TODO: still a bit unsecure, only allow some functions
	$exp = str_replace(array('eval','mysql_query', 'mysql', '$wpdb', 'fopen', 'readfile', 'include(','include_once','require(','require_once','file_get_contents','file_put_contents','copy(','unlink','rename('), '', $exp);
	
	$exp = preg_replace('/%([a-z0-9_\/]+?)%/i', '($f->get_tpl_var(\'$1\'))', $exp);
	$exp = preg_replace('/([^\w])AND([^\w])/', '$1&&$2', $exp);
	$exp = preg_replace('/([^\w])OR([^\w])/', '$1||$2', $exp);
	$exp = preg_replace('/([^\w])NOT([^\w])/', '$1!$2', $exp);
	
	// unescape "
	$exp = stripslashes($exp);
	
	return $exp;
}

static function ParseTplIfBlock($block)
{
	static $s = '<!-- ELSE -->';
	static $r = '\'):(\'';
	if(strpos($block, $s) === false)
		$block .= $r;
	else
		$block = str_replace($s, $r, $block);
	
	// unescape "
	$block = str_replace('\"', '"', $block);
	
	return $block;
}


static function Check($tpl)
{	
	$result = array('error' => false, 'msg' => '', 'line' => '');
	
	wpfb_loadclass('File');
	$f = new WPFB_File();
	$tpl = 'return (' . $tpl . ');';
	
	if(!@eval($tpl))
	{
		$result['error'] = true;
		
		$err = error_get_last();
		if(!empty($err))
		{
			$result['msg'] = $err['message'];
			$result['line'] = $err['line'];
		}
	}
	
	return $result;
}

}
?>