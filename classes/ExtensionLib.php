<?php

if(!class_exists('WPFB_ExtensionLib')) {
    class WPFB_ExtensionLib {

        private static function apiRequest($act, $post_data = null, $use_ssl = true) {
            global $wp_version;

            $site = rawurlencode(base64_encode(get_option('siteurl')));
            $license_key = false;
            $url = "http" . ($use_ssl ? "s://ssl-account.com" : ":/") . "/interface.fabi.me/wpfilebase-pro/$act.php";
            $get_args = array('pl_slug' => basename(WPFB_PLUGIN_ROOT), 'pl_ver' => WPFB_VERSION, 'wp_ver' => $wp_version, 'key' => $license_key, 'site' => $site);

            // try to get from cache
            $cache_key =  'wpfb_apireq_'.md5($act.'||'.serialize($get_args).'||'.serialize($post_data).'||'.__FILE__);

            $no_cache = isset($_REQUEST['no_api_cache']) || isset($_REQUEST['force-check']) || isset($post_data['nocache']);
            if($no_cache) {
                self::flushCache();
                delete_transient($cache_key);
                $get_args['nocache'] = 1;
            }

            $res = $no_cache ? false : get_transient($cache_key);
            if ($res !== false) {
                return $res;
            }

            if (empty($post_data)) {
                $res = wp_remote_get($url, $get_args);
            } else {
                $res = wp_remote_post(add_query_arg($get_args, $url), array('body' => $post_data));
            }

            if (is_wp_error($res)) {
                if ($use_ssl) // retry without ssl
                    return self::apiRequest($act, $post_data, false);
                echo "<b>WP-Filebase API request error:</b>";
                print_r($res);
				!isset($post_data['nocache']) && set_transient($cache_key, 0, 60 * MINUTE_IN_SECONDS); // on failure retry every hour
                WPFB_Core::LogMsg("API request error (act: $act): ".json_encode($res));
                return false;
            }

            $res = empty($res['body']) ? false : json_decode($res['body']);

            if(!$res && !is_array($res)) {
                WPFB_Core::LogMsg("API request error (act: $act): empty/invalid response body!");
            }

            if(!isset($post_data['nocache']))
                set_transient($cache_key, $res, $license_key ? (10 * MINUTE_IN_SECONDS) : (6 * HOUR_IN_SECONDS));

            return $res;
        }

        private static function flushCache()
        {
            global $wpdb;
            $transient_names = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpfb_apireq_%'");
            $p   = strlen('_transient_');
            foreach ($transient_names as $tn) {
               delete_transient(substr($tn, $p));
            }
        }

        static function GetExtensionsVersionNumbers() {
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $installed_versions = array();
            foreach (get_plugins() as $p => $details) {
                if (substr($p, 0, 5) === "wpfb-") {
                    $installed_versions[$p] = $details['Version'];
                }
            }
            return $installed_versions;
        }

        static function GetLatestVersionInfoExt() {
            $ext_vers = json_encode(self::GetExtensionsVersionNumbers());
            WPFB_Core::LogMsg('Update channel query for '.json_encode($ext_vers).' ...', 'updater');
            $res = self::apiRequest('update-check-ext', array('extensions' => $ext_vers));
            WPFB_Core::LogMsg('Update channel query result: '.json_encode($res), 'updater');
            return empty($res) ? array() : (array) $res;
        }

        static function QueryAvailableExtensions($bought_extensions_only = false) {
            $ext_vers = json_encode(self::GetExtensionsVersionNumbers());
            $res = self::apiRequest('exts', array('bought' => $bought_extensions_only, 'extensions' => $ext_vers));
            if (!empty($res)) {
                $res = (object) $res;
                $res->info = (array) $res->info;
                foreach ($res->plugins as $i => $p) {
                    $res->plugins[$i] = (object) $p;
                    $res->plugins[$i]->ratings = (array) $res->plugins[$i]->ratings;
                    $res->plugins[$i]->icons = (array) $res->plugins[$i]->icons;
                }
            } else {
                $res = null;
            }
            return $res;
        }

        static function GetApiPluginInfo($slug) {
            $info = self::apiRequest('version-info', array('plugin_slug' => $slug));
            if (empty($info))
                return false;
            if (!empty($info->sections) && is_object($info->sections))
                $info->sections = (array) $info->sections;
            return $info;
        }

        static function PluginDeactivated() {
            delete_option('wpfilebase_is_licensed');
            // the timestamp is to avoid caching
            return self::apiRequest('disable-site', array('nocache' => 1));
        }

        static function SendStatistics() {
            return self::apiRequest('stats-sink', array(
                'nocache' => 1,
                'file_extensions' => get_transient('wpfb_file_type_stats')
            ));
        }
    }
}
