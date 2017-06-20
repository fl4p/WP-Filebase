<?php class WPFB_Misc
{

    /**
     * @param array $info
     * @param array $keywords
     *
     * @return mixed
     */
    static function GetKeywords($info, &$keywords)
    {
        foreach ($info as $key => $val) {
            if (is_array($val) || is_object($val)) {
                self::GetKeywords($val, $keywords);
                self::GetKeywords(array_keys($val), $keywords); // this is for archive files, where file names are array keys
            } else if (is_string($val)) {
                $val_a = explode(' ', strtolower(preg_replace('/\W+/u', ' ', $val)));
                foreach ($val_a as $v) {
                    if (!in_array($v, $keywords))
                        array_push($keywords, $v);
                }
            }
        }
        return $keywords;
    }

    static function GenRewriteRules()
    {
        global $wp_rewrite;
        $fb_pid = intval(WPFB_Core::$settings->file_browser_post_id);
        if ($fb_pid > 0) {
            $is_page = (get_post_type($fb_pid) == 'page');
            $redirect = 'index.php?' . ($is_page ? 'page_id' : 'p') . "=$fb_pid";
            $base = trim(substr(get_permalink($fb_pid), strlen(home_url())), '/');
            $pattern = "$base/(.+)$";
            $wp_rewrite->rules = array($pattern => $redirect) + $wp_rewrite->rules;
        }
    }

    static function GetTraffic()
    {
        $traffic = isset(WPFB_Core::$settings->traffic_stats) ? WPFB_Core::$settings->traffic_stats : array();
        $time = intval(@$traffic['time']);
        $year = intval(date('Y', $time));
        $month = intval(date('m', $time));
        $day = intval(date('z', $time));

        $same_year = ($year == intval(date('Y')));
        if (!$same_year || $month != intval(date('m')))
            $traffic['month'] = 0;
        if (!$same_year || $day != intval(date('z')))
            $traffic['today'] = 0;

        return $traffic;
    }


    static function UserRole2Level($role)
    {
        switch ($role) {
            case 'administrator':
                return 8;
            case 'editor':
                return 5;
            case 'author':
                return 2;
            case 'contributor':
                return 1;
            case 'subscriber':
                return 0;
            default:
                return -1;
        }
    }

    static function ParseIniFileSize($val)
    {
        if (is_numeric($val))
            return $val;

        $val_len = strlen($val);
        $bytes = substr($val, 0, $val_len - 1);
        $unit = strtolower(substr($val, $val_len - 1));
        switch ($unit) {
            case 'k':
                $bytes *= 1024;
                break;
            case 'm':
                $bytes *= 1048576;
                break;
            case 'g':
                $bytes *= 1073741824;
                break;
        }
        return $bytes;
    }

    static function IsUtf8($string)
    {
        return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string);
    }


    static function GetFileTypeStats()
    {
        global $wpdb;


        $stats = get_transient('wpfb_file_type_stats');
        if ($stats)
            return $stats;

        $stats = array();

        $results = $wpdb->get_results("
		SELECT LOWER(SUBSTRING_INDEX(file_name,'.',-1)) as ext, COUNT(file_id) as cnt
		FROM `$wpdb->wpfilebase_files`
		WHERE LENGTH(SUBSTRING_INDEX(file_name,'.',-1)) < 10
		GROUP by LOWER(SUBSTRING_INDEX(file_name,'.',-1)) ORDER BY `cnt` DESC LIMIT 40"
            , OBJECT_K);

        foreach ($results as $r) {
            $stats[$r->ext] = 0 + $r->cnt;
        }

        set_transient('wpfb_file_type_stats', $stats, 24 * HOUR_IN_SECONDS); // should (must) be on daily-base!

        wpfb_call('ExtensionLib', 'SendStatistics');

        return $stats;
    }


    /**
     * @param $host
     * @param $path
     * @return string
     * @throws Exception
     */
    static function HttpTestRequest($host, $path)
    {
        $fp = fsockopen($host, 80, $errno, $errstr, 3);
        if (!$fp) {
            throw new Exception("Connection failed: $errstr ($errno)");
        } else {
            $out = "GET $uri HTTP/1.1\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Connection: Close\r\n\r\n";
            fwrite($fp, $out);
            $s = '';
            while (!feof($fp)) {
                $s .= fgets($fp, 128);
            }
            fclose($fp);
            return $s;
        }
    }
}

