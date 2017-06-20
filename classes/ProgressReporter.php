<?php

class WPFB_ProgressReporter {

    const FIELD_UPDATE_INTERVAL = 0.3; //s

    var $quiet;
    var $debug;

    var $throw_exceptions = false;
    var $text_output = false;

    var $progress_cur;
    var $progress_end;
    var $progress_bar;
    var $files = array();
    var $last_field_id = null;
    var $field_update_times = array();

    var $memprof;

    var $nl;

    /**
     * @var progressbar
     */
    var $mem_bar = null;

    function __construct($suppress_output = false) {
        $this->quiet = !!$suppress_output;
        $this->debug = !empty($_REQUEST['debug']);
        $this->nl = "<br>";

        if ($this->memprof = (isset($_REQUEST['MEMPROF']) && function_exists('memprof_enable'))) {
            memprof_enable();
            $this->Log("Memprof enabled!");
        }
    }

    function enableThrowExceptions(){
        $this->throw_exceptions = true;
    }

    function enableTextOutput(){
        $this->text_output = true;
        $this->nl = "\n";
    }

    function Log($msg, $no_new_line = false) {
        if($this->text_output)
            $msg = strip_tags($msg);

        if (!$this->quiet)
            self::DEcho((!$no_new_line) ? ($msg . $this->nl) : $msg);
        WPFB_Core::LogMsg($msg, 'sync');
        $this->UpdateMemBar();
    }

    function LogError($err) {
        if (!$this->quiet) {
            self::DEcho($this->text_output ? "\033[31m$err\n" : "<span style='color:#d00;'>$err</span><br />");
            WPFB_Core::LogMsg("error: $err", 'sync');
            $this->UpdateMemBar();
        }
        if($this->throw_exceptions)
            throw new Exception($err);
    }

    function LogException(Exception $e) {
        if (!$this->quiet) {

            $msg = print_r($e->getMessage(), true);
            self::DEcho($this->text_output ? "\033[31m$msg\n" : ("<span style='color:#d00;'>" . $msg . "</span><br />"));
            WPFB_Core::LogMsg("error: $msg", 'sync');
            if ($this->debug) {
                var_dump($e);
                self::DEcho("<br />");
            }
            $this->UpdateMemBar();
        }

        if($this->throw_exceptions)
            throw $e;
    }

    function Debug() {
        if ($this->debug) {
            $this->UpdateMemBar();

            $args = func_get_args();
            $format = array_shift($args);
            $callers = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($callers[1]['class']) ? ($callers[1]['class'] . '::' . $callers[1]['function']) : $callers[1]['function'];
            $this->Log("[$caller] " . vsprintf($format, $args));
        }
    }

    function DebugLog($msg) {

    }

    function InitProgress($progress_end) {
        $this->progress_end = $progress_end;
        if (!$this->quiet && !$this->text_output) {
            //if(is_null($this->progress_bar)) {
            if (!class_exists('progressbar'))
                include_once(WPFB_PLUGIN_ROOT . 'extras/progressbar.class.php');
            $this->progress_bar = new progressbar(0, 100);
            $this->progress_bar->print_code();
            //}
        }
    }

    function SetProgress($progress) {
        $this->UpdateMemBar();
        $this->progress_cur = $progress;
        if (!$this->quiet && !is_null($this->progress_bar)) {
            $this->progress_bar->set(100 * $progress / $this->progress_end);
        }
    }

    function SetSubProgress($sub_progress, $sub_total) {
        $this->UpdateMemBar();
        if (!$this->quiet && !is_null($this->progress_bar))
            $this->progress_bar->set(100 * ($this->progress_cur + $sub_progress) / $this->progress_end);
    }

    function InitProgressField($format = 'Value = %#%', $val = 0, $obey_upd_interval = false) {
        $this->last_field_id = $id = md5(uniqid());
        $this->Log(str_replace('%#%', "<span id='$id'>$val</span>", $format), true);

        if(is_numeric($val) && !$this->text_output)
            echo (" (<span id='$id-rate-scope'></span>) <script> document.getElementById('$id-rate-scope').measure = {log: [{t: Date.now(),v: 0+'$val'}], update: function(v) {
                var el = document.getElementById('$id-rate-scope'), m = el.measure, now = Date.now(), l = m.log[0];
                m.log.push({t: now,v: 0+v});
                for(var i = m.log.length-1; i >= 0; i--) {
                    if((now - m.log[i].t) > 1000*60) {
                        l = m.log[i];
                        break;
                    }
                }
                var elapsed = now - l.t, delta = v - l.v, rate = delta / elapsed * 1000;
                el.innerHTML = (Math.round(rate*10)/10)+ ' per second (in the last minute)';
            }}; </script>");

        echo $this->nl;

        if ($obey_upd_interval && !$this->debug)
            $this->field_update_times[$id] = 1;
        return $id;
    }

    function SetField($val, $id = false) {
        $this->UpdateMemBar();

        if (!$id)
            $id = $this->last_field_id;

        if ($id && !$this->quiet && !$this->text_output && (!isset($this->field_update_times[$id]) || (($t = microtime(true)) - $this->field_update_times[$id]) >= self::FIELD_UPDATE_INTERVAL)) {
            $val = str_replace('\\', '/', $val);
            self::DEcho("<script> document.getElementById('$id').innerHTML = '$val'; </script>");
            if(is_numeric($val)) {
                self::DEcho("<script> document.getElementById('$id-rate-scope').measure.update('$val'); </script>");
            }
            if (isset($t))
                $this->field_update_times[$id] = $t;
            return true;
        }
        return false;
    }

    function FileChanged($file, $action) {
        $this->UpdateMemBar();

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

    static function GetMemStats()
    {
        static $limit = -2;
        if ($limit == -2) {
            $limit = wpfb_call("Misc", "ParseIniFileSize",
                ini_get('memory_limit'));
        }

        $usage = max(memory_get_usage(true), memory_get_usage());



        return array(            'limit' => $limit,            'usage' => $usage        );;
    }

    function InitMemBar() {
        if (!$this->mem_bar && !$this->quiet) {
            if (!class_exists('progressbar')) {
                include_once(WPFB_PLUGIN_ROOT . 'extras/progressbar.class.php');
            }

            $ms = self::GetMemStats();
            $this->mem_bar = new progressbar($ms['usage'], $ms['limit'], 200, 20,
                '#d90', 'white', 'wpfb-progress-bar-mem');
            echo "<div><br /></div>";
            echo "<div>Memory Usage (limit = "
                . WPFB_Output::FormatFilesize($ms['limit']) . "):</div>";
            $this->mem_bar->print_code();
            echo "<div><br /></div>";
        }
        return $this->mem_bar;
    }

    function UpdateMemBar() {
        static $was_80 = false;
        static $was_90 = false;

        if($this->mem_bar) {
            $ms = self::GetMemStats();
            $pu = round($ms['usage']/$ms['limit'] * 100);

            if($this->memprof && $pu > 90) {
                memprof_dump_callgrind(fopen("/tmp/callgrind_mem.out", "w"));
                die("memprof @$pu% written to /tmp/callgrind_mem.out");
            }

            if(!$was_80 && $pu > 80) {
                $was_80 = true;
                $this->Log("Notice: high memory usage $pu% > 80%.");
            }

            if(!$was_90 && $pu > 90) {
                $was_90 = true;
                $this->Log("WARNING: running out of memory (usage $pu% > 90%)!");
            }

            if($this->memprof) {
                self::DEcho("Mem Usage: $pu %<br />");
                //sleep(1);
            }

            $this->mem_bar->set($ms['usage']);
        }
    }

    var $stopwatches = array();

    function StopwatchStart() {
        array_push($this->stopwatches, microtime(true));
    }

    function StopwatchEnd($msg) {
        $start = array_pop($this->stopwatches);
        $this->Log(sprintf($msg, human_time_diff($start, microtime(true))));
    }

}
