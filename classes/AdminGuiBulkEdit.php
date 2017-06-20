<?php

class WPFB_AdminGuiBulkEdit
{

    static private function getFileAndCatIds()
    {
        $file_ids = array_filter(array_map('intval', empty($_POST['file']) ? (empty($_POST['files']) ? array() : json_decode(stripslashes($_POST['files']))) : $_POST['file']));
        $cat_ids = array_filter(array_map('intval', empty($_POST['cat']) ? (empty($_POST['cats']) ? array() : json_decode(stripslashes($_POST['cats']))) : $_POST['cat']));

        $cat_select = empty($cat_ids) ? " 0=1 " : WPFB_File::GetSqlCatWhereStr($cat_ids);
        $file_select = empty($file_ids) ? " 0=1 " : ('file_id = ' . implode(' OR file_id = ', $file_ids));


        return array($file_ids, $cat_ids, " ($cat_select) OR ($file_select) ");
    }


    static function Process()
    {
        list($file_ids, $cat_ids, $sql_where) = self::getFileAndCatIds();
        $files = WPFB_File::GetFiles2($sql_where, 'edit');

        $data = (object)$_POST;

        // set owner
        if (!empty($data->file_added_by)) {
            $user = get_user_by('login', $data->file_added_by);
            $data->file_added_by = ($user && $user->exists()) ? $user->ID : 0;
        } else
            $data->file_added_by = 0;

        foreach ($files as $file) {
            $file->Lock();

            // category
            if (!empty($data->file_category))
                $file->ChangeCategoryOrName($data->file_category);

            // add tags
            if (!empty($data->file_tags))
                $file->SetTags($file->file_tags . ',' . $data->file_tags);

            if (!empty($data->file_author))
                $file->file_author = $data->file_author;

            if (isset($data->file_direct_linking) && $data->file_direct_linking !== "") {
                $file->file_direct_linking = (int)$data->file_direct_linking;
            }

            if (isset($data->file_offline) && $data->file_offline !== "") {
                $file->file_offline = (int)$data->file_offline;
            }

            if (!empty($data->file_password))
                $file->file_password = $data->file_password;

            if ($data->file_added_by)
                $file->file_added_by = $data->file_added_by;

            $file->Lock(false);
            $file->DBSave();
        }


        return sprintf(__('%d File(s) processed.', 'wp-filebase'), count($files));
    }


    static function Display()
    {
        list($file_ids, $cat_ids, $sql_where) = self::getFileAndCatIds();

        $num_files = WPFB_File::GetNumFiles2($sql_where, 'edit');
        $prefix = "bulk";
        wp_print_scripts('jquery-ui-autocomplete');
        ?>
        <div class="form-wrap">
            <h3><?php printf(__('Batch edit %d files', 'wp-filebase'), $num_files); ?></h3>
            <form action="<?php echo remove_query_arg(array('action')) ?>" method="post">
                <input type="hidden" name="action" value="edit"/>
                <input type="hidden" name="action2" value="apply"/>
                <input type="hidden" name="files" value="<?php echo esc_attr(json_encode($file_ids)); ?>"/>
                <input type="hidden" name="cats" value="<?php echo esc_attr(json_encode($cat_ids)); ?>"/>

                <div>
                    <label for="<?php echo $prefix ?>file_category"><?php _e('Category', 'wp-filebase') ?></label>
                    <select name="file_category" id="<?php echo $prefix; ?>file_category"
                            class="wpfb-cat-select"><?php wpfb_loadclass('Category');
                        echo WPFB_Output::CatSelTree(array('none_label' => __('&mdash; No Change &mdash;'), 'check_add_perm' => true, 'add_cats' => true)); ?></select>
                </div>
                <div class="form-field">
                    <label for="<?php echo $prefix; ?>file_added_by"><?php _e('Owner') ?></label>
                    <input id="<?php echo $prefix; ?>file_added_by" name="file_added_by" type="text"
                           placeholder="<?php _e('&mdash; No Change &mdash;'); ?>" class="wpfb-user-autocomplete"/>
                </div>
                <div class="form-field">
                    <label for="<?php echo $prefix; ?>file_tags"><?php _e('Add Tags') ?></label>
                    <input id="<?php echo $prefix; ?>file_tags" name="file_tags" type="text"/>
                </div>

                <div class="form-field">
                    <label for="<?php echo $prefix; ?>file_author"><?php _e('Author') ?></label>
                    <input id="<?php echo $prefix; ?>file_author" name="file_author" type="text"
                           placeholder="<?php _e('&mdash; No Change &mdash;'); ?>"/>
                </div>

                <!--
	<div class="form-field">		
		<label for="<?php echo $prefix; ?>file_author"><?php _e('Author') ?></label>
		<input id="<?php echo $prefix; ?>file_author" name="file_author" type="text" placeholder="<?php _e('&mdash; No Change &mdash;'); ?>" />
	</div>


	<div class="form-field">		
		<label for="<?php echo $prefix; ?>file_description"><?php _e('Description') ?></label>
		<textarea id="<?php echo $prefix; ?>file_description" name="file_description"></textarea>
	</div>
-->
                <div class="" style="float: left;">
                    <fieldset>
                        <legend class=""><?php _e('Direct Linking', 'wp-filebase') ?></legend>
                        <label title="<?php _e('&mdash; No Change &mdash;') ?>"><input type="radio"
                                                                                       name="file_direct_linking"
                                                                                       value="" <?php checked(1); ?>/> <?php _e('&mdash; No Change &mdash;') ?>
                        </label>
                        <label title="<?php _e('Yes') ?>"><input type="radio" name="file_direct_linking"
                                                                 value="1"/> <?php _e('Allow direct linking', 'wp-filebase') ?>
                        </label>
                        <label title="<?php _e('No') ?>"><input type="radio" name="file_direct_linking"
                                                                value="0"/> <?php _e('Redirect to post', 'wp-filebase') ?>
                        </label>
                        
                    </fieldset>
                </div>

                <div class="">
                    <fieldset>
                        <legend class=""><?php _e('Offline', 'wp-filebase') ?></legend>
                        <label title="<?php _e('&mdash; No Change &mdash;') ?>"><input type="radio" name="file_offline"
                                                                                       value="" <?php checked(1); ?>/> <?php _e('&mdash; No Change &mdash;') ?>
                        </label>
                        <label title="<?php _e('Yes') ?>"><input type="radio" name="file_offline"
                                                                 value="1"/> <?php _e('Set offline', 'wp-filebase') ?>
                        </label>
                        <label title="<?php _e('No') ?>"><input type="radio" name="file_offline"
                                                                value="0"/> <?php _e('Set online', 'wp-filebase') ?>
                        </label>
                    </fieldset>
                </div>




                <div style="clear:both;"></div>

                <!--

-->

                

                <p class="submit"><input type="submit" name="submit" class="button-primary"
                                         value="<?php _e("Submit") ?>"/></p>
            </form>
        </div>
        <!--
        post,
        access permission,
        version,
        custom vars,
        lang,
        platforms,
        requirements,
        date
        secondary cats,
        download counter,
        -->
        <?php
    }
}
