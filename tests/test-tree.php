<?php

class TreeTest extends WP_UnitTestCase {
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

    function testCreateCatAndFile() {
        WPFB_Category::DisableBubbling(false);

        $this->testSetUser();
        wpfb_loadclass('Admin');
        $files = new TestFileSet();

        $res = WPFB_Admin::InsertCategory(array('cat_name' => "cat"));
        $this->assertEmpty($res['error'], $res['error']);
        /** @var WPFB_Category $cat */
        $cat = $res['cat'];

        $res = WPFB_Admin::InsertFile(array(
            'file_remote_uri' => 'file://'.$files->getSmallTxt(),
            'file_category' => $cat
        ));
        $this->assertEmpty($res['error'],$res['error']);
        /** @var WPFB_File $file02 */
        $file01 = $res['file'];

        $this->assertEquals(1, $cat->cat_num_files_total);
        $this->assertEquals(1, $cat->cat_num_files);

        $this->assertEquals(1, $cat->DBReload()->cat_num_files_total);
        $this->assertEquals(1, $cat->DBReload()->cat_num_files);


        $this->assertEquals($cat->DBReload(), $file01->GetParent());


    }

    /**
     * @depends testCreateCatAndFile
     */
    function testCreateTree() {
        $this->testSetUser();

        wpfb_loadclass('Admin');
        WPFB_Category::DisableBubbling(false);


        /** @var WPFB_Category $parent */
        $parent = null;

        /** @var WPFB_Category[] $cats */
        $cats = array();

        for($d = 0; $d < 4; $d++) {
            $res = WPFB_Admin::InsertCategory(array('cat_name' => "layer $d", 'cat_parent' => $parent ? $parent->GetId() : 0));
            $this->assertEmpty($res['error'], $res['error']);
            /** @var WPFB_Category $cat */
            $cat = $res['cat'];

            $this->assertTrue($parent ? $cat->GetParent()->Equals($parent) : (is_null($cat->GetParent())));
            $this->assertTrue(is_dir($cat->GetLocalPath()));

            $cats[] = $cat;
            $parent = $cat;
        }


        $this->assertEquals($cats[0]->cat_id, $cats[1]->GetParent()->cat_id);
        //$this->assertEquals($cats[2]->GetParent(), $cats[1], '', 0.0, 2, true);

       // print_r(array_map( function($c) { return strval($c);}, $cats));

        $files = new TestFileSet();

        $res = WPFB_Admin::InsertFile(array(
            'file_remote_uri' => 'file://'.$files->getImageBanner(),
            'file_category' => $parent));
        $this->assertEmpty($res['error'],$res['error']);
        /** @var WPFB_File $file01 */
        $file01 = $res['file'];


        $res = WPFB_Admin::InsertFile(array(
            'file_remote_uri' => 'file://'.$files->getSmallTxt(),
            'file_category' => $parent->GetParent()
        ));
        $this->assertEmpty($res['error'],$res['error']);
        /** @var WPFB_File $file02 */
        $file02 = $res['file'];

        $this->assertEquals($file01->GetParent()->cat_id, $parent->cat_id);

        $this->assertEquals($file02->GetParent()->cat_id, $parent->GetParent()->cat_id);

        $this->assertEquals($file02->GetParent(), $parent->GetParent());

        $this->assertEquals(2, $parent->GetParent()->cat_num_files_total);
        $this->assertEquals(2, $file02->GetParent()->cat_num_files_total);
        $this->assertEquals(1, $file02->GetParent()->cat_num_files);

        $this->assertEquals(2, $cats[0]->cat_num_files_total);


        $this->assertEquals(1, count($parent->GetParent()->GetChildCats(true)));
        $this->assertEquals(1, count($file02->GetParent()->GetChildCats(true)));


        $this->assertEquals(2, count($cats[0]->GetChildFiles(true)), $cats[0]);
        $this->assertEquals(3, count($cats[0]->GetChildCats(true)), $cats[0]);


        $this->assertEquals(2, count($cats[2]->GetChildFiles(true)), $cats[2]);
        $this->assertEquals(1, count($cats[2]->GetChildCats(true)), $cats[2]);



        $this->assertEquals(2, count($cats[1]->GetChildCats(true)), $cats[1]);
        $this->assertEquals(2, count($cats[1]->GetChildFiles(true)), $cats[1]);


        $res = $parent->Delete();
        $this->assertEmpty($res['error'],$res['error']);
        unset($cats[3]);

        $file01->DBReload();  // TODO fix: need to reload from DB!

        $this->assertFileExists($file01->GetLocalPath());
        $this->assertFileExists($file01->GetThumbPath());



       // print_r(array_map( function($c) { return strval($c);}, $cats));

        $this->assertEquals(strval($file01->GetParent()), strval($file02->GetParent()));

        $this->assertEquals(0, count($cats[2]->DBReload()->GetChildCats(true)), $cats[2]);
        $this->assertEquals(2, count($cats[2]->GetChildFiles(false)), $cats[2]);

        $this->assertEquals(1, count($cats[1]->DBReload()->GetChildCats(true)), $cats[1]);
        $this->assertEquals(2, count($cats[1]->GetChildFiles(true)), $cats[1]);

        $this->assertEquals(2, count($cats[0]->DBReload()->GetChildCats(true)), $cats[0]);
        $this->assertEquals(2, count($cats[0]->GetChildFiles(true)), $cats[0]);

        foreach($cats as $cat) {
            $res = $cat->DBReload()->Delete();
            $this->assertEmpty($res['error'],$res['error']);
        }

        $thumb = $file01->GetThumbPath();

        $this->assertTrue($file01->DBReload()->Delete());
        $this->assertTrue($file02->DBReload()->Delete());

        $this->assertFileNotExists($thumb);

    }
}

