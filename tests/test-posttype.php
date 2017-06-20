<?php

class PostTypeTest extends WP_UnitTestCase {
    function testSetUser()
    {
        if(get_current_user_id())
            return;

        $usr = get_user_by('login', 'test_admin');
        if($usr && $usr->exists()) {
            wp_set_current_user($usr->ID);
            return;
        }
        $usr = wp_create_user('test_admin', 'test_admin');
        $this->assertNotWPError($usr);
        wp_set_current_user($usr);
    }

    function testCreateTree() {
        $this->testSetUser();

        wpfb_loadclass('Admin');


        /** @var WPFB_Category $parent */
        $parent = null;

        /** @var WPFB_Category[] $cats */
        $cats = array();

        for($d = 0; $d < 4; $d++) {
            $res = WPFB_Admin::InsertCategory(array('cat_name' => "layer $d", 'cat_parent' => $parent ? $parent->GetId() : 0));
            $this->assertEmpty($res['error']);
            /** @var WPFB_Category $cat */
            $cat = $res['cat'];

            $this->assertTrue($parent ? $cat->GetParent()->Equals($parent) : (is_null($cat->GetParent())));
            $this->assertTrue(is_dir($cat->GetLocalPath()));

            $cats[] = $cat;
            $parent = $cat;
        }

       // print_r(array_map( function($c) { return strval($c);}, $cats));

        $files = new TestFileSet();

        $res = WPFB_Admin::InsertFile(array(
            'file_remote_uri' => 'file://'.$files->getImageBanner(),
            'file_category' => $parent));
        $this->assertEmpty($res['error'],$res['error']);
        /** @var WPFB_File $file01 */
        $file01 = $res['file'];


    }
}

