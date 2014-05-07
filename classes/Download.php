<?php
class WPFB_Download {
static function RefererCheck()
{
	// fix (FF?): avoid caching of redirections so the file cannot be downloaded anymore
	if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) || !empty($_COOKIE))
		return true;
		
	if(empty($_SERVER['HTTP_REFERER']))
		return ((bool)WPFB_Core::$settings->accept_empty_referers);
		
	$referer = @parse_url($_SERVER['HTTP_REFERER']);		
	$referer = $referer['host'];
	
	$allowed_referers = explode("\n", WPFB_Core::$settings->allowed_referers);
	$allowed_referers[] = get_option('home');
	
	foreach($allowed_referers as $ar)
	{
		if(empty($ar))
			continue;
		
		$ar_host = @parse_url($ar);
		$ar_host = $ar_host['host'];
		if(@strpos($referer, $ar) !== false || @strpos($referer, $ar_host) !== false)
			return true;
	}
	
	return false;
}

static function AddTraffic($bytes)
{
	$traffic = wpfb_call('Misc','GetTraffic');
	$traffic['month'] = $traffic['month'] + $bytes;
	$traffic['today'] = $traffic['today'] + $bytes;	
	$traffic['time'] = time();
	WPFB_Core::UpdateOption('traffic_stats', $traffic);
}

static function CheckTraffic($file_size)
{
	$traffic = wpfb_call('Misc','GetTraffic');
	
	$limit_month = (WPFB_Core::$settings->traffic_month * 1073741824); //GiB
	$limit_day = (WPFB_Core::$settings->traffic_day * 1048576); // MiB
	
	return ( ($limit_month == 0 || ($traffic['month'] + $file_size) < $limit_month) && ($limit_day == 0 || ($traffic['today'] + $file_size) < $limit_day) );
}


static function GetFileType($name)
{
	$pos = strrpos($name, '.');
	if($pos !== false) $name = substr($name, $pos + 1);
	switch (strtolower($name))
	{
		case 'zip':		return 'application/zip';
		case 'rar':		return 'application/x-rar-compressed';
		case 'bin':
		case 'dms':
		case 'lha':
		case 'lzh':
		case 'exe':
		case 'class':
		case 'so':
		case 'dll':		return 'application/octet-stream';   
		case 'ez':  	return 'application/andrew-inset';
		case 'hqx':		return 'application/mac-binhex40';
		case 'cpt':		return 'application/mac-compactpro';
		case 'docx':	return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'; //wtf? ;)
		case 'doc':		return 'application/msword';
		case 'oda':		return 'application/oda';
		case 'pdf':		return 'application/pdf';
		case 'ai':
		case 'eps':
		case 'ps':		return 'application/postscript';
		case 'smi':
		case 'smil':	return 'application/smil';
		case 'xls':		return 'application/vnd.ms-excel';
		case 'ppt':		return 'application/vnd.ms-powerpoint';
		case 'wbxml':	return 'application/vnd.wap.wbxml';
		case 'wmlc':	return 'application/vnd.wap.wmlc';
		case 'wmlsc':	return 'application/vnd.wap.wmlscriptc';
		case 'bcpio':	return 'application/x-bcpio';
		case 'vcd':		return 'application/x-cdlink';
		case 'pgn':		return 'application/x-chess-pgn';
		case 'cpio':	return 'application/x-cpio';
		case 'csh':		return 'application/x-csh';
		case 'dcr':
		case 'dir':
		case 'dxr':		return 'application/x-director';
		case 'dvi':		return 'application/x-dvi';
		case 'spl':		return 'application/x-futuresplash';
		case 'gtar':	return 'application/x-gtar';
		case 'hdf':		return 'application/x-hdf';
		case 'js':  	return 'application/x-javascript';
		case 'skp':
		case 'skd':
		case 'skt':
		case 'skm':		return 'application/x-koan';
		case 'latex':	return 'application/x-latex';
		case 'nc':
		case 'cdf':		return 'application/x-netcdf';
		case 'sh':		return 'application/x-sh';
		case 'shar':	return 'application/x-shar';
		case 'swf':		return 'application/x-shockwave-flash';
		case 'sit':		return 'application/x-stuffit';
		case 'sv4cpio':	return 'application/x-sv4cpio';
		case 'sv4crc':	return 'application/x-sv4crc';
		case 'tar':		return 'application/x-tar';
		case 'tcl':		return 'application/x-tcl';
		case 'tex':		return 'application/x-tex';
		case 'texinfo':
		case 'texi':	return 'application/x-texinfo';
		case 't':
		case 'tr':
		case 'roff':	return 'application/x-troff';
		case 'man':		return 'application/x-troff-man';
		case 'me':		return 'application/x-troff-me';
		case 'ms':		return 'application/x-troff-ms';
		case 'ustar':	return 'application/x-ustar';
		case 'src':		return 'application/x-wais-source';
		case 'torrent': return 'application/x-bittorrent';
		case 'xhtml':
		case 'xht':		return 'application/xhtml+xml';
		case 'au':  	return 'audio/basic';
		case 'snd':		return 'audio/basic';
		case 'mid':		return 'audio/midi';
		case 'midi':	return 'audio/midi';
		case 'kar':		return 'audio/midi';
		case 'mpga':
		case 'mp2':
		case 'mp3':		return 'audio/mpeg';
		case 'mp4':		return 'video/mp4';
		case 'aif':
		case 'aiff':
		case 'aifc':	return 'audio/x-aiff';
		case 'm3u':		return 'audio/x-mpegurl';
		case 'ram':
		case 'rm':		return 'audio/x-pn-realaudio';
		case 'rpm':		return 'audio/x-pn-realaudio-plugin';
		case 'ra':		return 'audio/x-realaudio';
		case 'wav':		return 'audio/x-wav';
		case 'pdb':		return 'chemical/x-pdb';
		case 'xyz':		return 'chemical/x-xyz';
		case 'bmp':		return 'image/bmp';
		case 'gif':		return 'image/gif';
		case 'ief':		return 'image/ief';
		case 'jpeg':
		case 'jpg':
		case 'jpe':		return 'image/jpeg';
		case 'png':		return 'image/png';
		case 'tiff':
		case 'tif':		return 'image/tiff';
		case 'djvu':
		case 'djv':		return 'image/vnd.djvu';
		case 'wbmp':	return 'image/vnd.wap.wbmp';
		case 'ras':		return 'image/x-cmu-raster';
		case 'ico':		return 'image/x-icon';
		case 'pnm':		return 'image/x-portable-anymap';
		case 'pbm':		return 'image/x-portable-bitmap';
		case 'pgm':		return 'image/x-portable-graymap';
		case 'ppm':		return 'image/x-portable-pixmap';
		case 'rgb':		return 'image/x-rgb';
		case 'xbm':		return 'image/x-xbitmap';
		case 'xpm':		return 'image/x-xpixmap';
		case 'xwd':		return 'image/x-xwindowdump';
		case 'svg':		return 'image/svg+xml';
		case 'igs':
		case 'iges':	return 'model/iges';
		case 'msh':
		case 'mesh':
		case 'silo':	return 'model/mesh';
		case 'wrl':
		case 'vrml':	return 'model/vrml';
		case 'css':		return 'text/css';
		case 'html':
		case 'htm':		return 'text/html';
		case 'asc':
		case 'c':
		case 'cc':
		case 'cs':
		case 'h':
		case 'hh':
		case 'cpp':
		case 'hpp':
		case 'strings': /*since 0.2.0*/
		case 'm': /*since 0.2.0*/
		case 'log':
		case 'txt':		return 'text/plain';
		case 'rtx':		return 'text/richtext';
		case 'rtf':		return 'text/rtf';
		case 'sgml':
		case 'sgm':		return 'text/sgml';
		case 'tsv':		return 'text/tab-separated-values';
		case 'wml':		return 'text/vnd.wap.wml';
		case 'wmls':	return 'text/vnd.wap.wmlscript';
		case 'etx':		return 'text/x-setext';
		case 'xml':
		case 'xsl':		return 'text/xml';
		case 'mpeg':
		case 'mpg':
		case 'mpe':		return 'video/mpeg';
		case 'qt':
		case 'mov':		return 'video/quicktime';
		case 'mxu':		return 'video/vnd.mpegurl';
		case 'avi':		return 'video/x-msvideo';
		case 'movie':	return 'video/x-sgi-movie';
		case 'asf':
		case 'asx':		return 'video/x-ms-asf';
		case 'wm':		return 'video/x-ms-wm';
		case 'wmv':		return 'video/x-ms-wmv';
		case 'wvx':		return 'video/x-ms-wvx';
		case 'flv':		return 'video/x-flv';
		case 'ice':		return 'x-conference/x-cooltalk';
		
		// ms office stuff http://technet.microsoft.com/en-us/library/ee309278%28office.12%29.aspx
		case 'docm':	return 'application/vnd.ms-word.document.macroEnabled.12';
		case 'dotx':	return 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
		case 'dotm':	return 'application/vnd.ms-word.template.macroEnabled.12';		
		case 'xlsx':	return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
		case 'xlsm':	return 'application/vnd.ms-excel.sheet.macroEnabled.12';
		case 'xltx':	return 'application/vnd.openxmlformats-officedocument.spreadsheetml.template';
		case 'xltm':	return 'application/vnd.ms-excel.template.macroEnabled.12';
		case 'xlsb':	return 'application/vnd.ms-excel.sheet.binary.macroEnabled.12';
		case 'xlam':	return 'application/vnd.ms-excel.addin.macroEnabled.12';
		case 'pptx':	return 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
		case 'pptm':	return 'application/vnd.ms-powerpoint.presentation.macroEnabled.12';
		case 'ppsx':	return 'application/vnd.openxmlformats-officedocument.presentationml.slideshow';
		case 'ppsm':	return 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12';
		case 'potx':	return 'application/vnd.openxmlformats-officedocument.presentationml.template';		
		case 'potm':	return 'application/vnd.ms-powerpoint.template.macroEnabled.12';
		case 'ppam':	return 'application/vnd.ms-powerpoint.addin.macroEnabled.12';
		case 'sldx':	return 'application/vnd.openxmlformats-officedocument.presentationml.slide';		
		case 'sldm':	return 'application/vnd.ms-powerpoint.slide.macroEnabled.12';		
		case 'one':
		case 'onetoc2':
		case 'onetmp':
		case 'onepkg':	return 'application/onenote';
		case 'thmx':	return 'application/vnd.ms-officetheme';
		
		case 'notebook':	return 'application/notebook';
			
		case 'gadget': return 'application/x-windows-gadget';
		
		default:
			if(function_exists('wp_get_mime_types')) {
				$res = wp_check_filetype(".$name", wp_get_mime_types());
				if($res['type'])
					return $res['type'];
			}
			return 'application/octet-stream';
	}
}

static function FileType2Ext($type)
{
	$pos = strrpos($type, ';');
	if($pos > 0) $type = substr($type, 0, $pos);
	switch(strtolower($type))
	{
		case 'application/zip': return 'zip';
		case 'application/x-rar-compressed': return 'rar';
		case 'audio/midi': return 'mid';
		case 'audio/mpeg': return 'mp3';
		case 'audio/x-aiff': return 'aif';
		case 'audio/x-mpegurl': return 'm3u';
		case 'audio/x-wav': return 'wav';
		case 'image/jpeg': return 'jpg';
		case 'image/png': return 'png';
		case 'image/tiff': return 'tiff';
		case 'image/x-icon': return 'ico';
		case 'text/css': return 'css';
		case 'text/html': return 'html';
		case 'text/plain': return 'txt';
		case 'text/rtf': return 'rtf';
		case 'text/xml': return 'xml';
		case 'video/mpeg': return 'mpeg';
		case 'video/x-flv': return 'flv';		
		
		case 'application/pdf':
		case 'application/x-pdf':
		case 'application/acrobat':
		case 'applications/vnd.pdf':
		case 'text/pdf':
		case 'text/x-pdf':				return 'pdf';
		
		case 'audio/3gpp':
		case 'video/3gpp': return '3gp';
		
		default: return null;
	}
}

// returns true if the download should not be streamed in the browser
static function ShouldSendDLHeader($file_path, $file_type)
{
	if(WPFB_Core::$settings->force_download)
		return true;
	
	$file_name = basename($file_path);
	$request_path = parse_url($_SERVER['REQUEST_URI']);
	$request_path = urldecode($request_path['path']);
	$request_file_name = basename($request_path);
	if($file_name == $request_file_name)
		return false;
		
	// types that can be viewed in the browser
	static $media = array('audio', 'image', 'text', 'video', 'application/pdf', 'application/x-shockwave-flash');	
	foreach($media as $m)
	{
		$p = strpos($file_type, $m);
		if($p !== false && $p == 0)
			return false;
	}	
	return true;
}

// returns true if range download should be supported for the specified file/file type
static function ShouldSendRangeHeader($file_path, $file_type)
{
	static $no_range_types = array('application/pdf', 'application/x-shockwave-flash');
	
	if(!WPFB_Core::$settings->range_download)
		return false;
		
	foreach($no_range_types as $t)
	{
		$p = strpos($file_type, $t);
		if($p !== false && $p == 0)
			return false;
	}	
	return true;
}

// this is the cool function which sends the file!
static function SendFile($file_path, $args=array())
{
	$defaults = array(
		'bandwidth' => 0,
		'etag' => null,
		'force_download' => false,
		'cache_max_age' => 0,
		'md5_hash' => null,
		'filename' => null
	);
	extract(wp_parse_args($args, $defaults), EXTR_SKIP);
	
	@ini_set('max_execution_time', '0');
	@set_time_limit(0);
	@error_reporting(0);
	while(@ob_end_clean()){}
	
	$no_cache = WPFB_Core::$settings->http_nocache && ($cache_max_age <= 0);
	
	@ini_set("zlib.output_compression", "Off");
	
	// remove some headers
	if(function_exists('header_remove')) {
		header_remove();
	} else {
		header("Expires: ");
		header("X-Pingback: ");
	}

	if(!@file_exists($file_path) || !is_file($file_path))
	{
		header('HTTP/1.x 404 Not Found');
		wp_die('File ' . basename($file_path) . ' not found!');
	}
	
	wpfb_loadclass('FileUtils');
	$size = WPFB_FileUtils::GetFileSize($file_path);
	$time = filemtime($file_path);
	$file_type = WPFB_Download::GetFileType($file_path);
	if(empty($etag))
		$etag = md5("$size|$time|$file_type");
	else $etag = trim($etag, '"');
	
	// set basic headers
	if($no_cache) {
		header("Cache-Control: no-cache, must-revalidate, max-age=0");
		header("Pragma: no-cache");
		header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
	} elseif($cache_max_age > 0)	
		header("Cache-Control: must-revalidate, max-age=$cache_max_age");	
		
	//header("Connection: close");
	//header("Keep-Alive: timeout=5, max=100");
	//header("Connection: Keep-Alive");
	
	header("Content-Type: " . $file_type . ((strpos($file_type, 'text/') !== false) ? '; charset=' : '')); 	// charset fix
	header("Last-Modified: " . gmdate("D, d M Y H:i:s", $no_cache ? time() : $time) . " GMT");
	
	if(!empty($md5_hash) && $md5_hash{0} != '#') { // check if fake md5
		$pmd5 = @pack('H32',$md5_hash);
		if(!empty($pmd5)) header("Content-MD5: ".@base64_encode($pmd5));
	}
	
	if(!$no_cache)
	{
		header("ETag: \"$etag\"");
		
		$if_mod_since = !empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
		$if_none_match = !empty($_SERVER['HTTP_IF_NONE_MATCH']) ? ($etag == trim($_SERVER['HTTP_IF_NONE_MATCH'], '"')) : false;
		
		if($if_mod_since || $if_none_match) {
			$not_modified = true;
			
			if($not_modified && $if_mod_since)
				$not_modified = (@strtotime($if_mod_since) >= $time);
				
			if($not_modified && $if_none_match)
				$not_modified = ($if_none_match == $etag);
				
			if($not_modified) {
				header("Content-Length: " . $size);
				header("HTTP/1.x 304 Not Modified");
				exit;
			}
		}
	}
	
	if(!($fh = @fopen($file_path, 'rb')))
		wp_die(__('Could not read file!', WPFB));
		
	$begin = 0;
	$end = $size-1;

	$http_range = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : '';
	if(!empty($http_range) && strpos($http_range, 'bytes=') !== false && strpos($http_range, ',') === false) // multi-range not supported (yet)!
	{
		$range = array_map('trim',explode('-', trim(substr($http_range, 6))));
		if(is_numeric($range[0])) {
			$begin = 0 + $range[0];
			if(is_numeric($range[1])) $end = 0 + $range[1];
		} else {
			$begin = $size - $range[1]; // format "-x": last x bytes
		}
	} else
		$http_range = '';
	
	if($begin > 0 || $end < ($size-1))
		header('HTTP/1.0 206 Partial Content');
	else
		header('HTTP/1.0 200 OK');
		
	$length = ($end-$begin+1);
	WPFB_Download::AddTraffic($length);
	
	
	if(WPFB_Download::ShouldSendRangeHeader($file_path, $file_type))
		header("Accept-Ranges: bytes");
	
	// content headers
	if(!empty($force_download) || WPFB_Download::ShouldSendDLHeader($file_path, $file_type) || !empty($filename)) {
		header("Content-Disposition: attachment; filename=\"" . (empty($filename) ? basename($file_path) : $filename) . "\"");
		header("Content-Description: File Transfer");
	}
	header("Content-Length: " . $length);
	if(!empty($http_range))
		header("Content-Range: bytes $begin-$end/$size");
	
	// clean up things that are not needed for download
	@session_write_close(); // disable blocking of multiple downloads at the same time
	global $wpdb;
	if(!empty($wpdb->dbh) && is_resource($wpdb->dbh))
		@mysql_close($wpdb->dbh);
	else
		@mysql_close();
	
	@ob_flush();
   @flush();
	
	//if(WPFB_Core::$settings->dl_destroy_session)
//		@session_destroy();
	
	// ready to send the file!
	
	if($begin > 0)
		fseek($fh,$begin,0);
	
	if(WPFB_Core::$settings->use_fpassthru) {
		fpassthru($fh);
	}
	else
	{
		$bandwidth = empty($bandwidth) ? 0 : (float)$bandwidth;
		if($bandwidth <= 0)
			$bandwidth = 1024 * 1024;

		$buffer_size = (int)(1024 * min($bandwidth, 64));

		// convert kib/s => bytes/ms
		$bandwidth *= 1024;
		$bandwidth /= 1000;

		$cur = $begin;
		
		while(!@feof($fh) && $cur <= $end && @connection_status() == 0)
		{		
			$nbytes = min($buffer_size, $end-$cur+1);
			$ts = microtime(true);

			print @fread($fh, $nbytes);
			@ob_flush();
			@flush();

			$dt = (microtime(true) - $ts) * 1000; // dt = time delta in ms		
			$st = ($nbytes / $bandwidth) - $dt;
			if($st > 0)
				usleep($st * 1000);			

			$cur += $nbytes;
		}
	}

	@fclose($fh);	
	return true;
}

static function getHttpStreamContentLength($s)
{
	$meta = stream_get_meta_data($s);
	foreach($meta['wrapper_data'] as $header)
	{
		if(stripos($header,'Content-Length:') === 0)
			return 0+trim(substr($header,15));
	}
	return -1;
}
static function SideloadFile($url, $dest_path, $progress_bar_or_callback=null)
{
	$rh = @fopen($url, 'rb'); // read binary
	if($rh === false)
		return array('error' => sprintf('Could not open URL %s!', $url). ' '.  print_r(error_get_last(), true));
	
	$total_size = self::getHttpStreamContentLength($rh);
	
	$fh = @fopen($dest_path, 'wb'); // write binary
	if($fh === false) {
		@fclose($rh);
		return array('error' => sprintf('Could not create file %s!', $dest_path));
	}
	
	$size = 0;
	while (!feof($rh)) {
	  if(($s=fwrite($fh, fread($rh, 65536))) === false) {
		@fclose($rh);
		@fclose($fh);
		return array('error' => sprintf('Writing to file %s failed!', $dest_path));	
	  }
	  $size += $s;
	  if(is_object($progress_bar_or_callback))
			$progress_bar_or_callback->set($size);
	  elseif(is_callable($progress_bar_or_callback))
		  call_user_func($progress_bar_or_callback, $size, $total_size);
	}
	fclose($rh);
	fclose($fh);
	
	if($size <= 0) return array('error' => 'File is empty.');
	
	return array('error' => false, 'size' => $size);
}

}