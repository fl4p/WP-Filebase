<?php

class WPFB_AdminGuiManage
{

    static function NewExtensionsAvailable()
    {
        $last_gui_time = get_user_option('wpfb_ext_tagtime');
        if (!$last_gui_time) return true;
        $tag_time = get_transient('wpfb_ext_tagtime');
        if (!$tag_time) {
            wpfb_loadclass('ExtensionLib');
            $res = WPFB_ExtensionLib::QueryAvailableExtensions();
            if (!$res || empty($res->info)) return false;
            $tag_time = $res->info['tag_time'];
            set_transient('wpfb_ext_tagtime', $tag_time, 0  + 6 * HOUR_IN_SECONDS);
        }

        return (!$last_gui_time || $last_gui_time != $tag_time);
    }

    static function Display()
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/dashboard.php');
        wpfb_loadclass('AdminDashboard');


        add_thickbox();
        wp_enqueue_script('dashboard');
        if (wp_is_mobile())
            wp_enqueue_script('jquery-touch-punch');

        //register_shutdown_function( create_function('','$error = error_get_last(); if( $error && $error[\'type\'] != E_STRICT ){print_r( $error );}else{return true;}') );

        wpfb_loadclass('File', 'Category', 'Admin', 'Output');

        $_POST = stripslashes_deep($_POST);
        $_GET = stripslashes_deep($_GET);
        $action = (!empty($_POST['action']) ? $_POST['action'] : (!empty($_GET['action']) ? $_GET['action'] : ''));
        $clean_uri = remove_query_arg(array('message', 'action', 'file_id', 'cat_id', 'deltpl', 'hash_sync', 'doit', 'ids', 'files', 'cats', 'batch_sync' /* , 's'*/)); // keep search keyword


        WPFB_Admin::PrintFlattrHead();
        ?>
        <script type="text/javascript">
            /* Liking/Donate Bar */
            if (typeof(jQuery) != 'undefined') {
                jQuery(document).ready(function () {
                    if (getUserSetting("wpfilebase_hidesuprow", false) == 1) {
                        jQuery('#wpfb-liking').hide();
                        jQuery('#wpfb-liking-toggle').addClass('closed');
                    }
                    jQuery('#wpfb-liking-toggle').click(function () {
                        jQuery('#wpfb-liking').slideToggle();
                        jQuery(this).toggleClass('closed');
                        setUserSetting("wpfilebase_hidesuprow", 1 - getUserSetting("wpfilebase_hidesuprow", false), 0);
                    });
                });
            }
        </script>

        
        <div class="wrap">
            <div id="icon-wpfilebase" class="icon32"><br/></div>
            <h2><?php echo WPFB_PLUGIN_NAME; ?></h2>

            <?php


            switch ($action) {
                default:
                    $clean_uri = remove_query_arg('pagenum', $clean_uri);

                    $upload_dir = WPFB_Core::UploadDir();
                    $upload_dir_rel = str_replace(ABSPATH, '', $upload_dir);
                    $chmod_cmd = "CHMOD " . WPFB_PERM_DIR . " " . $upload_dir_rel;
                    if (!is_dir($upload_dir)) {
                        $result = WPFB_Admin::Mkdir($upload_dir);
                        if ($result['error'])
                            $error_msg = sprintf(__('The upload directory <code>%s</code> does not exists. It could not be created automatically because the directory <code>%s</code> is not writable. Please create <code>%s</code> and make it writable for the webserver by executing the following FTP command: <code>%s</code>', 'wp-filebase'), $upload_dir_rel, str_replace(ABSPATH, '', $result['parent']), $upload_dir_rel, $chmod_cmd);
                        else
                            wpfb_call('Setup', 'ProtectUploadPath');
                    } elseif (!is_writable($upload_dir)) {
                        $error_msg = sprintf(__('The upload directory <code>%s</code> is not writable. Please make it writable for PHP by executing the follwing FTP command: <code>%s</code>', 'wp-filebase'), $upload_dir_rel, $chmod_cmd);
                    }

                    if (!empty($error_msg)) echo '<div class="error default-password-nag"><p>' . $error_msg . '</p></div>';

                    if (!empty(WPFB_Core::$settings->tag_conv_req)) {
                        echo '<div class="updated"><p><a href="' . add_query_arg('action', 'convert-tags') . '">';
                        _e('WP-Filebase content tags must be converted', 'wp-filebase');
                        echo '</a></p></div><div style="clear:both;"></div>';
                    }
                    ?>
                    <?php
                    if (self::PluginHasBeenUsedAWhile(true))
                        self::ProUpgradeNag();

                    if (self::PluginHasBeenUsedAWhile()) { ?>
                        <div id="wpfb-support-col">
                            <div id="wpfb-liking-toggle"></div>
                            <h3><?php _e('Like WP-Filebase?', 'wp-filebase') ?></h3>
                            <div id="wpfb-liking">
                                <!-- <div style="text-align: center;"><iframe src="http://www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwordpress.org%2Fextend%2Fplugins%2Fwp-filebase%2F&amp;send=false&amp;layout=button_count&amp;width=150&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font&amp;height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:140px; height:21px; display:inline-block; text-align:center;" <?php echo ' allowTransparency="true"'; ?>></iframe></div> -->

                                <div style="text-align: center;"><a href="https://twitter.com/wpfilebase"
                                                                    class="twitter-follow-button"
                                                                    data-show-count="false">Follow @wpfilebase</a>
                                    <script type="text/javascript">!function (d, s, id) {
                                            var js, fjs = d.getElementsByTagName(s)[0];
                                            if (!d.getElementById(id)) {
                                                js = d.createElement(s);
                                                js.id = id;
                                                js.src = "//platform.twitter.com/widgets.js";
                                                fjs.parentNode.insertBefore(js, fjs);
                                            }
                                        }(document, "script", "twitter-wjs");</script>
                                </div>

                                <p>Please <a href="http://wordpress.org/support/view/plugin-reviews/wp-filebase">give it
                                        a good rating</a>.</p>
                                <p>For Cloud support and lots of other advanced features consider an</p>
                                <p style="text-align: center;"><a href="https://wpfilebase.com/?ref=dblike"
                                                                  class="button-primary">Upgrade to Pro</a></p>
                                <p style="text-align: center;"><a href="http://demo.wpfilebase.com/?ref=dblike"
                                                                  class="button">Live Pro Demo</a></p>
                                <p style="text-align:right;float:right;font-style:italic;">Thanks, Fabian</p>
                                <!-- <div style="text-align: center;">
	<?php //WPFB_Admin::PrintPayPalButton() ?>
	<?php //WPFB_Admin::PrintFlattrButton() ?>
	</div> -->
                            </div>
                        </div>
                    <?php }
                    ?>


                    <div id="dashboard-widgets-wrap">
                        <?php wp_dashboard(); ?>
                    </div><!-- dashboard-widgets-wrap -->

                    <?php
                    break;

            case 'convert-tags':
                ?><h2><?php _e('Tag Conversion'); ?></h2><?php
                if (empty($_REQUEST['doit'])) {
                    echo '<div class="updated"><p>';
                    _e('<strong>Important:</strong> before updating, please <a href="http://codex.wordpress.org/WordPress_Backups">backup your database and files</a>. For help with updates, visit the <a href="http://codex.wordpress.org/Updating_WordPress">Updating WordPress</a> Codex page.');
                    echo '</p></div>';
                    echo '<p><a href="' . add_query_arg('doit', 1) . '" class="button">' . __('Continue') . '</a></p>';
                    break;
                }
                $result = wpfb_call('Setup', 'ConvertOldTags');
                ?>
                <p><?php printf(__('%d Tags in %d Posts has been converted.'), $result['n_tags'], count($result['tags'])) ?></p>
                <ul>
                    <?php
                    if (!empty($result['tags'])) foreach ($result['tags'] as $post_title => $tags) {
                        echo "<li><strong>" . esc_html($post_title) . "</strong><ul>";
                        foreach ($tags as $old => $new) {
                            echo "<li>$old =&gt; $new</li>";
                        }
                        echo "</ul></li>";
                    }
                    ?>
                </ul>
                <?php
            if (!empty($result['errors'])) { ?>
                <h2><?php _e('Errors'); ?></h2>
                <ul><?php foreach ($result['errors'] as $post_title => $err) echo "<li><strong>" . esc_html($post_title) . ": </strong> " . esc_html($err) . "<ul>"; ?></ul>
            <?php
            }
            $opts = WPFB_Core::GetOpt();
            unset($opts['tag_conv_req']);
            update_option(WPFB_OPT_NAME, $opts);
            WPFB_Core::$settings = (object)$opts;

            break; // convert-tags


            case 'del':
                if (!empty($_REQUEST['files']) && WPFB_Core::CurUserCanUpload()) {
                    $ids = explode(',', $_REQUEST['files']);
                    $nd = 0;
                    foreach ($ids as $id) {
                        $id = intval($id);
                        if (($file = WPFB_File::GetFile($id)) != null && $file->CurUserCanDelete()) {
                            $file->Remove(true);
                            $nd++;
                        }
                    }
                    WPFB_File::UpdateTags();

                    echo '<div id="message" class="updated fade"><p>' . sprintf(__('%d Files removed'), $nd) . '</p></div>';
                }
                if (!empty($_REQUEST['cats']) && WPFB_Core::CurUserCanCreateCat()) {
                    $ids = explode(',', $_REQUEST['cats']);
                    $nd = 0;
                    foreach ($ids as $id) {
                        $id = intval($id);
                        if (($cat = WPFB_Category::GetCat($id)) != null) {
                            $cat->Delete();
                            $nd++;
                        }
                    }

                    echo '<div id="message" class="updated fade"><p>' . sprintf(__('%d Categories removed'), $nd) . '</p></div>';
                }

                case 'sync':
                    echo '<h2>' . __('Synchronisation') . '</h2>';
                    wpfb_loadclass('Sync');
                    $result = WPFB_Sync::Sync(!empty($_GET['hash_sync']), true);
                    if (!is_null($result))
                        WPFB_Sync::PrintResult($result);

                    if (empty($_GET['hash_sync']))
                        echo '<p><a href="' . add_query_arg('hash_sync', 1) . '" class="button">' . __('Complete file sync', 'wp-filebase') . '</a> ' . __('Checks files for changes, so more reliable but might take much longer. Do this if you uploaded/changed files with FTP.', 'wp-filebase') . '</p>';

                    if (empty($_GET['debug']))
                        echo '<p><a href="' . add_query_arg('debug', 1) . '" class="button">' . __('Debug Sync', 'wp-filebase') . '</a> ' . __('Run to get more Debug Info in case Sync crashes', 'wp-filebase') . '</p>';

                    break; // sync


                case 'batch-upload':
                    wpfb_loadclass('BatchUploader');
                    $batch_uploader = new WPFB_BatchUploader();
                    $batch_uploader->Display();
                    break;
                case 'reset-hits':
                    global $wpdb;
                    $n = 0;
                    if (current_user_can('manage_options'))
                        $n = $wpdb->query("UPDATE `$wpdb->wpfilebase_files` SET file_hits = 0 WHERE 1=1");
                    echo "<p>";
                    printf(__('Done. %d Files affected.'), $n);
                    echo "</p>";
                    break;

                case 'install-extensions':
                    wpfb_call('AdmInstallExt', 'Display');
                    break;

            } // switch


            if (!empty($_GET['action']))
                echo '<p><a href="' . $clean_uri . '" class="button">' . __('Go back'/*def*/) . '</a></p>';
            ?>
        </div> <!-- wrap -->
        <?php
    }

    static function ProgressBar($progress, $label)
    {
        $progress = round(100 * $progress);
        echo "<div class='wpfilebase-progress'><div class='progress'><div class='bar' style='width: $progress%'></div></div><div class='label'><strong>$progress %</strong> ($label)</div></div>";
    }

    static function PluginHasBeenUsedAWhile($long_while = false)
    {
        global $wpdb;
        static $n = -1, $first_file_time = -1;
        if ($n === -1) {
            $n = WPFB_File::GetNumFiles();
            $first_file_time = mysql2date('U', $wpdb->get_var("SELECT file_date FROM $wpdb->wpfilebase_files ORDER BY file_date ASC LIMIT 1"));
        }
        if ($n < ($long_while ? 20 : 5)) return false;
        return ($first_file_time > 1 && (time() - $first_file_time) > (86400 * ($long_while ? 20 : 4))); // 4 days
    }

    static function ProUpgradeNag()
    {
        static $first = true;
        if ($first) $first = false;
        else return;

        if (!current_user_can('install_plugins') || (time() - get_user_option('wpfb_dismiss_pro_nag', get_current_user_id())) < (86400 * 30 * 5))
            return;

        if (!empty($_REQUEST['wpfb_dismiss_pro_nag'])) {
            update_user_option(get_current_user_id(), 'wpfb_dismiss_pro_nag', time());
            return;
        }
        ?>
        <div class="notice notice-info"><p>
                <?php _e('Upgrade to WP-Filebase Pro for cloud support, advanced permissions handling and much more.', 'wp-filebase'); ?>
                <a href="https://wpfilebase.com/?ref=dbnote" target="_blank" class="button-primary">Learn More</a>
                <a href="http://demo.wpfilebase.com/?ref=dbnote" target="_blank" class="button-primary">Live Pro
                    Demo</a>
                <a href="<?php echo esc_attr(add_query_arg('wpfb_dismiss_pro_nag', 1)); ?>" class="dismiss"
                   style="display:block;float:right;margin:0 10px 0 15px;"><?php _e('Dismiss'); ?></a>
            </p>
        </div>
        <?php
    }
}
