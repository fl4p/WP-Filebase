<?php

class WPFB_Core
{

    static $load_js = false;
    static $file_browser_search = false;
    static $file_browser_item = null;
    static $post_url_cache = array();
    static $ajax_url = '';
    static $ajax_url_public = '';

    /**
     * WP-Filebase Settings Object
     *
     * @since 3.0.14
     * @access public
     * @var WPFB_Options
     */
    static $settings;

    static function PluginUrl($url)
    {
        return is_multisite() ? add_query_arg('blog_id', get_current_blog_id(), WPFB_PLUGIN_URI . $url) : (WPFB_PLUGIN_URI . $url);
    }

    static function InitClass()
    {
        self::$ajax_url = admin_url('admin-ajax.php?action=wpfilebase');
        self::$ajax_url_public = strstr(home_url('/?wpfilebase_ajax=1'), '//'); // remove protocol qualifier
        self::$settings = (object)get_option(WPFB_OPT_NAME, array());


        if (defined('WPFB_NO_CORE_INIT'))
            return; // on activation

        $lang_dir = defined('WPFB_LANG_DIR') ? ('../../' . WPFB_LANG_DIR) : basename(WPFB_PLUGIN_ROOT) . '/languages';
        load_plugin_textdomain('wp-filebase', false, $lang_dir);

        add_action('parse_query', array(__CLASS__, 'ParseQuery')); // search
        add_action('wp_enqueue_scripts', array(__CLASS__, 'EnqueueScripts'));
        add_action('wp_footer', array(__CLASS__, 'Footer'));
        add_action('generate_rewrite_rules', array(__CLASS__, 'GenRewriteRules'));

        add_action('wp_ajax_nopriv_wpfilebase', wpfb_callback('Ajax', 'PublicRequest'));
        add_action('wp_ajax_wpfilebase', wpfb_callback('Ajax', 'AdminRequest'));

        add_action('wpfb_cron', array(__CLASS__, 'Cron'));
        add_action('wpfilebase_sync', array(__CLASS__, 'Sync')); // for Developers: New wp-filebase actions
        add_action('wpfilebase_bgscan', array(__CLASS__, 'BgScanWork')); // for Developers: New wp-filebase actions

        // for attachments and file browser
        add_filter('the_content', array(__CLASS__, 'ContentFilter'), 10); // must be lower than 11 (before do_shortcode) and after wpautop (>9)

        // this adds the wp-filebase update channel
        add_filter('pre_set_site_transient_update_plugins', array(__CLASS__, 'PreSetPluginsTransientFilter'));

        // for wpfb-extension details
        add_filter('plugins_api', array(__CLASS__, 'PluginsApiFilter'), 10, 3);

        add_filter('ext2type', array(__CLASS__, 'Ext2TypeFilter'));

        add_shortcode('wpfilebase', array(__CLASS__, 'ShortCode'));

        self::DownloadRedirect();
        if (isset($_GET['wpfilebase_ajax'])) {
            define('DOING_AJAX', true);
            wpfb_loadclass('Ajax');
            WPFB_Ajax::PublicRequest();
        }

        // register treeview stuff
        wp_register_script('wpfb-treeview', WPFB_PLUGIN_URI . 'extras/jquery/treeview/jquery.treeview-async-edit.min.js', array('jquery'), WPFB_VERSION);
        wp_register_style('wpfb-treeview', WPFB_PLUGIN_URI . 'extras/jquery/treeview/jquery.treeview.css', array(), WPFB_VERSION);

        // DataTables
        wp_register_script('jquery-dataTables', WPFB_PLUGIN_URI . 'extras/jquery/dataTables/datatables.min.js', array('jquery'), WPFB_VERSION);
        wp_register_style('jquery-dataTables', WPFB_PLUGIN_URI . 'extras/jquery/dataTables/datatables.min.css', array(), WPFB_VERSION);

        wp_register_script(WPFB, WPFB_PLUGIN_URI . 'js/common.js', array('jquery'), WPFB_VERSION); // cond loading (see Footer)
        wp_register_script('wpfb-live-admin', WPFB_PLUGIN_URI . 'js/live-admin.js', array('jquery'), WPFB_VERSION);


        if (empty(WPFB_Core::$settings->disable_css)) {
            $wpfb_css = get_option('wpfb_css');
            wp_enqueue_style(WPFB, strstr($wpfb_css ? $wpfb_css : (WPFB_PLUGIN_URI . 'wp-filebase.css'), '//'), array(), WPFB_VERSION, 'all');
        }


        // live admin normaly for front-end, but also on filebrowser backend
        if (is_admin() ? (isset($_GET['page']) && $_GET['page'] == 'wpfilebase_filebrowser') : (WPFB_Core::CurUserCanCreateCat() || WPFB_Core::CurUserCanUpload())) {
            wp_enqueue_script('wpfb-live-admin');
            wp_enqueue_style('wpfb-live-admin', WPFB_PLUGIN_URI . 'css/live-admin.css', array(), WPFB_VERSION);

            self::$settings->admin_bar && add_action('admin_bar_menu', array(__CLASS__, 'AdminBar'), 80);

            if (!empty(self::$settings->file_context_menu)) {
                wp_enqueue_script('jquery-contextmenu', WPFB_PLUGIN_URI . 'extras/jquery/contextmenu/jquery.contextmenu.js', array('jquery'));
                wp_enqueue_style('jquery-contextmenu', WPFB_PLUGIN_URI . 'extras/jquery/contextmenu/jquery.contextmenu.css', array(), WPFB_VERSION);
            }
        }


        if (defined('WP_DEBUG') && WP_DEBUG) {
            wpfb_loadclass('Sync'); // load the sync class for error handling
        }

        if (!empty($_GET['wpfb_upload_file']) || !empty($_GET['wpfb_add_cat'])) {            
            wpfb_call('Admin', empty($_GET['wpfb_upload_file']) ? 'ProcessWidgetAddCat' : 'ProcessWidgetUpload');
        }



    }

    const LOG_MAX_FILE_SIZE = 200000; // 200K

    static function GetLogFile($for)
    {
        static $cache = array();

        if (isset($cache[$for]))
            return $cache[$for];

        $u = WPFB_Core::UploadDir();
        $fn = $u . '/._log-' . $for . '-' . md5($u . (defined('NONCE_KEY') ? NONCE_KEY : '') . $for) . '.txt';

        if (is_file($fn) && filesize($fn) > self::LOG_MAX_FILE_SIZE) {
            rename($fn, "$fn.old");
            file_put_contents($fn, strstr(file_get_contents("$fn.old", false, null, self::LOG_MAX_FILE_SIZE / 2), "\n"));
            touch($fn, date('U', filemtime("$fn.old")), time());
        }

        return ($cache[$for] = $fn);
    }

    static function InitDirectScriptAccess()
    {
        if (is_multisite() && !empty($_REQUEST['blog_id']) && get_current_blog_id() != $_REQUEST['blog_id']) {
            $blog_id = (int)$_REQUEST['blog_id'];
            if (!get_blog_details($blog_id, false))
                die('Blog does not exists!');
            switch_to_blog($blog_id);
        }
    }

    static function GetOpt($name = null)
    {
        return empty($name) ? (array)WPFB_Core::$settings : (isset(WPFB_Core::$settings->$name) ? WPFB_Core::$settings->$name : null);
    }

    /**
     * For compatibility to extensions
     * TODO: replace this with an action like `wpfilebase_print_js`
     */
    public static function PrintJS()
    {
        wpfb_loadclass('Output');
        WPFB_Output::PrintJS();
    }

    static function AdminInit()
    {
        wpfb_loadclass('AdminLite');
        if (!empty($_GET['page']) && strpos($_GET['page'], 'wpfilebase_') !== false)
            wpfb_loadclass('Admin');
        WPFB_AdminLite::Init();
    }


    static function AdminBar()
    {
        wpfb_call('AdminBar', 'AdminBar');
    }

    static function Sync()
    {
        wpfb_call('Sync', 'Sync');
    }

    static function LogMsg($msg, $for = 'core')
    {
        $t = current_time('mysql');
        $msg = str_replace(array("\r\n", "\n", "  "), " ", $msg);
        return @error_log("[$t] $msg\n", 3, self::GetLogFile($for));
    }

    /**
     *
     * @param WPFB_CCronWorker $worker
     */
    static function BgScanWork($worker = false)
    {
        wpfb_loadclass('Sync');
        WPFB_Sync::RescanFiles();
    }

    static function GenRewriteRules()
    {
        wpfb_call('Misc', 'GenRewriteRules');
    }

    static function GetPostId($query = null)
    {
        global $wp_query, $post;

        if (!empty($post->ID))
            return $post->ID;

        if (empty($query))
            $query = &$wp_query;

        return ((!empty($query->post) && $query->post->ID > 0) ? $query->post->ID :
            (!empty($query->queried_object_id) ? $query->queried_object_id :
                (!empty($query->query['post_id']) ? $query->query['post_id'] :
                    (!empty($query->query['page_id']) ? $query->query['page_id'] :
                        0))));
    }

    /**
     * @param $query WP_Query
     */
    static function ParseQuery(&$query)
    {
        // conditional loading of the search hooks
        global $wp_query;

        //print_r($query);

        $filepage_query = ($query->query_vars['post_type'] == 'wpfb_filepage' || isset($query->tax_query->queried_terms['wpfb_file_category']));

        if (WPFB_Core::$settings->search_integration && !empty($wp_query->query_vars['s'])) {
            wpfb_loadclass('Search');
            WPFB_Search::sqlHooks();
        } else if($filepage_query) {
            // for REST queries and category listing
            wpfb_loadclass('Search');
            WPFB_Search::sqlHooksPermsOnly();
        }


        if (!empty($_GET['wpfb_s']) || !empty($_GET['s'])) {
            WPFB_Core::$file_browser_search = true;
            add_filter('the_excerpt', array(__CLASS__, 'SearchExcerptFilter'), 100); // must be lower than 11 (before do_shortcode) and after wpautop (>9)
        }

        // check if current post is file browser and get currenlty selected file or category
        if (($id = self::GetPostId($query)) == WPFB_Core::$settings->file_browser_post_id) {
            wpfb_loadclass('File', 'Category');
            if (!empty($_GET['wpfb_file']))
                self::$file_browser_item = WPFB_File::GetFile($_GET['wpfb_file']);
            elseif (!empty($_GET['wpfb_cat']))
                self::$file_browser_item = WPFB_Category::GetCat($_GET['wpfb_cat']);
            elseif (!empty($_SERVER["HTTP_HOST"])) {
                $url = (is_ssl() ? 'https' : 'http') . '://' . $_SERVER["HTTP_HOST"] . stripslashes($_SERVER['REQUEST_URI']);
                if (($qs = strpos($url, '?')) !== false)
                    $url = substr($url, 0, $qs); // remove query string
                $path = trim(substr($url, strlen(WPFB_Core::GetPostUrl($id))), '/');
                if (!empty($path)) {
                    self::$file_browser_item = WPFB_Item::GetByPath(urldecode($path));
                    if (is_null(self::$file_browser_item))
                        self::$file_browser_item = WPFB_Item::GetByPath($path);
                }
            }
        }
    }

    static function DownloadRedirect()
    {
        $file = null;

        if (!empty($_GET['wpfb_dl'])) {
            wpfb_loadclass('File');
            $file = WPFB_File::GetFile($_GET['wpfb_dl']);
            @ob_end_clean(); // FIX: clean the OB so any output before the actual download is truncated (OB is started in wp-filebase.php)
        } else {
            if (!WPFB_Core::$settings->download_base || is_admin())
                return;
            $dl_url_path = parse_url(home_url(WPFB_Core::$settings->download_base . '/'), PHP_URL_PATH);
            $pos = strpos($_SERVER['REQUEST_URI'], $dl_url_path);
            if ($pos === 0) {
                $filepath = trim(substr(stripslashes($_SERVER['REQUEST_URI']), strlen($dl_url_path)), '/');
                if (($qs = strpos($filepath, '?')) !== false)
                    $filepath = substr($filepath, 0, $qs); // remove query string
                if (!empty($filepath)) {
                    wpfb_loadclass('File', 'Category');
                    $file = is_null($file = WPFB_File::GetByPath($filepath)) ? WPFB_File::GetByPath(urldecode($filepath)) : $file;
                }
            }
        }

        if (!empty($file) && is_object($file) && !empty($file->is_file)) {
            $file->Download();
            exit;
        }
    }

    static function Ext2TypeFilter($arr)
    {
        $arr['interactive'][] = 'exe';
        $arr['interactive'][] = 'msi';
        return $arr;
    }

    static function SearchExcerptFilter($content)
    {
        global $id, $post;

        // replace file browser post content with search results
        if (WPFB_Core::$file_browser_search && $id && $id == WPFB_Core::$settings->file_browser_post_id) {
            wpfb_loadclass('Search', 'File', 'Category');
            $content = '';
            WPFB_Search::FileSearchContent($content);
        }         return $content;
    }

    static function ContentFilter($content)
    {
        global $id, $wpfb_fb, $post;
        if (!WPFB_Core::$settings->parse_tags_rss && is_feed())
            return $content;

        if (is_object($post) && !post_password_required()) {
            // TODO: file resulst are generated twice, 2nd time in the_excerpt filter (SearchExcerptFilter)
            // some themes do not use excerpts in search resulsts!!
            // replace file browser post content with search results
            if (WPFB_Core::$file_browser_search && WPFB_Core::$settings->file_browser_post_id && $id == WPFB_Core::$settings->file_browser_post_id) {
                wpfb_loadclass('Search', 'File', 'Category');
                $content = '';
                WPFB_Search::FileSearchContent($content);
            } else { // do not hanlde attachments when searching
                $single = is_single() || is_page();

                // the did_action check prevents JS beeing printed into the post during a pre-render (e.g. WP SEO)
                if ($single && WPFB_Core::$settings->file_browser_post_id && $post->ID == WPFB_Core::$settings->file_browser_post_id && did_action('wp_print_scripts')) {
                    $wpfb_fb = true;
                    wpfb_loadclass('Output', 'File', 'Category');
                    WPFB_Output::FileBrowser($content, 0, empty($_GET['wpfb_cat']) ? 0 : intval($_GET['wpfb_cat']));
                }

                if (self::GetOpt('auto_attach_files') && ($single || self::GetOpt('attach_loop'))) {
                    wpfb_loadclass('Output');
                    if (WPFB_Core::$settings->attach_pos == 0)
                        $content = WPFB_Output::PostAttachments(true) . $content;
                    else
                        $content .= WPFB_Output::PostAttachments(true);
                }
            }
        }

        return $content;
    }

    static function ShortCode($atts, $content = null, $tag = null)
    {
        wpfb_loadclass('Output');
        return WPFB_Output::ProcessShortCode(shortcode_atts(array(
            'tag' => 'list', // file, fileurl, attachments
            'id' => -1,
            'path' => null,
            'tpl' => null,
            'sort' => null,
            'showcats' => false,
            'sortcats' => null,
            'num' => 0,
            'pagenav' => 1,
            'linktext' => null,
                    ), $atts), $content, $tag);
    }

    static function Footer()
    {
        global $wpfb_fb; // filebrowser loaded?
        // TODO: use enque and no cond loading ?
        if (!empty(self::$load_js)) {
            wpfb_call('Output', 'PrintJS');
        }

        if (!empty($wpfb_fb) && !WPFB_Core::$settings->disable_footer_credits) {
            echo '<div id="wpfb-credits" name="wpfb-credits" style="' . esc_attr(WPFB_Core::$settings->footer_credits_style) . '">';
            printf(__('<a href="%s" title="Wordpress Download Manager Plugin" style="color:inherit;font-size:inherit;">Downloads served by WP-Filebase</a>', 'wp-filebase'), 'https://wpfilebase.com/');
            echo '</div>';
        }
    }

    static function UpdateOption($name, $value = null)
    {
        WPFB_Core::$settings->$name = $value;
        update_option(WPFB_OPT_NAME, (array)WPFB_Core::$settings);
    }

    static function UploadDir()
    {
        static $upload_path = '';
        return empty($upload_path) ? ($upload_path = path_join(ABSPATH, empty(WPFB_Core::$settings->upload_path) ? 'wp-content/uploads/filebase' : WPFB_Core::$settings->upload_path)) : $upload_path;
    }

    static function GetPostUrl($id)
    {
        return isset(self::$post_url_cache[$id]) ? self::$post_url_cache[$id] : (self::$post_url_cache[$id] = get_permalink($id));
    }

    static function GetSortSql($sort = null, $attach_order = false, $for_cat = false)
    {
        wpfb_loadclass('Output');
        $sql = $attach_order ? ("`" . ($for_cat ? 'cat_order' : 'file_attach_order') . "` ASC, ") : "";
        foreach (explode(',', $sort) as $s) {
            list($sf, $sd) = WPFB_Output::ParseSorting($s, $for_cat);
            $sql .= "`" . esc_sql($sf) . "` $sd, ";
        }
        return substr($sql, 0, -2);
    }

    static function EnqueueScripts()
    {
        global $wp_query;

        if (!WPFB_Core::$settings->late_script_loading && ((!empty($wp_query->queried_object_id) && $wp_query->queried_object_id == WPFB_Core::$settings->file_browser_post_id) ||
                !empty($wp_query->post) && $wp_query->post->ID == WPFB_Core::$settings->file_browser_post_id)
        ) {
            wp_enqueue_script('wpfb-treeview');
            wp_enqueue_style('wpfb-treeview');
        }
    }

// OPTIMZE: not so deep function calls
// gets custom template list or single if tag specified
    static function GetFileTpls($tag = null)
    {
        if ($tag == 'default')
            return self::GetOpt('template_file');
        $tpls = get_option(WPFB_OPT_NAME . '_tpls_file');
        return empty($tag) ? $tpls : @$tpls[$tag];
    }

    static function GetCatTpls($tag = null)
    {
        if ($tag == 'default')
            return self::GetOpt('template_cat');
        $tpls = get_option(WPFB_OPT_NAME . '_tpls_cat');
        return empty($tag) ? $tpls : @$tpls[$tag];
    }

    static function GetTpls($type, $tag = null)
    {
        return ($type == 'cat') ? self::GetCatTpls($tag) : self::GetFileTpls($tag);
    }

    static function SetFileTpls($tpls)
    {
        return is_array($tpls) ? update_option(WPFB_OPT_NAME . '_tpls_file', $tpls) : false;
    }

    static function SetCatTpls($tpls)
    {
        return is_array($tpls) ? update_option(WPFB_OPT_NAME . '_tpls_cat', $tpls) : false;
    }

    static function GetParsedTpl($type, $tag)
    {
        if (empty($tag))
            return null;
        if ($tag == 'default')
            return self::GetOpt("template_{$type}_parsed");
        $on = WPFB_OPT_NAME . '_ptpls_' . $type;
        $ptpls = get_option($on);
        if (empty($ptpls)) {
            $ptpls = wpfb_call('TplLib', 'Parse', self::GetTpls($type));
            update_option($on, $ptpls);
        }
        return empty($ptpls[$tag]) ? null : $ptpls[$tag];
    }

    static function Cron()
    {
        wpfb_loadclass('AdminLite'); // register fatal error logger

        wpfb_call('Misc', 'GetFileTypeStats');

        wpfb_loadclass('FileUtils');
        WPFB_FileUtils::DeleteOldFiles(WPFB_Core::UploadDir() . '/.tmp/catzip', 60*30);
        WPFB_FileUtils::DeleteOldFiles(WPFB_Core::UploadDir() . '/.tmp');

        if (self::$settings->cron_sync ) {
            self::LogMsg('Starting cron sync...', 'sync');
            $t_start = microtime(true);
            wpfb_call('Sync', 'Sync');
            $t_end = microtime(true);
            self::LogMsg('Cron sync done!', 'sync');
            update_option('wpfilebase_cron_sync_stats', array(
                't_start' => $t_start,
                't_end' => $t_end,
                'mem_peak' => memory_get_peak_usage()
            ));
        }
    }

    static function GetMaxUlSize()
    {
        return wpfb_call('Misc', 'ParseIniFileSize', ini_get('upload_max_filesize'));
    }

    public static function GetCustomFields($full_field_names = false, &$default_values = null)
    {
        $custom_fields = isset(WPFB_Core::$settings->custom_fields) ? explode("\n", WPFB_Core::$settings->custom_fields) : array();
        $arr = array();
        $default_values = array();
        if (empty($custom_fields[0]))
            return array();
        foreach ($custom_fields as $cf) {
            $cfa = explode("|", $cf);
            $arr[$k = $full_field_names ? ('file_custom_' . trim($cfa[1])) : trim($cfa[1])] = $cfa[0];
            $default_values[$k] = empty($cfa[2]) ? '' : $cfa[2];
        }
        return $arr;
    }

    static function GetOldCustomCssPath($path = null)
    {
        $path = empty($path) ? self::UploadDir() : (ABSPATH . '/' . trim(str_replace('\\', '/', str_replace('..', '', $path)), '/'));
        return @is_dir($path) ? "$path/_wp-filebase.css" : null;
    }

    static function CreateTplFunc($parsed_tpl)
    {
        return create_function('$f,$e=null', "return ($parsed_tpl);");
    }

    /**
     * Hooks into update checks and adds extensions
     *
     * @param type $value
     * @return type
     */
    static function PreSetPluginsTransientFilter($value)
    {
        if (!isset($value->response) || !is_array($value->response))
            return $value;
        $lvi = wpfb_call('ExtensionLib', 'GetLatestVersionInfoExt');
        if (!empty($lvi))
            $value->response = array_merge($value->response, $lvi);
        return $value;
    }

    static function PluginsApiFilter($value, $action = null, $args = null)
    {
        if ($value)
            return $value;
        if (!is_object($args))
            $args = (object)$args;
        return ($action === 'plugin_information' && (                strncmp($args->slug, "wpfb-", 5) === 0)) ? wpfb_call('ExtensionLib', 'GetApiPluginInfo', $args->slug) : $value;
    }

    static function CurUserCanCreateCat()
    {
        return current_user_can('manage_categories');
    }

    static function CurUserCanUpload()
    {
        return (current_user_can('upload_files'));
    }

}
