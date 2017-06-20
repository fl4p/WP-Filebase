<?php

wpfb_loadclass('Item');

class WPFB_Category extends WPFB_Item
{

    var $cat_id;
    var $cat_name;
    var $cat_description;
    var $cat_folder;
    var $cat_path;
    var $cat_parent = 0;
    var $cat_num_files = 0;
    var $cat_num_files_total = 0;
    var $cat_user_roles;
    var $cat_owner = 0;
    var $cat_icon;
    var $cat_exclude_browser = 0;
    var $cat_order;
    var $cat_wp_term_id = 0;
    var $cat_scan_lock = 0;


    static $cache = array();
    static $cache_complete = false; // for GetCats(null) and GetChildCats(...)

    /**
     * Get category objects
     *
     * @access public
     *
     * @param $extra_sql Optional
     *
     * @return WPFB_Category[] Categories
     */

    static function GetCats($extra_sql = null)
    {
        global $wpdb;

        if (empty($extra_sql)) {
            $extra_sql = 'ORDER BY cat_name ASC';
            if (self::$cache_complete) {
                return self::$cache;
            } else {
                self::$cache_complete = true;
            }
        }

        $cats = array();

        $results = $wpdb->get_results("SELECT * FROM $wpdb->wpfilebase_cats $extra_sql");
        if (!empty($results)) {
            foreach (array_keys($results) as $i) {
                $id = (int)$results[$i]->cat_id;
                if (!isset(self::$cache[$id])) {
                    self::$cache[$id] = new WPFB_Category($results[$i]);
                }
                $cats[$id] = self::$cache[$id]; // always use items from cache
            }
        }

        // child cats
        foreach (array_keys($cats) as $id) {
            $cat = &$cats[$id];

            $pid = (int)$cat->cat_parent;
            if ($pid > 0 && isset(self::$cache[$pid])) {
                $pcat = &self::$cache[$pid];
                if (!isset($pcat->cat_childs) || !is_array($pcat->cat_childs)) {
                    $pcat->cat_childs = array();
                }
                $pcat->cat_childs[$id] = $cat;
            }
        }

        return $cats;
    }

    /**
     * Get category objects
     *
     * @access public
     *
     * @param int $id ID
     *
     * @return WPFB_Category
     */
    static function GetCat($id)
    {
        $id = 0 + $id;
        if ($id > 0 && (isset(self::$cache[$id]) || WPFB_Category::GetCats("WHERE cat_id = $id"))) {
            return self::$cache[$id];
        }

        return null;
    }

    static function GetNumCats()
    {
        global $wpdb;

        return $wpdb->get_var("SELECT COUNT(cat_id) FROM $wpdb->wpfilebase_cats");
    }

    static function CompareName($a, $b)
    {
        return $a->cat_name > $b->cat_name;
    }

    function __construct($db_row = null)
    {
        parent::__construct($db_row);
        $this->is_category = true;
    }

    function DBSave($throw_on_error = false)
    { // validate some values before saving (fixes for mysql strict mode)
        if ($this->locked > 0) {
            return $this->TriggerLockedError();
        }
        $this->cat_exclude_browser = (int)!empty($this->cat_exclude_browser);
        //$this->cat_required_level = intval($this->cat_required_level);
        $this->cat_parent = intval($this->cat_parent);
        $this->cat_num_files = intval($this->cat_num_files);
        $this->cat_num_files_total = intval($this->cat_num_files_total);


        return parent::DBSave($throw_on_error);
    }

    function GetWPTermId()
    {
        return 0;
    }


    static $no_bubble = false;

    static function DisableBubbling($disable = true)
    {
        self::$no_bubble = $disable;
        self::$cache_complete = false;
    }

    function NotifyFileAdded($file)
    {
        if (self::$no_bubble)
            return;


        if ($file->file_category == $this->cat_id) {
            $this->cat_num_files++;
        }

        $this->cat_num_files_total++;
        if (!$this->locked) {
            $this->DBSave();
        }


        $parent = $this->GetParent();
        if ($parent) {
            $parent->NotifyFileAdded($file);
        }
    }

    function NotifyFileRemoved($file)
    {
        if (self::$no_bubble)
            return;


        if ($file->file_category == $this->cat_id) {
            $this->cat_num_files--;
        }
        $this->cat_num_files_total--;
        if ($this->cat_num_files < 0) {
            $this->cat_num_files = 0;
        }
        if ($this->cat_num_files_total < 0) {
            $this->cat_num_files_total = 0;
        }
        if (!$this->locked) {
            $this->DBSave();
        }

        $parent = $this->GetParent();
        if ($parent) {
            $parent->NotifyFileRemoved($file);
        }
    }

    function GetChildCats($recursive = false, $sort_by_name = false)
    {
        if (!self::$cache_complete && empty($this->childs_complete)) {
            $this->cat_childs = self::GetCats("WHERE cat_parent = " . (int)$this->cat_id . ($sort_by_name ? " ORDER BY cat_name ASC" : ""));
            $this->childs_complete = true;
        }

        if (empty($this->cat_childs)) {
            return array();
        }

        $cats = $this->cat_childs;
        if ($recursive) {
            $keys = array_keys($cats);
            foreach ($keys as $i) {
                $cats += $cats[$i]->GetChildCats(true);
            }
        }

        return $cats;
    }

    function HasChildren($cats_only = false)
    {
        return $cats_only ? (count($this->GetChildCats()) > 0) : ($this->cat_num_files_total > 0);
    }

    function Delete()
    {
        global $wpdb;

        // TODO: error handling
        $cats = $this->GetChildCats();
        $files = $this->GetChildFiles();
        $parent_id = $this->GetParentId();

        foreach ($cats as $cat) {
            $cat->ChangeCategoryOrName($parent_id);
        }
        foreach ($files as $file) {
            $file->ChangeCategoryOrName($parent_id);
        }

        self::$cache_complete = false;
        unset(self::$cache[0 + $this->cat_id]);

        // delete the category
        @unlink($this->GetLocalPath());
        @rmdir($this->GetLocalPath());
        $wpdb->query("DELETE FROM $wpdb->wpfilebase_cats WHERE cat_id = " . (int)$this->GetId());

        return array('error' => false);
    }

    private function _get_tpl_var($name, &$esc)
    {
        switch ($name) {
            case 'cat_url':
                return $this->GetUrl();
            case 'cat_path':
                return $this->GetLocalPathRel();
            case 'cat_parent':
            case 'cat_parent_name':
                return is_object($parent = &$this->GetParent()) ? $parent->cat_name : '';
            case 'cat_icon_url':
                return $this->GetIconUrl();
            //	case 'cat_icon_url_small':	return $this->GetIconUrl('small');
            case 'cat_has_icon':
                return !empty($this->cat_icon);
            case 'cat_small_icon':
                $esc = false;

                return '<img src="' . $this->GetIconUrl('small') . '" alt="' . esc_attr(sprintf(__('Icon of %s', 'wp-filebase'), $this->cat_name)) . '" style="width:auto;' . ((WPFB_Core::$settings->small_icon_size > 0) ? ('height:' . WPFB_Core::$settings->small_icon_size . 'px;') : '') . 'vertical-align:middle;" />';
            case 'cat_num_files':
                return $this->cat_num_files;
            case 'cat_num_files_total':
                return $this->cat_num_files_total;
            //case 'cat_required_level':	return ($this->cat_required_level - 1);
            case 'cat_user_can_access':
                return $this->CurUserCanAccess();
            case 'cat_user_can_edit':
                return $this->CurUserCanEdit();
            case 'cat_edit_url':
                return $this->GetEditUrl();
            case 'uid':
                return self::$tpl_uid;

            case 'is_mobile':
                return wp_is_mobile();
        }

        // string length limit:
        if (!isset($this->$name) && ($p = strpos($name, ':')) > 0) {
            $maxlen = (int)substr($name, $p + 1);
            $name = substr($name, 0, $p);
            $str = $this->get_tpl_var($name);
            if ($maxlen > 3 && strlen($str) > $maxlen) {
                $str = (function_exists('mb_substr') ? mb_substr($str, 0, $maxlen - 3, 'utf8') : mb_substr($str, 0, $maxlen - 3)) . '...';
            }

            return $str;
        }

        return isset($this->$name) ? $this->$name : '';
    }

    function get_tpl_var($name, $extra_data = null)
    {
        if (isset($extra_data->$name)) {
            return $extra_data->$name;
        }
        $esc = true;
        $v = $this->_get_tpl_var($name, $esc);

        return $esc ? esc_html($v) : $v;
    }

    function CurUserCanAddFiles($user = null)
    {
        return $this->CurUserIsOwner($user) || ($user !== null && user_can($user, 'manage_options'))             ;
    }

    function CurUserCanEdit($user = null)
    {
        return parent::CurUserCanEdit($user) ;
    }

    /**
     * @return null|WPFB_RemoteSync
     */
    function getCloudSync() {
        wpfb_loadclass('RemoteSync');
        $rs = WPFB_RemoteSync::GetByCat($this->GetId());
        if($rs) return $rs;
        $parent = $this->GetParent();
        return $parent ? $parent->getCloudSync() : null;
    }

}

?>