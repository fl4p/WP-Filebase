<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/wp-filebase.php';
	
	add_action('init', function() {
		require_once dirname( dirname( __FILE__ ) ) . '/classes/Core.php';
		
		wpfb_loadclass('Setup');
		WPFB_Setup::OnActivateOrVerChange(null);
		
		WPFB_Core::$settings = (object) get_option(WPFB_OPT_NAME);
		WPFB_Core::InitClass();
	}, 1);

}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

require dirname( __FILE__ ) . '/create-test-files.php';



