<?php

class WPFB_ProgressReporter {

    const FIELD_UPDATE_INTERVAL = 0.3; //s

    var $quiet;
    var $debug;
    var $progress_cur;
    var $progress_end;
    var $progress_bar;
    var $files = array();
    var $last_field_id = null;
    var $field_update_times = array();

    function __construct($suppress_output = false) {
        $this->quiet = !!$suppress_output;
        $this->debug = !empty($_REQUEST['debug']);
    }

    function Log($msg, $no_new_line = false) {
        if (!$this->quiet)
            self::DEcho((!$no_new_line) ? ($msg . "<br />") : $msg);
    }

    function LogError($err) {
        if ($this->quiet)
            return;
        self::DEcho("<span style='color:#d00;'>$err</span><br />");
    }

    function LogException(Exception $e) {
        if ($this->quiet)
            return;
        self::DEcho("<span style='color:#d00;'>" . $e->getMessage() . "</span><br />");
        if ($this->debug) {
            var_dump($e);
            self::DEcho("<br />");
        }
    }

    function Debug() {
        if ($this->debug) {
            $args = func_get_args();
            $format = array_shift($args);
            $callers = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($callers[1]['class']) ? ($callers[1]['class'] . '::' . $callers[1]['function']) : $callers[1]['function'];
            $this->Log("[$caller] " . vsprintf($format, $args));
        }
    }

    function InitProgress($progress_end) {
        $this->progress_end = $progress_end;
        if (!$this->quiet) {
            //if(is_null($this->progress_bar)) {
            if (!class_exists('progressbar'))
                include_once(WPFB_PLUGIN_ROOT . 'extras/progressbar.class.php');
            $this->progress_bar = new progressbar(0, 100);
            $this->progress_bar->print_code();
            //}
        }
    }

    function SetProgress($progress) {
        $this->progress_cur = $progress;
        if (!$this->quiet && !is_null($this->progress_bar)) {
            $this->progress_bar->set(100 * $progress / $this->progress_end);
        }
    }

    function SetSubProgress($sub_progress, $sub_total) {
        if (!$this->quiet && !is_null($this->progress_bar))
            $this->progress_bar->set(100 * ($this->progress_cur + $sub_progress) / $this->progress_end);
    }

    function InitProgressField($format = 'Value = %#%', $val = 0, $obey_upd_interval = false) {
        $this->last_field_id = $id = md5(uniqid());
        $this->Log(str_replace('%#%', "<span id='$id'>$val</span>", $format));
        if ($obey_upd_interval && !$this->debug)
            $this->field_update_times[$id] = 1;
        return $id;
    }

    function SetField($val, $id = false) {
        if (!$id)
            $id = $this->last_field_id;
        if ($id && !$this->quiet && (!isset($this->field_update_times[$id]) || (($t = microtime(true)) - $this->field_update_times[$id]) >= self::FIELD_UPDATE_INTERVAL)) {
            $val = str_replace('\\', '/', $val);
            self::DEcho("<script> document.getElementById('$id').innerHTML = '$val'; </script>");
            if (isset($t))
                $this->field_update_times[$id] = $t;
            return true;
        }
    }

    function FileChanged($file, $action) {
        if (empty($this->files[$action]))
            $this->files[$action] = array();
        $this->files[$action][] = $file;
    }

    function ChangedFilesReport() {
        foreach ($this->files as $tag => $group) {
            $t = str_replace('_', ' ', $tag);
            $t{0} = strtoupper($t{0});

            echo '<h2>' . __($t) . '</h2><ul>';
            foreach ($group as $item)
                echo '<li>' . (is_object($item) ? ('<a href="' . $item->GetEditUrl() . '">' . $item->GetLocalPathRel() . '</a>') : $item) . '</li>';
            echo '</ul>';
        }

        foreach ($this->files as $t => $group) {
            $n = count($group);
            echo '<p>';
            printf(__('%d files <i>%s</i>', 'wp-filebase'), $n, $t);
            echo '</p>';
        }
    }

    static function DEcho($str) {
        echo $str;
        @ob_flush();
        @flush();
    }

}
