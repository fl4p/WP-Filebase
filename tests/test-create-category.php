<?php

class CreateCatTest extends WP_UnitTestCase {

	function test_new_cat() {
        wpfb_loadclass('Admin');
		$res = WPFB_Admin::InsertCategory(array('cat_name' => 'Root Cat'));
		$this->assertEmpty($res['error'], $res['error']);

        /** @var WPFB_Category $root_cat */
        $root_cat = $res['cat'];

        $res = WPFB_Admin::InsertCategory(array('cat_folder' => 'layer01', 'cat_parent' => $root_cat->GetId()));
        $this->assertEmpty($res['error']);

        /** @var WPFB_Category $sub_cat */
        $sub_cat = $res['cat'];

        $this->assertTrue($sub_cat->GetParent()->Equals($root_cat));

        $res = $sub_cat->Delete();
        $this->assertEmpty($res['error']);

        $res = $root_cat->Delete();
        $this->assertEmpty($res['error']);
	}


    /**
     * @depends test_new_cat
     */
    function test_cat_tree() {
        wpfb_loadclass('Admin');

        $depth = 4;

        /** @var WPFB_Category $parent */
        $parent = null;

        $cats = array();

        for($d = 0; $d < $depth; $d++) {
            $res = WPFB_Admin::InsertCategory(array('cat_name' => "layer $d", 'cat_parent' => $parent ? $parent->GetId() : 0));
            $this->assertEmpty($res['error']);
            /** @var WPFB_Category $cat */
            $cat = $res['cat'];

            $this->assertTrue($parent ? $cat->GetParent()->Equals($parent) : (is_null($cat->GetParent())));

            $cats[] = $cat;
        }


        foreach(array_reverse($cats) as $cat) {
            $res = $cat->Delete();
            $this->assertEmpty($res['error'],$res['error']);
        }
    }
}

