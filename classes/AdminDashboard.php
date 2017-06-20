<?php

/**
 * Description of AdminDashboard
 *
 * @author Fabian Schlieper
 */
class WPFB_AdminDashboard
{


    static function Setup($custom = false)
    {
        $screen = get_current_screen();

        //$screen->get_option('layout_colums', 2);
        //add_filter('screen_layout_columns', array(__CLASS__, 'screenLayoutColumns'), 10, 2);

        if ($custom && !wp_is_mobile())
            add_screen_option('layout_columns', array('max' => 2, 'default' => is_network_admin() ? 1 : 2));

        if (WPFB_Core::CurUserCanUpload() && !is_network_admin())
            add_meta_box('wpfb-add-file-widget', ($custom ? '' : (WPFB_PLUGIN_NAME . ': ')) . __('Add File', 'wp-filebase'), array(__CLASS__, 'WidgetAddFile'), $screen, 'normal', 'default', array(!$custom));

        if ($custom) {
            if (!empty($_GET['wpfb-hide-how-start']))
                update_user_option(get_current_user_id(), WPFB_OPT_NAME . '_hide_how_start', 1);
            $show_how_start = !(bool)get_user_option(WPFB_OPT_NAME . '_hide_how_start');


            if(!is_network_admin()) {
                add_meta_box('wpfb-tools', __('Tools'), array(__CLASS__, 'WidgetTools'), $screen, 'side', 'default');
                add_meta_box('wpfb-stats', __('Statistics', 'wp-filebase'), array(__CLASS__, 'WidgetStats'), $screen, 'side', 'default');
                add_meta_box('wpfb-getstarted', sprintf(__('How to get started with %s?', 'wp-filebase'), WPFB_PLUGIN_NAME), array(__CLASS__, 'WidgetGetStarted'), $screen, 'side', $show_how_start ? 'high' : 'low');
                add_meta_box('wpfb-about', __('About', 'wp-filebase'), array(__CLASS__, 'WidgetAbout'), $screen, 'side', 'default');
            }

            add_meta_box('wpfb-logs', __('Log Files', 'wp-filebase'), array(__CLASS__, 'WidgetLogFiles'), $screen, 'side', 'low');


            //add_meta_box('wpfb-', __('','wp-filebase'), array(__CLASS__, ''), $screen, 'normal', 'default' );
            //$screen->render_screen_meta();
        }
    }

    static function WidgetTools()
    {

        $cron_sync_desc = '';
        if (WPFB_Core::$settings->cron_sync) {
            $cron_sync_desc .= __('Automatic sync is enabled. Cronjob scheduled hourly.');
            $sync_stats = (get_option('wpfilebase_cron_sync_stats'));
            $cron_sync_desc .= ((!empty($sync_stats)) ? (" (" . sprintf(__('Last cron sync %s ago took %s and used %s of RAM.', 'wp-filebase'), human_time_diff($sync_stats['t_start']), human_time_diff($sync_stats['t_start'], $sync_stats['t_end']), WPFB_Output::FormatFilesize($sync_stats['mem_peak']))

                    . ")") : '') . " "
                . (($next = wp_next_scheduled(WPFB . '_cron')) ? sprintf(__('Next cron sync scheduled in %s.', 'wp-filebase'), human_time_diff(time(), $next)) : "");
        } else {
            $cron_sync_desc .= __('Cron sync is disabled.', 'wp-filebase');
        }

        $tools = array(
            array(
                'url' => add_query_arg(array('action' => 'sync', 'no-ob' => 1, )),
                'icon' => 'cached',
                'label' => __('Sync Filebase', 'wp-filebase'),
                'desc' => __('Synchronises the database with the file system. Use this to add FTP-uploaded files.', 'wp-filebase') . '<br />' . $cron_sync_desc
            )
        );


        if (current_user_can('install_plugins')) { // is admin?
            $new_tag = WPFB_AdminGuiManage::NewExtensionsAvailable() ? '<span class="wp-ui-notification new-exts">new</span>' : '';
            $tools[] = array(
                'url' => add_query_arg(array('action' => 'install-extensions')),
                'icon' => 'extension',
                'label' => __('Extensions', 'wp-filebase') . $new_tag,
                'desc' => __('Install Extensions to extend functionality of WP-Filebase', 'wp-filebase')
            );
        }
        ?>
        <ul>
            <?php foreach ($tools as $id => $tool) {
                ?>
                <li id="wpfb-tool-<?php echo $id; ?>"><a
                        href="<?php echo $tool['url']; ?>" <?php if (!empty($tool['confirm'])) { ?> onclick="return confirm('<?php echo $tool['confirm']; ?>')" <?php } ?>
                        class="button admin-scheme-fill-2-hover"><?php echo WPFB_Admin::Icon($tool['icon'], 32);
                        echo $tool['label']; ?></a></li>
            <?php } ?>
        </ul>
        <?php foreach ($tools as $id => $tool) { ?>
        <div id="wpfb-tool-desc-<?php echo $id; ?>" class="tool-desc">
            <?php echo $tool['desc']; ?>
        </div>
    <?php } ?>
        <script>
            if (!jQuery(document.body).hasClass('mobile')) {
                jQuery('#wpfb-tools li').mouseenter(function (e) {
                    jQuery('#wpfb-tools .tool-desc').hide();
                    jQuery('#wpfb-tool-desc-' + this.id.substr(10)).show();
                });
                jQuery('#wpfb-tools .tool-desc').first().show();
            }
        </script>

        <?php if (!empty(WPFB_Core::$settings->tag_conv_req)) { ?><p><a
        href="<?php echo add_query_arg('action', 'convert-tags') ?>"
        class="button"><?php _e('Convert old Tags', 'wp-filebase') ?></a>
        &nbsp; <?php printf(__('Convert tags from versions earlier than %s.', 'wp-filebase'), '0.2.0') ?></p> <?php } ?>
        <!--  <p><a href="<?php echo add_query_arg('action', 'add-urls') ?>" class="button"><?php _e('Add multiple URLs', 'wp-filebase') ?></a> &nbsp; <?php _e('Add multiple remote files at once.', 'wp-filebase'); ?></p>
		-->
        <?php
    }

    static function WidgetAddFile($compact = false)
    {
        // switch simple/extended form
        if (isset($_GET['exform'])) {
            $exform = (!empty($_GET['exform']) && $_GET['exform'] == 1);
            update_user_option(get_current_user_id(), WPFB_OPT_NAME . '_exform', $exform, true);
        } else
            $exform = (bool)get_user_option(WPFB_OPT_NAME . '_exform');

        wpfb_loadclass('Admin', 'File', 'Category');
        WPFB_Admin::PrintForm('file', null, (!$compact) ? array('exform' => false, 'in_widget' => false) : array('exform' => false, 'in_widget' => true));
    }

    static function WidgetStats()
    {
        global $wpdb;

        ?>
        <div id="col-container">
            <div id="col-right">
                <div class="col-wrap">
                    <h3><?php _e('Traffic', 'wp-filebase'); ?></h3>
                    <table class="wpfb-stats-table">
                        <?php
                        $traffic_stats = wpfb_call('Misc', 'GetTraffic');
                        $limit_day = (WPFB_Core::$settings->traffic_day * 1048576);
                        $limit_month = (WPFB_Core::$settings->traffic_month * 1073741824);
                        ?>
                        <tr>
                            <td><?php
                                if ($limit_day > 0)
                                    WPFB_AdminGuiManage::ProgressBar($traffic_stats['today'] / $limit_day, WPFB_Output::FormatFilesize($traffic_stats['today']) . '/' . WPFB_Output::FormatFilesize($limit_day));
                                else
                                    echo WPFB_Output::FormatFilesize($traffic_stats['today']);
                                ?></td>
                            <th scope="row"><?php _e('Today', 'wp-filebase'); ?></th>
                        </tr>
                        <tr>
                            <td><?php
                                if ($limit_month > 0)
                                    WPFB_AdminGuiManage::ProgressBar($traffic_stats['month'] / $limit_month, WPFB_Output::FormatFilesize($traffic_stats['month']) . '/' . WPFB_Output::FormatFilesize($limit_month));
                                else
                                    echo WPFB_Output::FormatFilesize($traffic_stats['month']);
                                ?></td>
                            <th scope="row"><?php _e('This Month', 'wp-filebase'); ?></th>
                        </tr>
                        <tr>
                            <td><?php echo WPFB_Output::FormatFilesize($wpdb->get_var("SELECT SUM(file_size) FROM $wpdb->wpfilebase_files")) ?></td>
                            <th scope="row"><?php _e('Total File Size', 'wp-filebase'); ?></th>
                        </tr>
                    </table>
                </div>
            </div><!-- /col-right -->


            <div id="col-left">
                <div class="col-wrap">

                    <h3><?php _e('Statistics', 'wp-filebase'); ?></h3>
                    <table class="wpfb-stats-table">
                        <tr>
                            <td><?php echo WPFB_File::GetNumFiles() ?></td>
                            <th scope="row"><?php _e('Files', 'wp-filebase'); ?></th>
                        </tr>
                        <tr>
                            <td><?php echo WPFB_Category::GetNumCats() ?></td>
                            <th scope="row"><?php _e('Categories'); ?></th>
                        </tr>
                        <tr>
                            <td><?php echo "" . (int)$wpdb->get_var("SELECT SUM(file_hits) FROM $wpdb->wpfilebase_files") ?></td>
                            <th scope="row"><?php _e('Downloads', 'wp-filebase'); ?></th>
                        </tr>
                    </table>
                </div>
            </div><!-- /col-left -->

        </div><!-- /col-container -->
        <div style="clear:both;"></div>
        <?php
    }

    static function WidgetGetStarted()
    {
        ?>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready(function () {
                jQuery('div.widgets-holder-wrap').children('.sidebar-name').click(function () {
                    jQuery(this).parent().find('.widgets-sortables').slideToggle();
                });
            });
            //]]>
        </script>

        <div id="wpfb-how-start">
            <ul>
                <li>
                    <a href="<?php echo esc_attr(admin_url("admin.php?page=wpfilebase_cats#addcat")); ?>"><?php _e('Create a Category', 'wp-filebase') ?></a>
                </li>

                <li><?php _e('Add a file. There are different ways:', 'wp-filebase'); ?>
                    <ul>
                        <li><?php printf(__('<a href="%s">Use the normal File Upload Form.</a> You you can either upload a file from you local harddisk or you can provide a URL to a file that will be sideloaded to your blog.', 'wp-filebase'), esc_attr(admin_url("admin.php?page=wpfilebase_files#addfile"))); ?></li>
                        <li><?php printf(__('Use FTP: Use your favorite FTP Client to upload any directories/files to <code>%s</code>. Afterwards <a href="%s">sync the filebase</a> to add the newly uploaded files to the database.', 'wp-filebase'), esc_html(WPFB_Core::$settings->upload_path), esc_attr(admin_url('admin.php?page=wpfilebase_manage&action=sync'))); ?></li>
                    </ul>
                </li>
                <li><?php printf(__('Goto <a href="%s">WP-Filebase Settings -> Filebrowser</a> and set the Page ID to get a nice AJAX Tree View of all your files.', 'wp-filebase'), esc_attr(admin_url('admin.php?page=wpfilebase_sets#' . sanitize_title(__('File Browser', 'wp-filebase'))))); ?></li>
                <li><?php printf(__('WP-Filebase adds a new button to the visual editor. When creating or editing posts/pages, use the Editor Plugin %s to insert single files, file lists and other stuff into your content.', 'wp-filebase'), '<img src="' . esc_attr(WPFB_PLUGIN_URI) . 'tinymce/images/btn.gif" style="vertical-align:middle;" />'); ?></li>
                <li><?php printf(__('Take a look at the <a href="%s">Widgets</a>. WP-Filebase adds three widgets for file listing, category listing and user uploads.', 'wp-filebase'), admin_url('widgets.php')); ?></li>
                <li><?php printf(__('<a href="%s">Manage the Templates</a> (for advanced users): You can modify any file- or category template to fit your Wordpress theme.', 'wp-filebase'), esc_attr(admin_url('admin.php?page=wpfilebase_tpls'))); ?></li>
            </ul>
            <?php if (!get_user_option(WPFB_OPT_NAME . '_hide_how_start')) { ?>
                <p style="text-align: right"><a
                        href="<?php echo esc_attr(add_query_arg('wpfb-hide-how-start', '1')) ?>"><?php _e('Move this message to the bottom (dismiss)', 'wp-filebase') ?></a>
                </p>
            <?php } ?>
        </div>


        <?php
    }

    static function WidgetAbout()
    {
        ?>        <p>
        <?php echo WPFB_PLUGIN_NAME . ' ' . WPFB_VERSION ?> by Fabian Schlieper <a href="http://fabi.me/">
            <?php if (strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') === false) { ?><img
                src="http://fabi.me/misc/wpfb_icon.gif?lang=<?php if (defined('WPLANG')) {
                    echo WPLANG;
                } ?>" alt="" /><?php } ?> fabi.me</a><br/>
        Includes the great file analyzer <a href="http://www.getid3.org/">getID3()</a> by James Heinrich.<br/>
    </p>
        <?php if (current_user_can('edit_files')) { ?>
        
        <p><a href="<?php echo admin_url('plugins.php?wpfb-uninstall=1') ?>" class="button"
              onclick="return confirm('You will be brough to the Plugin page. When you disable WP-Filebase there, you loose all your data.')"><?php _e('Initiate a complete uninstall', 'wp-filebase') ?></a>
        </p>

        <?php
    }
    }

    /**
     * Lists 20 normal log entries and max. 100 errors
     *
     * @param $for
     */
    static function showLog($for)
    {
        $filename = WPFB_Core::GetLogFile($for);
        $lines = is_file($filename) ? file($filename) : null;
        $date_len = strlen('[2015-12-28 21:53:09] ');

        if (empty($lines)) {
            echo "No log for '$for' yet!";
            return;
        }
        $n = count($lines);
        $ni = $n - 1;
        echo "<pre><strong>$for</strong> ", sprintf(__('%s ago'), human_time_diff(filemtime($filename))), ":\n";
        for ($i = $n - 1; $i >= max(0, $n - 100); $i--) {
            $msg = rtrim(substr($lines[$i], $date_len));
            $e = (stripos($msg, 'error') !== false || stripos($msg, 'failed') !== false || stripos($msg, 'exception') !== false || stripos($msg, 'unexpected') !== false || stripos($msg, 'warning') !== false || stripos($msg, 'not found') !== false);

            if ($i < ($n - 20) && !$e)
                continue;

            if ($ni != $i)
                echo "<b>\t[...]\n</b>";

            $e && ($msg = "<span class='error'>" . esc_html($msg) . "</span>");
            echo '<b>' . esc_html(substr($lines[$i], 0, $date_len)) . '</b>', str_replace('&lt;br&gt;', '<br>', $msg), "\n";
            $ni = $i - 1;
        }
        echo '</pre>';
    }

    static function WidgetLogFiles()
    {
        ?>
        <div style="width: auto; overflow: auto;"><?php

        if(!is_network_admin()) {
            echo '<p>';
            self::showLog('core');
            echo '</p>';
            echo '<p>';
            self::showLog('sync');
            echo '</p>';

            echo '<p>';
            self::showLog('ccron');
            echo '</p>';
        } else {
            echo '<p>';
            self::showLog('updater');
            echo '</p>';
        }
        ?></div><?php
    }

}
