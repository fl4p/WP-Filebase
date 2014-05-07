<?php
// describes an editable list template that can be saved
// WPFB_ListTpl::Generate generates output for front-end file lists
class WPFB_ListTpl {
	
	var $tag;
	var $header;
	var $footer;
	var $file_tpl_tag;
	var $cat_tpl_tag;
	var $current_list = null;
        
		
	static function Get($tag) {
		$tag = trim($tag, '\'');
		$tpls = get_option(WPFB_OPT_NAME.'_list_tpls');
		return isset($tpls[$tag]) ? new WPFB_ListTpl($tag, $tpls[$tag]) : null;
	}
	
	static function GetAll() {
		$tpls = get_option(WPFB_OPT_NAME.'_list_tpls');
		if(empty($tpls)) return array();
		foreach($tpls as $tag => $tpl)
			$tpls[$tag] = new WPFB_ListTpl($tag, $tpl);
		return $tpls;
	}
	
	function WPFB_ListTpl($tag=null, $data=null) {
		if(!empty($data)) {
			$vars = array_keys(get_class_vars(get_class($this)));
			foreach($vars as $var)
				if(isset($data[$var]))
					$this->$var = $data[$var];
		}				
		$this->tag = $tag;
	}
	
	function Save() {
		$tpls = get_option(WPFB_OPT_NAME.'_list_tpls');
		if(!is_array($tpls)) $tpls = array();
		$data = (array)$this;
		unset($data['tag'], $data['current_list']);
		$tpls[$this->tag] = $data; 
		update_option(WPFB_OPT_NAME.'_list_tpls', $tpls);
	}
	
	private function ParseHeaderFooter($str, $uid=null) {
		$str = preg_replace_callback('/%sort_?link:([a-z0-9_]+)%/i', array(__CLASS__, 'GenSortlink'), $str);
		
		if(strpos($str, '%search_form%') !== false) {
			wpfb_loadclass('Output');
			$str = str_replace('%search_form%', WPFB_Output::GetSearchForm("", $_GET), $str);
		}
		
		$str = preg_replace('/%print_?script:([a-z0-9_-]+)%/ie', __CLASS__.'::PrintScriptOrStyle(\'$1\', false)', $str);
		$str = preg_replace('/%print_?style:([a-z0-9_-]+)%/ie', __CLASS__.'::PrintScriptOrStyle(\'$1\', true)', $str);
	
		if(empty($uid)) $uid = uniqid();
		$str = str_replace('%uid%', $uid, $str);
		
		
		$count = 0;
		$str = preg_replace("/jQuery\((.+?)\)\.dataTable\s*\((.*?)\)(\.?.*?)\s*;/", 'jQuery($1).dataTable((function(options){/*%WPFB_DATA_TABLE_OPTIONS_FILTER%*/})($2))$3;', $str, -1, $count);
		if($count > 0)
		{
			$dataTableOptions = array();
			list($sort_field, $sort_dir) = wpfb_call('Output','ParseSorting', $this->current_list->file_order);			
			$file_tpl = WPFB_Core::GetTpls('file', $this->file_tpl_tag);
			if(($p = strpos($file_tpl, "%{$sort_field}%")) > 0)
			{
				// get the column index of field to sort
				$col_index = substr_count($file_tpl,"</t", 0, $p);				
				$dataTableOptions["aaSorting"] = array(array($col_index, strtolower($sort_dir)));
			}
			
			if($this->current_list->page_limit > 0)
					$dataTableOptions["iDisplayLength"] = $this->current_list->page_limit;
			
			
			$str = str_replace('/*%WPFB_DATA_TABLE_OPTIONS_FILTER%*/', 
	" var wpfbOptions = ".json_encode($dataTableOptions)."; ".
	" if('object' == typeof(options)) { for (var v in options) { wpfbOptions[v] = options[v]; } }".
	" return wpfbOptions; "
, $str);
		}	
		
		return $str;
	}
	
	static function PrintScriptOrStyle($script, $style=false)
	{
		ob_start();
		if($style) wp_print_styles($script);
		else  wp_print_scripts($script);
		return ob_get_clean();
	}
	
	static function GenSortlink($ms) {
		static $link;
		$by = $ms[1];
		if(empty($link)) {
			$link = remove_query_arg('wpfb_file_sort');
			$link .= ((strpos($link, '?') > 0)?'&':'?').'wpfb_file_sort=&';	
		}
		$desc = !empty($_GET['wpfb_file_sort']) && ($_GET['wpfb_file_sort'] == $by || $_GET['wpfb_file_sort'] == "<$by"); 
		return $link.($desc?'gt;':'lt;').$by;
	}
	
	function GenerateList(&$content, $categories, $list_args=null)
	{
		if(!empty($list_args)) {
			$this->current_list = (object)$list_args;
			unset($list_args);
		}
		
		$hia = WPFB_Core::$settings->hide_inaccessible;
		$sort = WPFB_Core::GetSortSql($this->current_list->file_order);
		
		if($this->current_list->page_limit > 0) { // pagination
			$page = (empty($_REQUEST['wpfb_list_page']) || $_REQUEST['wpfb_list_page'] < 1) ? 1 : intval($_REQUEST['wpfb_list_page']);
			$start = $this->current_list->page_limit * ($page-1);
		} else $start = -1;
		
		$search_term = empty($_GET['wpfb_s']) ? null : stripslashes($_GET['wpfb_s']);
		
		if($search_term || WPFB_Core::$file_browser_search) { // search
			wpfb_loadclass('Search');
			$where = WPFB_Search::SearchWhereSql(WPFB_Core::$settings->search_id3, $search_term);
		} else $where = '1=1';
		
		$num_total_files = 0;
		if(is_null($categories)) { // if null, just list all files!
			$files = WPFB_File::GetFiles2($where, $hia, $sort, $this->current_list->page_limit, $start);
			$num_total_files = WPFB_File::GetNumFiles2($where, $hia);
				foreach($files as $file) $content .= $file->GenTpl2($this->file_tpl_tag);
		} else {
			if(!empty($this->current_list->cat_order))
				WPFB_Item::Sort($categories, $this->current_list->cat_order);
		
			$cat = reset($categories); // get first category
			// here we check if single category and cat has at least one file (also secondary cat files!)
			if(count($categories) == 1 && ($cat->cat_num_files > 0 )) { // single cat
				if(!$cat->CurUserCanAccess()) return '';
				
				$where = "($where) AND ".WPFB_File::GetSqlCatWhereStr($cat->cat_id);
				$files = WPFB_File::GetFiles2($where, $hia, $sort, $this->current_list->page_limit, $start);
				$num_total_files = WPFB_File::GetNumFiles2($where, $hia);
				
				if($this->current_list->cat_grouping && $num_total_files > 0) $content .= $cat->GenTpl2($this->cat_tpl_tag);

					 foreach($files as $file) $content .= $file->GenTpl2($this->file_tpl_tag);
			} else { // multi-cat
				// TODO: multi-cat list pagination does not work properly yet
		
				// special handling of categories that do not have files directly: list child cats!
				if(count($categories) == 1 && $cat->cat_num_files == 0) {
					$categories = $cat->GetChildCats(true, true);
					if(!empty($this->current_list->cat_order))
						WPFB_Item::Sort($categories, $this->current_list->cat_order);
				}
		
				if($this->current_list->cat_grouping) { // group by categories
					$n = 0;
					foreach($categories as $cat)
					{
						if(!$cat->CurUserCanAccess()) continue;
		
						$num_total_files = max($nf = WPFB_File::GetNumFiles2("($where) AND ".WPFB_File::GetSqlCatWhereStr($cat->cat_id), $hia), $num_total_files); // TODO
		
						//if($n > $this->current_list->page_limit) break; // TODO!!
						if($nf > 0) {
							$files = WPFB_File::GetFiles2("($where) AND ".WPFB_File::GetSqlCatWhereStr($cat->cat_id), $hia, $sort, $this->current_list->page_limit, $start);                                                     
							if(count($files) > 0) {
								$content .= $cat->GenTpl2($this->cat_tpl_tag); // check for file count again, due to pagination!
								foreach($files as $file) $content .= $file->GenTpl2($this->file_tpl_tag); 
							}
						}
					}
				} else {
					// this is not very efficient, because all files are loaded, no pagination!
					$all_files = array();
					foreach($categories as $cat)
					{
						if(!$cat->CurUserCanAccess()) continue;
						$all_files += WPFB_File::GetFiles2("($where) AND ".WPFB_File::GetSqlCatWhereStr($cat->cat_id), $hia, $sort);
					}
					$num_total_files = count($all_files);

					{
						 WPFB_Item::Sort($all_files, $sort);

						 $keys = array_keys($all_files);
						 if($start == -1) $start = 0;
						 $last = ($this->current_list->page_limit > 0) ? min($start + $this->current_list->page_limit, $num_total_files) : $num_total_files;

						 for($i = $start; $i < $last; $i++)
							  $content .= $all_files[$keys[$i]]->GenTpl2($this->file_tpl_tag);
					 }
				}
			}
		}
		
		return $num_total_files;
	}

        
	function Generate($categories=null, $args = array())
	{	  
		$this->current_list = (object)wp_parse_args($args, array(
			 'cat_grouping' => false,
			 'cat_order' => null,
			 'file_order' => null,
			 'page_limit' => 0,			 
			 'hide_pagenav' => false,
			 'search' => null
		));
		unset($args);
		
		
		$uid = uniqid();
      
		
		$content = $this->ParseHeaderFooter($this->header, $uid);
		
		$num_total_files = $this->GenerateList($content, $categories);
		
		$footer = $this->ParseHeaderFooter($this->footer, $uid);		
		$is_datatable = strpos($footer, ").dataTable(")!==false;
		
		// TODO: no page_limit when dataTable?
		// hide pagenav when using datatable
		$this->current_list->hide_pagenav = $this->current_list->hide_pagenav || $is_datatable;
		
		$page_break = $this->current_list->page_limit > 0 && $num_total_files > $this->current_list->page_limit;
		
		if($page_break && !$this->current_list->hide_pagenav) {
			$pagenav = paginate_links( array(
				'base' => add_query_arg( 'wpfb_list_page', '%#%' ),
				'format' => '',
				'total' => ceil($num_total_files / $this->current_list->page_limit),
				'current' => empty($_GET['wpfb_list_page']) ? 1 : absint($_GET['wpfb_list_page'])
			));

			if(strpos($footer, '%page_nav%') === false)
				$footer .= $pagenav;
			else
				$footer = str_replace('%page_nav%', $pagenav, $footer);
		} else {
			$footer = str_replace('%page_nav%', '', $footer);
		}
		
		
		$content .= $footer;
		
		return $content;
	}
		
	function Sample($cat, $file) {
		$uid = uniqid();
		$this->current_list = (object)array('cat_grouping' => false, 'file_order' => null, 'page_limit' => 3, 'cat_order' => null);
	
		$footer = str_replace('%page_nav%', paginate_links(array(
			'base' => add_query_arg( 'wpfb_list_page', '%#%' ), 'format' => '',
			'total' => 3,
			'current' => 1
		)), $this->ParseHeaderFooter($this->footer, $uid));
		return $this->ParseHeaderFooter($this->header, $uid) . $cat->GenTpl2($this->cat_tpl_tag) . $file->GenTpl2($this->file_tpl_tag) . $footer;		
	}
	
	function Delete() {
		$tpls = get_option(WPFB_OPT_NAME.'_list_tpls');
		if(!is_array($tpls)) return;
		unset($tpls[$this->tag]);
		update_option(WPFB_OPT_NAME.'_list_tpls', $tpls);
	}
	
	function GetTitle() { return __(__(esc_html(WPFB_Output::Filename2Title($this->tag))), WPFB); }
}