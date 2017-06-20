<?php

/*
  Plugin Name: WP-Filebase
  Plugin URI:  https://wpfilebase.com/
  Description: Adds a powerful downloads manager supporting file categories, download counter, widgets, sorted file lists and more to your WordPress blog.
  Version:     0.3.4.24
  Author:      Fabian Schlieper
  Author URI:  http://fabi.me/
  License:     GPL2
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  Domain Path: /languages
  Text Domain: wp-filebase
  GitHub Plugin URI: https://github.com/f4bsch/WP-Filebase
 */

if (!defined('WPFB')) {
    define('WPFB', 'wpfb');
    define('WPFB_VERSION', '0.3.4.24');
    
    define('WPFB_PLUGIN_ROOT', str_replace('\\', '/', dirname(__FILE__)) . '/');
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))));
    } else {
        //define('WPFB_PLUGIN_URI', is_multisite() ? str_replace(array('http://','https://'), '//', str_replace(str_replace('\\','/',ABSPATH),get_option('siteurl').'/',WPFB_PLUGIN_ROOT)) : plugin_dir_url(__FILE__));
        define('WPFB_PLUGIN_URI', is_multisite() ? get_site_url(null, substr(WPFB_PLUGIN_ROOT, strlen(ABSPATH))) : plugin_dir_url(__FILE__));
    }
    if (!defined('WPFB_PERM_FILE'))
        define('WPFB_PERM_FILE', 666);
    if (!defined('WPFB_PERM_DIR'))
        define('WPFB_PERM_DIR', 777); // default unix 755
    define('WPFB_OPT_NAME', 'wpfilebase');
    define('WPFB_PLUGIN_NAME', 'WP-Filebase');
    define('WPFB_TAG_VER', 2);


    function wpfb_autoloadV2($class) {
        static $ns = 'WPFB\\';
        $len = strlen($ns);
        if(strncmp($class, $ns, $len) === 0 ) {
            require_once  WPFB_PLUGIN_ROOT."classes/".substr($class,$len).".php";
        }
    }

    spl_autoload_register('wpfb_autoloadV2');

    function wpfb_loadclass($cl)
    {
        if (func_num_args() > 1) {
            $args = func_get_args(); // func_get_args can't be used as func param!
            return array_map(__FUNCTION__, $args);
        } else {
            $cln = 'WPFB_' . $cl;

            if (class_exists($cln))
                return true;

            $p = WPFB_PLUGIN_ROOT . "classes/{$cl}.php";
            $res = (include_once $p);
            if (!$res) {
                echo("<p>WP-Filebase Error: Could not include class file <b>'{$cl}'</b>!</p>");
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    print_r(debug_backtrace());
                }
            } else {
                if (!class_exists($cln)) {
                    echo("<p>WP-Filebase Error: Class <b>'{$cln}'</b> does not exists in loaded file!</p>");
                    return false;
                }

                if (method_exists($cln, 'InitClass'))
                    call_user_func(array($cln, 'InitClass'));
            }
        }
        return $res;
    }

    // calls static $fnc of class $cl with $params
    // $cl is loaded automatically if not existing

    /**
     * Calls a static method of class with given suffix (e.g. WPFB_{$cl}::$fnc($params)).
     * Loads class if it does not exist.
     *
     * @param string $cl The class name (without WPFB_)
     * @param string $fnc The method name
     * @param null $params The arguments to pass
     * @param bool $is_args_array
     * @return mixed|null
     */
    function wpfb_call($cl, $fnc, $params = null, $is_args_array = false)
    {
        $cln = 'WPFB_' . $cl;
        $fnc = array($cln, $fnc);
        return (class_exists($cln) || wpfb_loadclass($cl)) ? ($is_args_array ? call_user_func_array($fnc, $params) : call_user_func($fnc, $params)) : null;
    }

    /**
     * Creates a callback with lazy class autoloading.
     *
     * @param $cl
     * @param $fnc
     * @return string
     */
    function wpfb_callback($cl, $fnc)
    {
        return create_function('', '$p=func_get_args();return wpfb_call("' . $cl . '","' . $fnc . '",$p,true);');
    }

    function wpfilebase_init()
    {
        wpfb_loadclass('Core');
    }

    function wpfilebase_widgets_init()
    {
        wpfb_loadclass('Widget');
        WPFB_Widget::register();
    }

    function wpfilebase_activate()
    {
        define('WPFB_NO_CORE_INIT', true);
        wpfb_loadclass('Core', 'Admin', 'Setup');
        WPFB_Setup::OnActivateOrVerChange(empty(WPFB_Core::$settings->version) ? null : WPFB_Core::$settings->version);
    }

    function wpfilebase_deactivate()
    {
        wpfb_loadclass('Core', 'Admin', 'Setup');
        wpfb_call('ExtensionLib', 'PluginDeactivated');
        WPFB_Setup::OnDeactivate();
    }


    // FIX: setup the OB to truncate any other output when downloading
    if (!empty($_GET['wpfb_dl'])) {
        @define('NGG_DISABLE_RESOURCE_MANAGER', true); // NexGen Gallery
        ob_start();
    }
}

/**
 * WPDB
 * @global wpdb $wpdb
 */
global $wpdb;

if (isset($wpdb)) {
    $wpdb->wpfilebase_cats = $wpdb->prefix . 'wpfb_cats';
    $wpdb->wpfilebase_files = $wpdb->prefix . 'wpfb_files';
    $wpdb->wpfilebase_files_id3 = $wpdb->prefix . 'wpfb_files_id3';
}

if (isset($_GET['wpfilebase_thumbnail'])) {
    require_once(WPFB_PLUGIN_ROOT . 'thumbnail.php');
}

if (function_exists('add_action')) {
    add_action('init', 'wpfilebase_init');
    add_action('widgets_init', 'wpfilebase_widgets_init');
    add_action('admin_menu', wpfb_callback('AdminLite', 'SetupMenu'));
    add_action( 'network_admin_menu', wpfb_callback('AdminLite', 'NetworkMenu') );
    add_action('admin_init', array('WPFB_Core', 'AdminInit'), 10);
    //add_action( 'wp_enqueue_scripts', array('WPFB_Core', 'LateScripts'), 999 );
    
    register_activation_hook(__FILE__, 'wpfilebase_activate');
    register_deactivation_hook(__FILE__, 'wpfilebase_deactivate');
}
