<?php

class SyncTest extends WP_UnitTestCase {
   function testSyncAction()
   {
       do_action('wpfilebase_sync');
   }
}

