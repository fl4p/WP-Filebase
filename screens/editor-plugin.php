<?php

// dont allow direct access and access from outside wp-admin context
if (!defined('ABSPATH') || !is_admin())
    exit;

if (!isset($_GET['inline']))
    define('IFRAME_REQUEST', true);

if (!function_exists('get_current_screen')) {
    function get_current_screen()
    {
        return null;
    }
}
if (!function_exists('add_meta_box')) {
    function add_meta_box()
    {
        return null;
    }
}

auth_redirect();

wpfb_loadclass('Core', 'File', 'Category', 'AdminLite', 'Admin', 'ListTpl', 'Output', 'Models');

wp_enqueue_script('common');
wp_enqueue_script('jquery-ui-widget');
wp_enqueue_script('jquery-color');
wp_enqueue_script('wpfb-treeview');
wp_enqueue_script('postbox');
wp_enqueue_script('wpfb-editor-plugin', WPFB_PLUGIN_URI . "js/editor-plugin.js", array(), WPFB_VERSION);

wp_enqueue_style('global');
wp_enqueue_style('wp-admin');
//wp_enqueue_style( 'colors' );
wp_enqueue_style('media');
wp_enqueue_style('ie');
wp_enqueue_style('wpfb-treeview');

//do_action('admin_init');

if (!current_user_can('publish_posts') && !current_user_can('edit_posts') && !current_user_can('edit_pages'))
    wp_die(__('Cheatin&#8217; uh?'));
@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

$action = empty($_REQUEST['action']) ? '' : $_REQUEST['action'];
$post_id = empty($_REQUEST['post_id']) ? 0 : intval($_REQUEST['post_id']);
$file_id = empty($_REQUEST['file_id']) ? 0 : intval($_REQUEST['file_id']);
$file = ($file_id > 0) ? WPFB_File::GetFile($file_id) : null;

$file_picker = !empty($_REQUEST['pick-file']);
$manage_attachments = !empty($_REQUEST['manage_attachments']);
$post_title = $post_id ? get_the_title($post_id) : null;

switch ($action) {
    case 'detachfile':
        if ($file && $file->CurUserCanEdit() && $file->file_post_id == $post_id) {
            $file->SetPostId(0);
            $file = null;
        }
        break;

    case 'delfile':
        if ($file && $file->CurUserCanEdit()) $file->Remove();
        $file = null;
        break;

    case 'addfile':
        if (!WPFB_Core::CurUserCanUpload()) wp_die(__('Cheatin&#8217; uh?'));
        break;

    case 'updatefile':
        if (!$file || !$file->CurUserCanEdit()) wp_die(__('Cheatin&#8217; uh?'));
        break;

    case 'change-order':
        foreach ($_POST as $n => $v) {
            if (strpos($n, 'file_attach_order-') === 0) {
                $file_id = intval(substr($n, strlen('file_attach_order-')));
                if (!is_null($f = WPFB_File::GetFile($file_id))) {
                    $f->file_attach_order = intval($v);
                    $f->DBSave();
                }
            }
        }
        break;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type"
          content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>"/>
    <title><?php echo WPFB_PLUGIN_NAME ?></title>

    <?php
    wp_admin_css('wp-admin', true);
    wp_admin_css('colors-fresh', true);

    wp_print_scripts();
    wp_print_styles();
    ?>

    <style type="text/css">
        <!--
        h2 {
            margin: 8px 0 5px 0;
            font-size: 12px;
            padding: 0 0 4px 0;
            border-bottom: 1px #BAC3CA solid;
        }

        h3 {
            font-size: 10px;
            margin-left: -4px;
        }

        a {
            color: #00457A;
        }

        #menu {
            text-align: center;
        }

        #menu .button {
            width: 120px;
        }

        #filelist, #insfilelist {
            margin: 5px;
        }

        #tpllist {
            margin-top: 10px;
        }

        .media-item a {
            margin-top: 10px;
        }

        .media-item input {
            text-align: right;
            width: 30px;
            display: inline-block;
        }

        .media-item img.pinkynail {
            display: inline-block;
            vertical-align: middle;
            float: none;
        }

        .media-item .filename {
            display: inline-block;
            vertical-align: middle;
        }

        form, .container {
            padding: 0;
            margin: 10px;
        }

        #media-upload .widefat {
            width: 100% !important;
        }

        #attachbrowser.filetree li span.file {
            background-image: none;
        }

        #sidemenu {
            margin: -30px 15px 0 315px;
            list-style: none;
            position: relative;
            float: right;
            padding-left: 10px;
            font-size: 12px;
        }

        ul#sidemenu {
            font-weight: normal;
            margin: 0 5px;
            left: 0;
            bottom: -1px;
            float: none;
            overflow: hidden;
        }

        /* copied from colors-fresh.css */
        #sidemenu a {
            background-color: #f9f9f9;
            border-color: #f9f9f9;
            border-bottom-color: #dfdfdf;
            text-decoration: none;
            outline-color: red;
        }

        #sidemenu a {
            padding: 0 7px;
            display: block;
            float: left;
            line-height: 28px;
            margin: 2px;
        }

        #sidemenu a.current {
            background: #0073aa;
            color: white;
        }

        #sidemenu li {
            display: inline;
            line-height: 200%;
            list-style: none;
            text-align: center;
            white-space: nowrap;
            margin: 0;
            padding: 0;
        }

        #media-upload-header {
            background-color: #f9f9f9;
        }

        -->
    </style>

    <script type="text/javascript">
        //<![CDATA[

        var userSettings = {
            'url': '<?php echo SITECOOKIEPATH; ?>',
            'uid': '<?php if (!isset($current_user)) $current_user = wp_get_current_user(); echo $current_user->ID; ?>',
            'time': '<?php echo time(); ?>'
        };
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>', pagenow = 'wpfilebase-popup', adminpage = 'wpfilebase-popup', isRtl = <?php echo (int)is_rtl(); ?>;
        var wpfbAjax = '<?php echo WPFB_Core::$ajax_url ?>';
        var usePathTags = <?php echo (int)WPFB_Core::$settings->use_path_tags ?>;
        var yesImgUrl = '<?php echo admin_url('images/yes.png') ?>';
        var manageAttachments = <?php echo (int)($manage_attachments) ?>;
        var filePicker = <?php echo (int)($file_picker) ?>;
        var autoAttachFiles = <?php echo (int)WPFB_Core::$settings->auto_attach_files ?>;

        var theEditor;
        var currentTab = '';
        var selectedCats = [];
        var includeAllCats = false;

                function selectFile(id,filePath, fileDisplayName, a) {
            a = jQuery(a);
            var name = a.text();
            var el = a.parent('span.file');
            var theTag = {"tag": currentTab, <?php echo WPFB_Core::$settings->use_path_tags ? '"path": getFilePath(id)' : '"id":id'; ?>};

            if (filePicker) {
                var fileEvent = new CustomEvent('wpfb-file-selected', {
                    detail: {
                        id: id,
                        path: filePath,
                        name: fileDisplayName
                    }
                });
                
                window.parent.document.body.dispatchEvent(fileEvent);

                if(window.parent.tb_remove)
                    window.parent.tb_remove();
                else {
                    // close
                    var win = window.dialogArguments || opener || parent || top;
                    if (win && typeof close != 'undefined' && close) {
                        if (typeof(win.tinymce) != 'undefined' && win.tinymce && win.tinymce.EditorManager.activeEditor.windowManager)
                            win.tinymce.EditorManager.activeEditor.windowManager.close(window);
                    }
                }
                return;
            }

            if (manageAttachments || currentTab == 'attach') {
                jQuery.ajax({
                    url: wpfbAjax,
                    data: {
                        wpfb_action: "attach-file",
                        post_id:<?php echo $post_id ?>,
                        file_id: id
                    },
                    async: false
                });
                //delayedReload(); stupid :/
                el.css('background-image', 'url(' + yesImgUrl + ')');
                return;
            } else if (currentTab == 'fileurl') {
                <?php if(empty($_GET['content'])) {?>
                var linkText = prompt("<?php echo esc_attr(__('Enter link text. Prepend * to open link in a new tab.', 'wp-filebase')); ?>", name);
                if (!linkText || linkText == null || linkText == '')    return;
                <?php } else echo " var linkText = '" . $_GET['content'] . "'; "; ?>
                theTag.linktext = linkText;
            } else {
                var tpl = jQuery('input[name=filetpl]:checked', '#filetplselect').val();
                if (tpl && tpl != '' && tpl != 'default') theTag.tpl = tpl;
            }
            insertTag(theTag);
        }

        function insBrowserTag() {
            var tag = {tag: currentTab};
            var root = parseInt(jQuery('#browser-root').val());
            if (root > 0)
            <?php echo WPFB_Core::$settings->use_path_tags ? 'tag.path = getCatPath(root);' : 'tag.id = root;'; ?>

                                    return insertTag(tag);
        }

        
        //]]>
    </script>

</head>
<body id="media-upload" class="wp-core-ui" style="background:none;">

<div id="media-upload-header">
    <?php if (!$manage_attachments && !$file_picker) {

        $tabs = array(
            'attach' => __('Attachments', 'wp-filebase'),
            'file' => __('Single file', 'wp-filebase'),
            'fileurl' => __('File URL', 'wp-filebase'),
            'list' => __('File list', 'wp-filebase'),
            'browser' => __('File Tree View', 'wp-filebase'),
        );

        $tabs = apply_filters('wpfilebase_editor_plugin_tabmenu', $tabs);
        ?>
        <ul id='sidemenu'>
            <?php foreach ($tabs as $id => $tab) {
                $uses = array();
                if(is_array($tab)) {
                    $title = $tab['name'];
                    $uses = $tab['uses'];
                } else $title = $tab;
                ?>
                <li><a href="#<?php echo $id; ?>" onclick="return tabclick(this, <?php echo esc_js(json_encode($uses)); ?>)"><?php echo $title; ?></a></li>
            <?php } ?>
        </ul>
    <?php } ?>
</div>

<div id="attach" class="container">
    <?php
    if (!WPFB_Core::$settings->auto_attach_files) {
        echo '<div id="no-auto-attach-note" class="updated">';
        printf(__('Note: Listing of attached files is disabled. You have to <a href="%s">insert the attachments tag</a> to show the files in the content.'), 'javascript:insAttachTag();');
        echo '</div>';
    }


    if ($action == 'addfile' || $action == 'updatefile') {
        // nonce/referer check (security)
        $nonce_action = WPFB . "-" . $action;
        if ($action == 'updatefile') $nonce_action .= $_POST['file_id'];

        // check both nonces, since when using ajax uploader, the nonce if witout suffix -editor
        if (!wp_verify_nonce($_POST['wpfb-file-nonce'], $nonce_action . "-editor") && !wp_verify_nonce($_POST['wpfb-file-nonce'], $nonce_action))
            wp_die(__('Cheatin&#8217; uh?'));

        $result = WPFB_Admin::InsertFile(stripslashes_deep(array_merge($_POST, $_FILES)));
        if (isset($result['error']) && $result['error']) {
            ?>
            <div id="message" class="updated fade"><p><?php echo $result['error']; ?></p></div><?php
            $file = new WPFB_File($_POST);
        } else {
            // success!!!!
            $file_id = $result['file_id'];
            if ($action != 'addfile')
                $file = null;
        }
    }

    $post_attachments = ($post_id > 0) ? WPFB_File::GetAttachedFiles($post_id, true) : array();

    if ($action != 'editfile' && (!empty($post_attachments) || $manage_attachments)) {
        ?>
        <form action="<?php echo add_query_arg(array('action' => 'change-order')) ?>" method="post">
            <h2 class="media-title"><?php echo $post_title ? sprintf(__('Files attached to <i>%s</i>', 'wp-filebase'), $post_title) : __('Files', 'wp-filebase') ?></h2>
            <div id="media-items">
                <?php
                if (empty($post_attachments)) echo "<div class='media-item'>", __('No items found.'), "</div>";
                else foreach ($post_attachments as $pa) { ?>
                    <div class='media-item'>
                        <input type="text" size="3" name="file_attach_order-<?php echo $pa->file_id ?>"
                               value="<?php echo $pa->file_attach_order ?>"/>

                        <?php if (!empty($pa->file_thumbnail)) { ?><img class="pinkynail toggle"
                                                                        src="<?php echo $pa->GetIconUrl(); ?>"
                                                                        alt="" /><?php } ?>

                        <a class='toggle describe-toggle-on'
                           href="<?php echo add_query_arg(array('file_id' => $pa->file_id, 'action' => 'delfile')) ?>"
                           onclick="return confirm('Do you really want to delete this file?')"
                           title="<?php _e('Delete') ?>"><img style="display: inline;"
                                                              src="<?php echo WPFB_PLUGIN_URI . 'extras/jquery/contextmenu/delete_icon.gif'; ?>"/></a>
                        <a class='toggle describe-toggle-on'
                           href="<?php echo add_query_arg(array('file_id' => $pa->file_id, 'action' => 'detachfile')) ?>"
                           title="<?php _e('Remove') ?>"><img
                                src="<?php echo WPFB_PLUGIN_URI . 'extras/jquery/contextmenu/page_white_delete.png'; ?>"/></a>
                        <a class='toggle describe-toggle-on'
                           href="<?php echo add_query_arg(array('file_id' => $pa->file_id, 'action' => 'editfile')) ?>"
                           title="<?php _e('Edit') ?>"><img
                                src="<?php echo WPFB_PLUGIN_URI . 'extras/jquery/contextmenu/page_white_edit.png'; ?>"/></a>

                        <div class='filename'>
                            <span class='title'><?php echo $pa->file_display_name ?></span>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <input type="submit" name="change-order" value="<?php _e('Change Order', 'wp-filebase') ?>"/>
        </form>
        <?php
    }
    // switch simple/extended form
    if (isset($_GET['exform'])) {
        $exform = (!empty($_GET['exform']) && $_GET['exform'] == 1);
        update_user_option(get_current_user_id(), WPFB_OPT_NAME . '_exform_ep', $exform, true);
    } else {
        $exform = (bool)get_user_option(WPFB_OPT_NAME . '_exform_ep');
    }

    //if( (WPFB_Core::CurUserCanUpload()&&empty($file))) TODO
    WPFB_Admin::PrintForm('file', $file, array('exform' => $exform, 'in_editor' => true, 'post_id' => $post_id));
    ?>
    <h2 class="media-title"><?php _e('Attach existing file', 'wp-filebase') ?></h2>
    <ul id="attachbrowser" class="filetree"></ul>
    <?php wpfb_loadclass('TreeviewAdmin');
    WPFB_TreeviewAdmin::RenderHTML("attachbrowser");
    ?>
</div> <!-- attach -->

<?php if (!$manage_attachments && !$file_picker) { ?>
    <form id="filetplselect" class="insert">
        <h2><?php _e('Select Template', 'wp-filebase') ?></h2>
        <label><input type="radio" name="filetpl" value=""
                      checked="checked"/><i><?php _e('Default Template', 'wp-filebase') ?></i></label><br/>
        <?php $tpls = WPFB_Core::GetFileTpls();
        if (!empty($tpls)) {
            foreach ($tpls as $tpl_tag => $tpl_src)
                echo '<label><input type="radio" name="filetpl" value="' . esc_attr($tpl_tag) . '" />' . esc_html($tpl_tag) . '</label><br />';
        } ?>
        <i><a href="<?php echo admin_url('admin.php?page=wpfilebase_tpls#file') ?>"
              target="_parent"><?php _e('Add Template', 'wp-filebase') ?></a></i>
    </form>
    <div id="fileselect" class="container">
        <h2><?php _e('Select File', 'wp-filebase'); ?></h2>
        <ul id="filebrowser" class="filetree"></ul>
        <?php wpfb_loadclass('TreeviewAdmin');
        WPFB_TreeviewAdmin::RenderHTML("filebrowser");
        ?>
    </div>
    <div id="catselect" class="container">
        <h2><?php _e('Select Category'/*def*/); ?></h2>
        <div id="catselect-filter">
            <p><?php _e('Select the categories containing the files you would like to list.', 'wp-filebase'); ?></p>
            <p><input type="checkbox" id="list-all-files" name="list-all-files" value="1"
                      onchange="incAllCatsChanged(this.checked)"/> <label
                    for="list-all-files"><?php _e('Include all Categories', 'wp-filebase'); ?></label></p>
            
        </div>
        
        <ul id="catbrowser" class="filetree"></ul>
        <?php wpfb_loadclass('TreeviewAdmin');
        WPFB_TreeviewAdmin::RenderHTML("catbrowser");
        ?>
    </div>
    <form id="listtplselect" class="insert">
        <h2><?php _e('Select Template', 'wp-filebase') ?></h2>
        <?php $tpls = WPFB_ListTpl::GetAll();
        if (!empty($tpls)) {
            foreach ($tpls as $tpl)
                echo '<label><input type="radio" name="listtpl" value="' . $tpl->tag . '" />' . $tpl->GetTitle() . '</label><br />';
        } ?>
        <i><a href="<?php echo admin_url('admin.php?page=wpfilebase_tpls#list') ?>"
              target="_parent"><?php _e('Add Template', 'wp-filebase') ?></a></i>
    </form>

    <form id="list" class="insert">
        <p>
            <label for="list-num"><?php _e('Files per page:', 'wp-filebase') ?></label>
            <input name="list-num" type="text" id="list-num" value="0" class="small-text"/>
            <?php printf(__('Set to 0 to use the default limit (%d), -1 will disable pagination.', 'wp-filebase'), WPFB_Core::$settings->filelist_num) ?>
            
        </p>

        <p id="list-pagenav-wrap">
            <input type="checkbox" id="list-pagenav" name="list-pagenav" value="1" checked="checked"/>
            <label for="list-pagenav"><?php _e('Display Page Navigation', 'wp-filebase'); ?></label>
        </p>

        <p>
            <input type="checkbox" id="list-show-cats" name="list-show-cats" value="1"/>
            <label for="list-show-cats"><?php _e('Group by Categories', 'wp-filebase');
                echo " / ";
                _e('List selected Categories', 'wp-filebase') ?></label>
        </p>

        <p><a class="button-primary" style="position: fixed; right: 8px; bottom: 8px;" href="javascript:void(0)"
              onclick="return insListTag()"><?php echo _e('Insert') ?></a><br/>
        </p>
    </form>


    <form id="browser" class="insert">
        <p><?php _e('Select the root category of the tree view file browser:', 'wp-filebase'); ?><br/>
            <select name="browser-root"
                    id="browser-root"><?php echo WPFB_Output::CatSelTree(array('none_label' => __('All'))); ?></select>
        </p>

        <p><a class="button-primary" style="position: fixed; right: 8px; bottom: 8px;" href="javascript:void(0)"
              onclick="return insBrowserTag()"><?php echo _e('Insert') ?></a></p>
    </form>

    <form id="filesort" class="insert">
        <h2><?php _e('Sort Order:'); ?></h2>
        <p>
            <label for="list-sort-by"><?php _e("Sort by:") ?></label>
            <select name="list-sort-by" id="list-sort-by" style="width:100%">
                <option value=""><?php _e('Default');
                    echo ' (' . WPFB_Core::$settings->filelist_sorting . ')'; ?></option>
                <?php $opts = WPFB_Models::FileSortFields();
                foreach ($opts as $tag => $name) echo '<option value="' . $tag . '">' . $tag . ' - ' . $name . '</option>'; ?>
            </select>
            <input type="radio" checked="checked" name="list-sort-order" id="list-sort-order-asc" value="asc"/>
            <label for="list-sort-order-asc" class="radio"><?php _e('Ascending'); ?></label>
            <input type="radio" name="list-sort-order" id="list-sort-order-desc" value="desc"/>
            <label for="list-sort-order-desc" class="radio"><?php _e('Descending'); ?></label>
        </p>
    </form>

    <form id="catsort" class="insert">
        <p>
            <label for="list-cat-sort-by"><?php _e('Category order', 'wp-filebase') ?>:</label>
            <select name="list-cat-sort-by" id="list-cat-sort-by" style="width:100%">
                <option value=""><?php _e('None (order of IDs in shortcode)', 'wp-filebase'); ?></option>
                <?php $opts = WPFB_Models::CatSortFields();
                foreach ($opts as $tag => $name) echo '<option value="' . $tag . '">' . $tag . ' - ' . $name . '</option>'; ?>
            </select>
            <input type="radio" checked="checked" name="list-cat-sort-order" id="list-cat-sort-order-asc" value="asc"/>
            <label for="list-cat-sort-order-asc" class="radio"><?php _e('Ascending'); ?></label>
            <input type="radio" name="list-cat-sort-order" id="list-cat-sort-order-desc" value="desc"/>
            <label for="list-cat-sort-order-desc" class="radio"><?php _e('Descending'); ?></label>
        </p>
    </form>


    <?php
    do_action('wpfilebase_editor_plugin_tabs');

    ?>
<?php } /*manage_attachments*/ ?>

<?php
do_action('admin_print_footer_scripts');
?>
<script type="text/javascript">
    initEditorPlugin();
    if (typeof wpOnload == 'function')wpOnload();
</script>
<?php wpfb_call('Output', 'PrintJS'); /* only required for wpfbConf */ ?>
</body>
</html>