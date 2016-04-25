<?php

class DownloadTest extends WP_UnitTestCase
{
    function testDownload()
    {
        $usr = wp_create_user('test_admin', 'test_admin');
        $this->assertNotWPError($usr);
        wp_set_current_user($usr);

        $files = new TestFileSet();

        $res = WPFB_Admin::InsertFile(array(
            'file_remote_uri' => 'file://' . $files->getImageBanner()
        ));
        $this->assertEmpty($res['error'], $res['error']);
        /** @var WPFB_File $file01 */
        $file01 = $res['file'];

        $file01->Delete();
    }
}

