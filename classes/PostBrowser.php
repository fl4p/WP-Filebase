<?php

class WPFB_PostBrowser {

    public static function Main($args) {
        if (!defined('WPFB') || !current_user_can('edit_posts'))
            wp_die(__('Cheatin&#8217; uh?'));
        ?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
            <head>
                <title><?php _e('Posts'); ?></title>
                <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
                <?php
                wp_enqueue_script('wpfb-treeview');

                wp_enqueue_style('global');
                wp_enqueue_style('wp-admin');
                wp_enqueue_style('media');
                wp_enqueue_style('ie');
                wp_enqueue_style('wpfb-treeview');

                do_action('admin_print_styles');
                do_action('admin_print_scripts');
                do_action('admin_head');
                ?>

                <script type="text/javascript">
                    //<![CDATA[

                    jQuery(document).ready(function () {
                        jQuery("#wpfilebase-post-browser").treeview({
                            url: "<?php echo WPFB_Core::$ajax_url ?>",
                            ajax: {
                                data: {wpfb_action: "postbrowser", onclick: "selectPost(%d,'%s')"},
                                type: "post", complete: browserAjaxComplete
                            },
                            animated: "medium"
                        });
                    });

                    function selectPost(postId, postTitle)
                    {
                        var el;
        <?php if (!empty($args['inp_id'])) : ?>
                            el = opener.document.getElementById('<?php echo $args['inp_id']; ?>');
                            if (el != null)
                                el.value = postId;
        <?php endif;
        if (!empty($args['tit_id'])) :
            ?>
                            el = opener.document.getElementById('<?php echo $args['tit_id']; ?>');
                            if (el != null)
                                el.innerHTML = postTitle;
        <?php endif; ?>
                        window.close();
                        return true;
                    }

                    function browserAjaxComplete(jqXHR, textStatus)
                    {
                        if (textStatus != "success")
                        {
                            alert("AJAX Request error: " + textStatus);
                        }
                    }
                    //]]>
                </script>
            </head>
            <body>
                <div style="margin: 10px">
                    <div id="wphead"><h1><?php _e('Posts'); ?></h1></div>
                    <ul id="wpfilebase-post-browser" class="filetree">		
                    </ul>
                </div>
            </body>
        </html><?php
        exit;
    }

    public static function Ajax($args) {

        if (!current_user_can('edit_posts')) {
            wp_send_json(array(array('id' => '0', 'text' => __('Cheatin&#8217; uh?'), 'classes' => '', 'hasChildren' => false)));
            exit;
        }

        $id = (empty($args['root']) || $args['root'] == 'source') ? 0 : intval($args['root']);
        $onclick = empty($args['onclick']) ? '' : $args['onclick'];

        $args = array('hide_empty' => 0, 'hierarchical' => 1, 'orderby' => 'name', 'parent' => $id);
        $terms = get_terms('category', $args);

        $items = array();
        foreach ($terms as &$t) {
            $items[] = array(
                'id' => $t->term_id, 'text' => esc_html($t->name), 'classes' => 'folder',
                'hasChildren' => ($t->count > 0)
            );
        }

        $terms = get_posts(array(
            'numberposts' => 0, 'nopaging' => true,
//'category' => $id,
            'category__in' => array($id), // undoc: dont list posts of child cats!
            'orderby' => 'title', 'order' => 'ASC',
            'post_status' => 'any' // undoc: get private posts aswell
        ));

        if ($id == 0)
            $terms = array_merge($terms, get_pages(/* array('parent' => $id) */));

        foreach ($terms as $t) {
            $post_title = stripslashes(get_the_title($t->ID));
            if (empty($post_title))
                $post_title = $t->ID;
            $items[] = array('id' => $t->ID, 'classes' => 'file',
                'text' => ('<a href="javascript:' . sprintf($onclick, $t->ID, str_replace('\'', '\\\'', /* htmlspecialchars */ $post_title)) . '">' . $post_title . '</a>'));
        }

        wp_send_json($items);
    }

}
