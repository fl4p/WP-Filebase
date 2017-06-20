<?php

class WPFB_AdminLite
{
    static function onShutdown()
    {
        $error = error_get_last();
        if ($error
            && $error['type'] <= E_USER_ERROR
            && $error['type'] != E_COMPILE_WARNING
            && $error['type'] != E_CORE_WARNING
            && $error['type'] != E_NOTICE
            && $error['type'] != E_WARNING
        ) {
            if (current_user_can('manage_options')) {
                echo '<pre>PHP ERROR:';
                var_dump($error);
                echo '</pre>';
            }
            WPFB_Core::LogMsg('SHUTDOWN ERROR:' . json_encode($error));
        } else {
            return true;
        }
    }

    static function InitClass()
    {
        register_shutdown_function(array(__CLASS__, 'onShutdown'));

        wp_enqueue_style(WPFB . '-admin', WPFB_PLUGIN_URI . 'css/admin.css', array(), WPFB_VERSION, 'all');

        wp_register_script('jquery-deserialize', WPFB_PLUGIN_URI . 'bower_components/jquery-deserialize/dist/jquery.deserialize.min.js', array('jquery'), WPFB_VERSION);

        if (isset($_GET['page'])) {
            $page = $_GET['page'];
            if ($page == 'wpfilebase_files') {
                wp_enqueue_script('postbox');
                wp_enqueue_style('dashboard');
            } elseif ($page == 'wpfilebase' && isset($_GET['action']) && $_GET['action'] == 'sync') {
                do_action('wpfilebase_sync');
                wp_die("Filebase synced.");
            }
        }

        add_action('wp_dashboard_setup', array(__CLASS__, 'AdminDashboardSetup'));

        //wp_register_widget_control(WPFB_PLUGIN_NAME, "[DEPRECATED]".WPFB_PLUGIN_NAME .' '. __('File list','wp-filebase'), array(__CLASS__, 'WidgetFileListControl'), array('description' => __('DEPRECATED','wp-filebase')));

        add_action('admin_print_scripts', array('WPFB_AdminLite', 'AdminPrintScripts'));


        add_action('in_plugin_update_message-wp-filebase-pro/wp-filebase.php', array(__CLASS__, 'pluginUpdateMessage'), 10, 2);

        self::CheckChangedVer();


        if (basename($_SERVER['PHP_SELF']) === "plugins.php") {
            if (isset($_GET['wpfb-uninstall']) && current_user_can('edit_files'))
                update_option('wpfb_uninstall', !empty($_GET['wpfb-uninstall']) && $_GET['wpfb-uninstall'] != "0");

            if (get_option('wpfb_uninstall')) {
                function wpfb_uninstall_warning()
                {
                    echo "
				<div id='wpfb-warning' class='updated fade'><p><strong>" . __('WP-Filebase will be uninstalled completely when deactivating the Plugin! All settings and File/Category Info will be deleted. Actual files in the upload directory will not be removed.', 'wp-filebase') . ' <a href="' . add_query_arg('wpfb-uninstall', '0') . '">' . __('Cancel') . "</a></strong></p></div>
				";
                }

                add_action('admin_notices', 'wpfb_uninstall_warning');
            }
        }

    }


    static function SetupMenu()
    {
        global $wp_version;

        $pm_tag = WPFB_OPT_NAME . '_manage';
        $icon = (floatval($wp_version) >= 3.8) ? 'images/admin_menu_icon2.png' : 'images/admin_menu_icon.png';


        add_menu_page(WPFB_PLUGIN_NAME, WPFB_PLUGIN_NAME, 'manage_categories', $pm_tag, null, WPFB_PLUGIN_URI . $icon /*, $position*/);
        add_submenu_page($pm_tag, WPFB_PLUGIN_NAME, __('Dashboard'), 'manage_categories', $pm_tag, wpfb_callback('AdminGuiManage', 'Display'));

        $menu_entries = array(
            array('tit' => __('Files', 'wp-filebase'), 'tag' => 'files', 'fnc' => wpfb_callback('AdminGuiFiles', 'Display'), 'desc' => 'View uploaded files and edit them',
                'cap' => 'upload_files',
            ),
            array('tit' => __('Categories'/*def*/), 'tag' => 'cats', 'fnc' => 'DisplayCatsPage', 'desc' => 'Manage existing categories and add new ones.',
                'cap' => 'manage_categories',
            )
        );

        $menu_entries[] = array('tit' => __('File Browser', 'wp-filebase'), 'tag' => 'filebrowser', 'fnc' => wpfb_callback('AdminGuiFileBrowser', 'Display'), 'desc' => 'Brows files and categories',
            'cap' => 'upload_files',
        );
        //array('tit'=>'Sync Filebase', 'hide'=>true, 'tag'=>'sync',	'fnc'=>'DisplaySyncPage',	'desc'=>'Synchronises the database with the file system. Use this to add FTP-uploaded files.',	'cap'=>'upload_files'),


        if (empty(WPFB_Core::$settings->disable_css)) {
            $menu_entries[] = array('tit' => __('Edit Stylesheet', 'wp-filebase'), 'tag' => 'css', 'fnc' => wpfb_callback('AdminGuiCss', 'Display'), 'desc' => 'Edit the CSS for the file template',
                //'hide'=>true,
                'cap' => 'edit_themes',
            );
        }

        $menu_entries = array_merge($menu_entries, array(
            array('tit' => __('Embed Templates', 'wp-filebase'), 'tag' => 'tpls', 'fnc' => 'DisplayTplsPage', 'desc' => 'Edit custom file list templates',
                'cap' => 'edit_themes',
            ),

            array('tit' => __('Settings'), 'tag' => 'sets', 'fnc' => 'DisplaySettingsPage', 'desc' => 'Change Settings',
                'cap' => 'manage_options'),
            //array('tit'=>'Donate &amp; Feature Request','tag'=>'sup',	'fnc'=>'DisplaySupportPage','desc'=>'If you like this plugin and want to support my work, please donate. You can also post your ideas making the plugin better.', 'cap'=>'manage_options'),
        ));

        foreach ($menu_entries as $me) {
            $callback = is_callable($me['fnc']) ? $me['fnc'] : array(__CLASS__, $me['fnc']);
            add_submenu_page($pm_tag, WPFB_PLUGIN_NAME . ' - ' . $me['tit'], empty($me['hide']) ? $me['tit'] : null, empty($me['cap']) ? 'read' : $me['cap'], WPFB_OPT_NAME . '_' . $me['tag'], $callback);
        }
    }

    static function NetworkMenu()
    {
        $pm_tag = WPFB_OPT_NAME . '_manage';
        add_menu_page(WPFB_PLUGIN_NAME, WPFB_PLUGIN_NAME, 'manage_options', $pm_tag, wpfb_callback('AdminGuiManage', 'Display'), WPFB_PLUGIN_URI . 'images/admin_menu_icon2.png' /*, $position*/);
    }

    static function Init()
    {
        global $submenu;
        if (!empty($submenu['wpfilebase_manage']) && is_array($submenu['wpfilebase_manage']) && (empty($_GET['page']) || $_GET['page'] !== 'wpfilebase_css')) {
            foreach (array_keys($submenu['wpfilebase_manage']) as $i) {
                if ($submenu['wpfilebase_manage'][$i][2] === 'wpfilebase_css') {
                    unset($submenu['wpfilebase_manage'][$i]);
                    break;
                }
            }
        }

        add_filter('mce_external_plugins', array(__CLASS__, 'McePlugins'));
        add_filter('mce_buttons', array(__CLASS__, 'MceButtons'));


        if (isset($_GET['wpfilebase-screen'])) {
            switch ($_GET['wpfilebase-screen']) {
                case 'editor-plugin':
                    require_once(WPFB_PLUGIN_ROOT . 'screens/editor-plugin.php');
                    exit;
                case 'tpl-preview':
                    require_once(WPFB_PLUGIN_ROOT . 'screens/tpl-preview.php');
                    exit;
            }
            wp_die('Unknown screen ' . esc_html($_GET['wpfilebase-screen']) . '!');
        }
    }

    static function DisplayCatsPage()
    {
        wpfb_call('AdminGuiCats', 'Display');
    }

    static function DisplayTplsPage()
    {
        wpfb_call('AdminGuiTpls', 'Display');
    }

    static function DisplaySettingsPage()
    {
        wpfb_call('AdminGuiSettings', 'Display');
    }

    static function DisplaySupportPage()
    {
        wpfb_call('AdminGuiSupport', 'Display');
    }

    static function McePlugins($plugins)
    {
        $plugins['wpfilebase'] = WPFB_PLUGIN_URI . 'tinymce/editor_plugin.js';
        return $plugins;
    }

    static function MceButtons($buttons)
    {
        array_push($buttons, 'separator', 'wpfbInsertTag');
        return $buttons;
    }


    private static function CheckChangedVer()
    {
        $ver = wpfb_call('Core', 'GetOpt', 'version');
        if ($ver != WPFB_VERSION) {
            wpfb_loadclass('Setup');
            WPFB_Setup::OnActivateOrVerChange($ver);
        }
    }

    static function JsRedirect($url, $unsafe = false)
    {
        $url = wp_sanitize_redirect($url);
        if (!$unsafe)
            $url = wp_validate_redirect($url, apply_filters('wp_safe_redirect_fallback', admin_url(), 302));
        echo '<script type="text/javascript"> window.location = "', str_replace('"', '\\"', $url), '"; </script><h1><a href="', esc_attr($url), '">', esc_html($url), '</a></h1>';
        // NO exit/die here!
    }

    static function AdminPrintScripts()
    {
        if (!empty($_GET['page']) && strpos($_GET['page'], 'wpfilebase_') !== false) {
            if ($_GET['page'] == 'wpfilebase_manage') {
                wpfb_loadclass('AdminDashboard');
                WPFB_AdminDashboard::Setup(true);
            }

            wpfb_call('Output', 'PrintJS');
        }

        if (has_filter('ckeditor_external_plugins')) {
            ?>
            <script type="text/javascript">
                //<![CDATA[
                /* CKEditor Plugin */
                if (typeof(ckeditorSettings) == 'object') {
                    ckeditorSettings.externalPlugins.wpfilebase = ajaxurl + '/../../wp-content/plugins/wp-filebase/extras/ckeditor/';
                    ckeditorSettings.additionalButtons.push(["WPFilebase"]);
                }
                //]]>
            </script>
            <?php
        }
    }

    static function AdminDashboardSetup()
    {
        wpfb_loadclass('AdminDashboard');
        WPFB_AdminDashboard::Setup(false);
    }


static function pluginUpdateMessage($plugin_data, $response)
{
    if(empty( $response->package )) {
        $u = WPFB_ProLib::getLicenseExtendUrl();
        echo "<br><b>Please <a href='".$u."'>extend your license</a>.</b>";
        //print_r($plugin_data);
        //print_r($response);
    }
}

}